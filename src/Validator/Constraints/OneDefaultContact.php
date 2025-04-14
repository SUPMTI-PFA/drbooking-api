<?php
// src/Validator/Constraints/OneDefaultContact.php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class OneDefaultContact extends Constraint
{
    public $message = 'Only one contact can be set as default per user.';

    public function validatedBy()
    {
        return \get_class($this).'Validator';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}