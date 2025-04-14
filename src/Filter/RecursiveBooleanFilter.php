<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use ReflectionClass;

class RecursiveBooleanFilter extends AbstractFilter
{
    public function __construct(
        ManagerRegistry $managerRegistry,
        LoggerInterface $logger = null,
        ?array $properties = null,
        NameConverterInterface $nameConverter = null
    ) {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        Operation $operation = null,
        array $context = []
    ): void {
        if (!$this->isPropertyEnabled($property, $resourceClass)) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $metadata = $this->getClassMetadata($resourceClass);

        // Handle nested properties
        $properties = explode('.', $property);
        $currentAlias = $alias;

        foreach ($properties as $propertyPart) {
            if (in_array($propertyPart, $metadata->getAssociationNames())) {
                $association = $metadata->getAssociationTargetClass($propertyPart);
                $joinAlias = $queryNameGenerator->generateJoinAlias($propertyPart);
                if (!$this->isAlreadyJoined($queryBuilder, $joinAlias)) {
                    $queryBuilder->leftJoin(sprintf('%s.%s', $currentAlias, $propertyPart), $joinAlias);
                }
                $currentAlias = $joinAlias;
                $metadata = $this->getClassMetadata($association);
            } else {
                $field = sprintf('%s.%s', $currentAlias, $propertyPart);
                if ($metadata->hasField($propertyPart)) {
                    $fieldType = $metadata->getTypeOfField($propertyPart);

                    // Handle boolean fields
                    if ($fieldType === 'boolean') {
                        if (is_bool($value)) {
                            $queryBuilder->andWhere(sprintf('%s = :%s', $field, $this->normalizeParameterName($property)))
                                ->setParameter($this->normalizeParameterName($property), $value);
                        } elseif (is_string($value)) {
                            // Convert string to boolean
                            $boolValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                            if ($boolValue !== null) {
                                $queryBuilder->andWhere(sprintf('%s = :%s', $field, $this->normalizeParameterName($property)))
                                    ->setParameter($this->normalizeParameterName($property), $boolValue);
                            }
                        } elseif (is_int($value)) {
                            // Convert integer to boolean (1 => true, 0 => false)
                            $boolValue = (bool) $value;
                            $queryBuilder->andWhere(sprintf('%s = :%s', $field, $this->normalizeParameterName($property)))
                                ->setParameter($this->normalizeParameterName($property), $boolValue);
                        } else {
                            // Invalid boolean value
                            $this->logger->warning(sprintf('Invalid boolean value for property "%s" on resource "%s"', $property, $resourceClass));
                        }
                    } else {
                        $this->logger->warning(sprintf('Property "%s" is not a boolean field on resource "%s"', $property, $resourceClass));
                    }
                }
            }
        }
    }

    private function isAlreadyJoined(QueryBuilder $queryBuilder, string $joinAlias): bool
    {
        foreach ($queryBuilder->getDQLPart('join') as $joins) {
            foreach ($joins as $join) {
                if ($join->getAlias() === $joinAlias) {
                    return true;
                }
            }
        }
        return false;
    }

    private function normalizeParameterName(string $property): string
    {
        return str_replace('.', '_', $property);
    }

    public function getDescription(string $resourceClass): array
    {
        $reflectionClass = new ReflectionClass($resourceClass);
        $properties = $reflectionClass->getProperties();

        $description = [];
        foreach ($properties as $property) {
            // Only include boolean fields in the description
            $fieldType = $this->getClassMetadata($resourceClass)->hasField($property->getName())
                ? $this->getClassMetadata($resourceClass)->getTypeOfField($property->getName())
                : null;

            if ($fieldType === 'boolean') {
                $description[$property->getName()] = [
                    'property' => $property->getName(),
                    'type' => 'boolean',
                    'required' => false,
                    'description' => 'Boolean filter for ' . $property->getName(),
                ];
            }
        }

        return $description;
    }

    protected function isPropertyEnabled(string $property, string $resourceClass): bool
    {
        return true;
    }
}
