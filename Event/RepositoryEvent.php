<?php

namespace Opstalent\SecurityBundle\Event;

use Opstalent\ApiBundle\Repository\BaseRepository;
use Symfony\Component\EventDispatcher\Event;

class RepositoryEvent extends Event
{
    protected $repository;

    public function __construct(string $name, BaseRepository &$repository)
    {
        $this->repository = $repository;
    }

    public function getRepository()
    {
        return $this->repository;
    }


}