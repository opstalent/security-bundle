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
    const EVENT = "before.search.by.filter";
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
            self::EVENT => 'beforeSearchByFilter'
        ];
    }

    public function beforeSearchByFilter(RepositoryEvent $event)
    {
        $security = $this->getSecurity();
        if($security && array_key_exists('events',$security) && array_key_exists(self::EVENT,$security['events'])) {
            $callback = $security['events'][self::EVENT];
            $event->getRepository()->$callback($this->tokenStorage->getToken()->getUser());
        }
    }

    private function currentRoute(RouteCollection $routes, string $path, string $method):Route
    {
        foreach ($routes as $route)
        {
            if($route->getPath() === $path && $route->getMethods()[0] === $method) return $route;
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
}