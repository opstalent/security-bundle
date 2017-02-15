<?php


namespace Opstalent\SecurityBundle\EventListener;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;


class RouteMatchListener
{
    protected $router;
    protected $tokenStorage;

    public function __construct(Router $router, TokenStorage $tokenStorage) // this is @service_container
    {
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
    }

    public function onKernelRequest(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        $route = $this->router->getRouteCollection()->get($request->attributes->get('_route'));
        if($route && is_array($options = $route->getOption('security'))) {
            if(!$this->canAccess($options)) {
                throw new \Exception("Forbidden",403)  ;
            }
        }
    }

    protected function canAccess(array $options):bool
    {
        if(array_key_exists('roles', $options) && !empty($options['roles']) > 0 && $this->tokenStorage->getToken() && $user = $this->tokenStorage->getToken()->getUser()) {
            return !empty(array_intersect($options['roles'],$user->getRoles()));
        }

    }

}
