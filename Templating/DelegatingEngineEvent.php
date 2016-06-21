<?php
namespace ACSEO\Bundle\BehatGeneratorBundle\Templating;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DelegatingEngineEvent
 * @author Dmitry Korotovsky (dmitry@korotovsky.io)
 * @package ACSEO\Bundle\BehatGeneratorBundle
 */
class DelegatingEngineEvent extends Event
{
    protected $view;
    protected $parameters = [];
    protected $response;
    protected $request;
    /**
     * @param $view
     * @param array $parameters
     * @param Response $response
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function __construct(&$view, array &$parameters = array(), Response $response = null, Request $request = null)
    {
        $this->view = $view;
        $this->parameters = $parameters;
        $this->response = $response;
        $this->request = $request;
    }
    /**
     * @return string
     */
    public function getView()
    {
        return $this->view;
    }
    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }
    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }
    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}
