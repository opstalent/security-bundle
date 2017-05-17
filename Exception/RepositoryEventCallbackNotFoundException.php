<?php

namespace Opstalent\SecurityBundle\Exception;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\SecurityBundle
 */
class RepositoryEventCallbackNotFoundException extends \UnexpectedValueException implements Exception
{
    /**
     * @param string $event
     */
    public function __construct(string $event)
    {
        parent::__construct(sprintf('Repository event callback for event "%s" not found', $event));
    }
}
