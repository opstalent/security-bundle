<?php

namespace Opstalent\SecurityBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Role\RoleInterface;

class KernelControllerSubscriber implements EventSubscriberInterface
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
            KernelEvents::CONTROLLER => [
                ['onRouteMatch', 9999]
            ]
        ];
    }

    public function onRouteMatch(FilterControllerEvent $event)
    {
        /** @var Request $request */
        $request = $event->getRequest();
        $route = $this->router->getRouteCollection()->get($request->attributes->get('_route'));
        if ($route && is_array($options = $route->getOption('security')) && array_key_exists('secure', $options) && $options['secure']) {
            if (!$this->canAccess($options)) {
                throw new \Exception("Forbidden", 403);
            }
        }
    }

    protected function canAccess(array $options):bool
    {

        if (!array_key_exists('roles', $options) ||
            empty($options['roles'])
        ) {
            return true;
        }

        if (
            $this->tokenStorage->getToken()
        ) {
            return !empty(array_intersect($options['roles'], array_map([$this, "getRole"], $this->tokenStorage->getToken()->getRoles())));
        }

        return false;
    }

    public function getRole(RoleInterface $value)
    {
        return $value->getRole();
    }
}