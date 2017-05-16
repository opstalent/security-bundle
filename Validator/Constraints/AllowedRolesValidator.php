<?php

namespace Opstalent\SecurityBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @author Szymon Kunowski <szymon.kunowski@gmail.com>
 * @package Opstalent\Common
 */
class AllowedRolesValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if ($this->isRoleAllowed($value, $constraint))
            $this->isCommonRolesAllowed($value, $constraint);
    }

    public function isRoleAllowed($value, Constraint $constraint)
    {
        if ($diff = array_diff($value, array_keys($constraint->values))) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ roles }}', json_encode($diff))
                ->addViolation();
            return false;
        }
        return true;
    }

    public function isCommonRolesAllowed($value, Constraint $constraint)
    {
        foreach ($value as $role) {
            if (!array_key_exists($role, $constraint->values) || array_diff($value, array_merge([$role], $constraint->values[$role]))) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ role }}', json_encode($value))
                    ->addViolation();
                return false;
            }
        }
        return true;
    }
}
