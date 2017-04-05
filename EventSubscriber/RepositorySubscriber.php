<?php

namespace Opstalent\SecurityBundle\EventSubscriber;

use Opstalent\ApiBundle\Repository\BaseRepository;
use Opstalent\SecurityBundle\Event\RepositoryEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

class RepositorySubscriber implements EventSubscriberInterface
{
    const BEFORE_SEARCH_BY_FILTER = "before.search.by.filter";
    const BEFORE_PERSIST = "before.persist";

    protected $router;
    protected $tokenStorage;

    public function __construct(Router $router, TokenStorage $tokenStorage) // this is @service_container
    {
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
    }


    public static function getSubscribedEvents()
    {
        return [
            self::BEFORE_SEARCH_BY_FILTER => 'beforeSearchByFilter',
            self::BEFORE_PERSIST => 'beforePersist',
        ];
    }

    public function beforeSearchByFilter(RepositoryEvent $event)
    {
        $security = $this->getSecurity();
        if($security && array_key_exists('events',$security) && array_key_exists(self::BEFORE_SEARCH_BY_FILTER,$security['events'])) {
            $callback = $security['events'][self::BEFORE_SEARCH_BY_FILTER];
            $event->getRepository()->$callback($this->tokenStorage->getToken()->getUser());
        }
    }

    public function beforePersist(RepositoryEvent $event)
    {
        $security = $this->getSecurity();
        if($security && array_key_exists('events',$security) && array_key_exists(self::BEFORE_PERSIST,$security['events'])) {
            $callback = $security['events'][self::BEFORE_PERSIST];
            $event->getRepository()->$callback($event->getData(), $this->tokenStorage->getToken()->getUser());
        }
    }


    private function currentRoute(RouteCollection $routes, string $path, string $method):Route
    {
        foreach ($routes as $route)
        {
            if($route->getPath() === $this->processPath($path) && $route->getMethods()[0] === $method) return $route;
        }
        return $routes->get('root');
    }

    private function getSecurity()
    {
        $path = $this->router->getMatcher()->getContext()->getPathInfo();
        $method = $this->router->getMatcher()->getContext()->getMethod();
        $route = $this->currentRoute($this->router->getRouteCollection(), $path, $method);
        return $route->getOption('security');
    }

    private function processPath($path)
    {
        $parts = explode("/", $path);
        if (intval(end($parts)) != 0) {
            return str_replace(end($parts),"{id}", $path);
        }
        return "/";
    }
}