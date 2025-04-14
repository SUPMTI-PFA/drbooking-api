<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Location;

class OnlyOneDefaultLocationValidator extends ConstraintValidator
{
    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    public function validate($entity, Constraint $constraint)
    {
        if (!$entity instanceof Location) {
            return;
        }

        $country = $entity->getCountry();

        
        if (!$country) {
            return;
        }
        
        $repo = $this->entityManager->getRepository(Location::class);
        $locationsInCountry = $repo->findBy(['country' => $country]);
        
        // 👉 Si aucune Location n'existe pour ce pays
        if (count($locationsInCountry) === 0 && !$entity->isIsDefault()) {
            $this->context->buildViolation('The first location in a country must be set as default.')
            ->atPath('isDefault')
            ->addViolation();
            
            return;
        }
        
        // 👉 S'il y a déjà une autre location par défaut dans ce pays
        $defaultLocation = $repo->findOneBy([
            'country' => $country,
            'isDefault' => true,
        ]);

        if ($defaultLocation && $defaultLocation !== $entity && $entity->isIsDefault()) {
            $this->context->buildViolation($constraint->message)
                ->atPath('isDefault')
                ->addViolation();
        }
    }
}
