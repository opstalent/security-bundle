<?php


namespace Opstalent\SecurityBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;


class RouteMatchListener
{

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $route = $this->get('router')->getRouteCollection()->get($request->attributes->get('_route'));
        dump($route->getOption('security'));

    }

}
