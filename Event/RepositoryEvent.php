<?php

namespace Opstalent\SecurityBundle\Event;

use Opstalent\ApiBundle\Repository\BaseRepository;
use Symfony\Component\EventDispatcher\Event;

class RepositoryEvent extends Event
{
    protected $repository;
    protected $data;

    public function __construct(string $name, BaseRepository &$repository, $data = null)
    {
        $this->repository = $repository;
        $this->data = $data;
    }

    public function getRepository()
    {
        return $this->repository;
    }

    public function getData()
    {
        return $this->data;
    }
}
