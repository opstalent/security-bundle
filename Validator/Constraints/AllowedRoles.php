<?php

namespace Opstalent\SecurityBundle\Validator\Constraints;

use Doctrine\Common\Annotations\Annotation;
use Symfony\Component\Validator\Constraint;

/**
 * @author Szymon Kunowski <szymon.kunowski@gmail.com>
 * @package Opstalent\Common
 *
 * @Annotation
 * @Annotation\Target(["PROPERTY"])
 */
class AllowedRoles extends Constraint
{
    /**
     * @var array
     *
     * @Annotation\Required
     */
    public $values;

    public $maxCommon = 1;

    /**
     * @var string
     */
    public $message = "Roles {{ roles }} not allowed.";

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return [
            static::PROPERTY_CONSTRAINT
        ];
    }
}