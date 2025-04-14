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

class RecursiveDateFilter extends AbstractFilter
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

                    if (in_array($fieldType, ['date', 'datetime', 'datetime_immutable', 'date_immutable']) && gettype($value) === "array") {

                        if (isset($value['eq'])) {
                            $queryBuilder->andWhere(sprintf('%s = :%s_eq', $field, $this->normalizeParameterName($property)))
                                ->setParameter(sprintf('%s_eq', $this->normalizeParameterName($property)), $value['eq']);
                        }
                        if (isset($value['gt'])) {
                            $queryBuilder->andWhere(sprintf('%s > :%s_gt', $field, $this->normalizeParameterName($property)))
                                ->setParameter(sprintf('%s_gt', $this->normalizeParameterName($property)), $value['gt']);
                        }
                        if (isset($value['gte'])) {
                            $queryBuilder->andWhere(sprintf('%s >= :%s_gte', $field, $this->normalizeParameterName($property)))
                                ->setParameter(sprintf('%s_gte', $this->normalizeParameterName($property)), $value['gte']);
                        }
                        if (isset($value['lt'])) {
                            $queryBuilder->andWhere(sprintf('%s < :%s_lt', $field, $this->normalizeParameterName($property)))
                                ->setParameter(sprintf('%s_lt', $this->normalizeParameterName($property)), $value['lt']);
                        }
                        if (isset($value['lte'])) {
                            $queryBuilder->andWhere(sprintf('%s <= :%s_lte', $field, $this->normalizeParameterName($property)))
                                ->setParameter(sprintf('%s_lte', $this->normalizeParameterName($property)), $value['lte']);
                        }
                    } else {
                        $this->logger->warning(sprintf('Property "%s" is not a date field on resource "%s"', $property, $resourceClass));
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
            $description[$property->getName()] = [
                'property' => $property->getName(),
                'type' => 'string',
                'required' => false,
                'description' => 'Date filter for ' . $property->getName(),
            ];
        }

        return $description;
    }

    protected function isPropertyEnabled(string $property, string $resourceClass): bool
    {
        return true;
    }
}
