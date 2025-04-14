<?php

namespace App\Service;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use App\Helpers\Helpers;
use DateTime;
use DateTimeImmutable;
use ReflectionClass;

class GlobalService extends AbstractController
{
    public function __construct(
        private DenormalizerInterface $denormalizer,
        private EntityManagerInterface $entityManager,
        private UserRepository $driverRepository,
        private ParameterBagInterface $params,
        private RequestStack $requestStack,
        private Helpers $helpers,
        private SerializerInterface $serializer,
        private ParameterBagInterface $parameters,
    ) {}


    public function PersistEntityDenormalizer(array $data, string $entityClass)
    {
        return $this->denormalizer->denormalize($data, $entityClass, 'json');
    }

    public function UpdateEntityDenormalizer(object $targetEntity, array $data, string $entityClass)
    {
        if (property_exists($targetEntity, 'updatedAt')) {
            $targetEntity->setUpdatedAt(new DateTimeImmutable());
        }

        return $this->denormalizer->denormalize($data, $entityClass, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $targetEntity]);
    }
}
