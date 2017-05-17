<?php

namespace Opstalent\SecurityBundle\EventSubscriber;

use Opstalent\ApiBundle\Event\RepositoryEvents;
use Opstalent\ApiBundle\Event\RepositoryEvent;
use Opstalent\ApiBundle\Event\RepositorySearchEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Routing\Route;

class RepositorySubscriber implements EventSubscriberInterface
{
    protected $router;
    protected $tokenStorage;
    protected $requestStack;
    protected $container;

    public function __construct(Router $router, TokenStorage $tokenStorage, RequestStack $requestStack, ContainerInterface $container) // this is @service_container
    {
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
        $this->requestStack = $requestStack;
        $this->container = $container;
    }


    public static function getSubscribedEvents()
    {
        return [
            RepositoryEvents::BEFORE_SEARCH_BY_FILTER => ['searchEventListener', 255],
            RepositoryEvents::BEFORE_PERSIST => ['unitOfWorkEventListener', 255],
            RepositoryEvents::AFTER_PERSIST => ['unitOfWorkEventListener', 255],
            RepositoryEvents::BEFORE_REMOVE => ['unitOfWorkEventListener', 255],
            KernelEvents::CONTROLLER => ['kernelControllerEventListener', -255],
        ];
    }

    public function searchEventListener(RepositorySearchEvent $event)
    {
        $security = $this->getSecurity();
        if (
            $security
            && array_key_exists('events', $security)
            && array_key_exists(RepositoryEvents::BEFORE_SEARCH_BY_FILTER, $security['events'])
        ) {
            $callback = $security['events'][RepositoryEvents::BEFORE_SEARCH_BY_FILTER];
            call_user_func(
                [$event->getRepository(), $callback],
                $this->tokenStorage->getToken()->getUser(),
                $event
            );
        }
    }

    public function kernelControllerEventListener(KernelEvent $event)
    {
        $security = $this->getSecurity();
        if ($security && array_key_exists('events', $security) && array_key_exists(KernelEvents::CONTROLLER, $security['events'])) {
            $callback = $security['events'][KernelEvents::CONTROLLER];
            call_user_func(
                [$this->container->get(substr($this->getOptionRepository(),1)), $callback],
                $this->requestStack->getMasterRequest()->attributes->get('entity'),
                $this->tokenStorage->getToken()->getUser()
            );
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

    private function getSecurity()
    {
        $route = $this->getRoute();
        return $route->getOption('security');
    }

    private function getOptionRepository()
    {
        $route = $this->getRoute();
        return $route->getOption('repository');
    }


    /**
     * @return Route
     */
    private function getRoute() : Route
    {
        return $this->router->getRouteCollection()->get($this->requestStack->getMasterRequest()->attributes->get("_route"));
    }
}
