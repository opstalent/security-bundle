<?php

namespace Opstalent\SecurityBundle\Event;

use Opstalent\ApiBundle\Repository\BaseRepository;
use Symfony\Component\EventDispatcher\Event;

class RepositoryEvent extends Event
{
    protected $repository;
    protected $data;
    protected $params;

    public function __construct(string $name, BaseRepository &$repository, $data = null, $params=[])
    {
        $this->repository = $repository;
        $this->data = $data;
        $this->params = $params;
    }

    public function getRepository()
    {
        return $this->repository;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getParams()
    {
        return $this->params;
    }
}