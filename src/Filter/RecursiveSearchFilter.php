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

class RecursiveSearchFilter extends AbstractFilter
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
        if ($property === 'model.bag.count') {
            return; // Skip filtering for this property
        }

        // Handle 'hasModelGroup' custom filter
        if ($property === 'hasModelGroup') {
            $this->filterHasModelGroup($value, $queryBuilder, $queryNameGenerator, $resourceClass);
            return;
        }

        if (!$this->isPropertyEnabled($property, $resourceClass)) {
            return;
        }

        $asEqual = ['code', 'email'];
        $excludedFields = ['date', 'datetime', 'datetime_immutable', 'date_immutable', 'boolean'];

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

                    // Exclude the fields
                    if (in_array($fieldType, $excludedFields) && gettype($value) === "array") {
                        return;
                    }

                    // Handle NULL and NOT NULL cases
                    if (strtoupper($value) === 'NULL') {
                        $queryBuilder->andWhere(sprintf('%s IS NULL', $field));
                    } elseif (strtoupper($value) === 'NOT NULL') {
                        $queryBuilder->andWhere(sprintf('%s IS NOT NULL', $field));
                    } else {
                        // Handle multiple values
                        if (str_contains($value, ',')) {
                            $values = explode(',', $value);
                            $paramName = $this->normalizeParameterName($property);

                            $queryBuilder->andWhere(sprintf('%s IN (:%s)', $field, $paramName))
                                ->setParameter($paramName, $values);
                        } elseif (in_array($fieldType, ['integer', 'smallint', 'bigint']) || in_array($propertyPart, $asEqual)) {
                            // Apply equality filter
                            $queryBuilder->andWhere(sprintf('%s = :%s', $field, $this->normalizeParameterName($property)))
                                ->setParameter($this->normalizeParameterName($property), $value);
                        } else {
                            // Apply LIKE filter
                            $queryBuilder->andWhere(sprintf('%s LIKE :%s', $field, $this->normalizeParameterName($property)))
                                ->setParameter($this->normalizeParameterName($property), '%' . $value . '%');
                        }
                    }
                } else {
                    $this->logger->warning(sprintf('Property "%s" does not exist on resource "%s"', $property, $resourceClass));
                }
            }
        }
    }

    private function filterHasModelGroup($value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass)
    {
        $alias = $queryBuilder->getRootAliases()[0];

        $queryBuilder->andWhere(sprintf("%s.modelGroups IS %s EMPTY", $alias, $value === 'true' ? 'NOT' : ''));
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
                'description' => 'Search filter for ' . $property->getName(),
            ];
        }

        // Adding description for our custom filter
        $description['hasModelGroup'] = [
            'property' => 'hasModelGroup',
            'type' => 'boolean',
            'required' => false,
            'description' => 'Filter models not in any model group',
        ];

        return $description;
    }

    protected function isPropertyEnabled(string $property, string $resourceClass): bool
    {
        return true;
    }
}
