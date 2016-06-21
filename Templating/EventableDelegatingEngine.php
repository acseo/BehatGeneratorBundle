<?php

namespace ACSEO\Bundle\BehatGeneratorBundle\Templating;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine;
use ACSEO\Bundle\BehatGeneratorBundle\Templating\DelegatingEngineEvent;
use ACSEO\Bundle\BehatGeneratorBundle\Templating\DelegatingEngineEvents;
/**
 * @author Dmitry Korotovsky (dmitry@korotovsky.io)
 * @package ACSEO\Bundle\BehatGeneratorBundle
 */
class EventableDelegatingEngine extends DelegatingEngine
{
    /**
     * @param string $view
     * @param array $parameters
     * @param Response $response
     * @return Response|void
     */
    public function renderResponse($view, array $parameters = array(), Response $response = null)
    {
        $event = new DelegatingEngineEvent($view, $parameters, $response, $this->getRequest());
        $this->getEventDispatcher()->dispatch(DelegatingEngineEvents::PRE_RENDER, $event);
        return parent::renderResponse($view, $parameters, $response);
    }
    /**
     * @return EventDispatcherInterface
     */
    protected function getEventDispatcher()
    {
        return $this->container->get('event_dispatcher');
    }
    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    protected function getRequest()
    {
        return $this->container->get('request');
    }
}
