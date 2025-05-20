<?php

namespace App\Util;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;

class SlugGenerator
{
    private const MAX_SLUG_LENGTH = 255;
    private const MAX_ATTEMPTS = 100;

    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function generateSlug(
        string $sourceString,
        string $entityClass,
        ?string $currentSlug = null,
        array $options = []
    ): string {
        // Normalization and configuration
        $options = array_merge([
            'separator' => '-',
            'lowercase' => true,
            'max_length' => self::MAX_SLUG_LENGTH,
            'field' => 'slug',
        ], $options);

        // Generate base slug
        $slug = $this->normalizeString($sourceString, $options);

        // If existing slug is the same, return it
        if ($currentSlug === $slug) {
            return $slug;
        }

        // Check and maintain uniqueness
        return $this->findUniqueSlug($slug, $entityClass, $options['field'], $options['max_length'], $options);
    }

    private function normalizeString(string $input, array $options): string
    {
        // Remove extra whitespace
        $cleanString = preg_replace('/\s+/', ' ', trim($input));

        // Convert to ASCII slug
        $slug = $this->slugger->slug($cleanString, $options['separator'], 'en')->toString();

        // Apply lowercase if requested
        if ($options['lowercase']) {
            $slug = mb_strtolower($slug);
        }

        // Truncate to max length while preserving word boundaries
        return (new UnicodeString($slug))
            ->truncate($options['max_length'], '', false)
            ->toString();
    }

    private function findUniqueSlug(
        string $originalSlug,
        string $entityClass,
        string $fieldName,
        int $maxLength,
        array $options
    ): string {
        $attempt = 1;
        $baseSlug = $originalSlug;
        $repository = $this->entityManager->getRepository($entityClass);

        do {
            $slug = $originalSlug;

            if ($attempt > 1) {
                $suffix = $options['separator'] . ($attempt - 1);
                $truncateLength = $maxLength - mb_strlen($suffix);
                $slug = (new UnicodeString($baseSlug))
                    ->truncate($truncateLength, '', false)
                    ->append($suffix)
                    ->toString();
            }

            $existing = $repository->findOneBy([$fieldName => $slug]);
            if (!$existing) {
                return $slug;
            }

            $attempt++;
        } while ($attempt <= self::MAX_ATTEMPTS);

        throw new \RuntimeException(sprintf(
            'Unable to generate unique slug for "%s" after %d attempts',
            $originalSlug,
            self::MAX_ATTEMPTS
        ));
    }
}
