<?php

namespace Opstalent\SecurityBundle\EventSubscriber;

use Opstalent\ApiBundle\Event\RepositoryEvents;
use Opstalent\ApiBundle\Event\RepositoryEvent;
use Opstalent\ApiBundle\Event\RepositorySearchEvent;
use Opstalent\ApiBundle\Repository\RepositoryInterface;
use Opstalent\SecurityBundle\Exception\RepositoryEventCallbackNotFoundException;
use Symfony\Component\EventDispatcher\Event;
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
        try {
            $callback = $this->getCallback(RepositoryEvents::BEFORE_SEARCH_BY_FILTER);
        } catch (RepositoryEventCallbackNotFoundException $e) {
            return;
        }

        $this->call($event->getRepository(), $callback, [
            $this->tokenStorage->getToken()->getUser(),
            $event,
        ]);
    }

    public function kernelControllerEventListener(KernelEvent $event)
    {
        try {
            $callback = $this->getCallback(KernelEvents::CONTROLLER);
        } catch (RepositoryEventCallbackNotFoundException $e) {
            return;
        }

        $this->call($this->resolveRepositoryFromRoute(), $callback, [
            $this->requestStack->getMasterRequest()->attributes->get('entity'),
            $this->tokenStorage->getToken()->getUser()
        ]);
    }

    public function unitOfWorkEventListener(RepositoryEvent $event)
    {
        try {
            $callback = $this->getCallback($event->getName());
        } catch (RepositoryEventCallbackNotFoundException $e) {
            return;
        }

        $this->call($event->getRepository(), $callback, [
            $event->getData(),
            $this->tokenStorage->getToken()->getUser()
        ]);
    }

    /**
     * @param RepositoryInterface $repository
     * @param array $callback
     * @param array $params
     */
    private function call(RepositoryInterface $repository, array $callback, array $params)
    {
        foreach ($callback as $method) {
            call_user_func_array([$repository, $method], $params);
        }
    }

    /**
     * @param string $event
     * @return array
     * @throws RepositoryEventCallbackNotFoundException
     */
    private function getCallback(string $event) : array
    {
        $security = $this->getSecurity();
        if (
            !$security
            || !array_key_exists('events', $security)
            || !array_key_exists($event, $security['events'])
        ) {
            throw new RepositoryEventCallbackNotFoundException($event);
        }

        $callback = $security['events'][$event];
        if (!is_array($callback)) {
            $callback = [$callback];
        }

        return $callback;
    }


    private function getSecurity()
    {
        $route = $this->getRoute();
        return $route->getOption('security');
    }

    /**
     * @return RepositoryInterface
     *
     * @TODO: move method to repository resolver in ApiBundle
     */
    private function resolveRepositoryFromRoute() : RepositoryInterface
    {
        $route = $this->getRoute();
        $serviceName = $route->getOption('repository');

        return $this->container->get(substr($serviceName, 1));
    }


    /**
     * @return Route
     */
    private function getRoute() : Route
    {
        return $this->router->getRouteCollection()->get($this->requestStack->getMasterRequest()->attributes->get("_route"));
    }
}
