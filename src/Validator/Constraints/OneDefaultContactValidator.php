<?php
// src/Validator/Constraints/OneDefaultContactValidator.php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\UserContact;

class OneDefaultContactValidator extends ConstraintValidator
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function validate($entity, Constraint $constraint)
    {
        if (!$entity instanceof UserContact) {
            return;
        }

        $user = $entity->getUser();
        if (!$user) {
            return;
        }

        // Check if there's already a default contact for this user
        $defaultContact = $this->entityManager->getRepository(UserContact::class)->findOneBy([
            'user' => $user,
            'isDefaultContact' => true
        ]);

        if ($defaultContact && $defaultContact !== $entity && $entity->isIsDefaultContact()) {
            $this->context->buildViolation($constraint->message)
                ->atPath('isDefaultContact')
                ->addViolation();
        }
    }
}