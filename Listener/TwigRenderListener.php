<?php
namespace ACSEO\Bundle\BehatGeneratorBundle\Listener;

use ACSEO\Bundle\BehatGeneratorBundle\Templating\DelegatingEngineEvents;
use ACSEO\Bundle\BehatGeneratorBundle\Templating\DelegatingEngineEvent;
use ACSEO\Bundle\BehatGeneratorBundle\Templating\EventableDelegatingEngine;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
/**
 * Class CounterSubscriber
 * @author Dmitry Korotovsky (dmitry@korotovsky.io)
 * @package Acme\Bundle\AcmeBundle\Listener
 */
class TwigRenderListener implements EventSubscriberInterface
{

    private $templateParams;

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return [
            DelegatingEngineEvents::PRE_RENDER => 'onPreRender'
        ];
    }
    /**
     * @param DelegatingEngineEvent $event
     * @return void
     */
    public function onPreRender(DelegatingEngineEvent $event)
    {
        $this->templateParams = $event->getParameters();
        // Do anything with filan set of template parameters
    }

    public function getLastTemplateParams()
    {
        return $this->templateParams;
    }
}
