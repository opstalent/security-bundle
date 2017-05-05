<?php

namespace Opstalent\SecurityBundle\EventSubscriber;

use Opstalent\ApiBundle\Event\RepositoryEvents;
use Opstalent\ApiBundle\Repository\BaseRepository;
use Opstalent\ApiBundle\Event\RepositoryEvent;
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
            RepositoryEvents::BEFORE_SEARCH_BY_FILTER => ['searchEventListener', 255],
            RepositoryEvents::BEFORE_PERSIST => ['unitOfWorkEventListener', 255],
            RepositoryEvents::AFTER_PERSIST => ['unitOfWorkEventListener', 255],
            RepositoryEvents::BEFORE_REMOVE => ['unitOfWorkEventListener', 255],
        ];
    }

    public function searchEventListener(RepositoryEvent $event)
    {
        $security = $this->getSecurity();
        if (
            $security
            && array_key_exists('events', $security)
            && array_key_exists(RepositoryEvents::BEFORE_SEARCH_BY_FILTER, $security['events'])
        ) {
            $callback = $security['events'][RepositoryEvents::BEFORE_SEARCH_BY_FILTER];
            call_user_func([$event->getRepository(), $callback], $this->tokenStorage->getToken()->getUser());
        }
    }

    public function unitOfWorkEventListener(RepositoryEvent $event)
    {
        $security = $this->getSecurity();
        if ($security && array_key_exists('events', $security) && array_key_exists($event->getName(), $security['events'])) {
            $callback = $security['events'][$event->getName()];
            call_user_func(
                [$event->getRepository(), $callback],
                $event->getData(),
                $this->tokenStorage->getToken()->getUser()
            );
        }
    }

    private function currentRoute(RouteCollection $routes, string $path, string $method):Route
    {
        foreach ($routes as $route) {
            if ($route->getPath() === $this->processPath($path) && $route->getMethods() == [$method]) return $route;
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
        if (count($parts) > 1 && intval(end($parts)) != 0) {
            return str_replace(end($parts), "{id}", $path);
        }
        return $path;
    }
}
