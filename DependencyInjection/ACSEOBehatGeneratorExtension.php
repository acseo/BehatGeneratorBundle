<?php

namespace ACSEO\Bundle\BehatGeneratorBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Container;
/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class ACSEOBehatGeneratorExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        // find templating engines in config
        $engines = array_map(function ($engine) { return new Reference('templating.engine.'.$engine); }, $container->getParameter('templating.engines'));
        // force use of templating.engine.delegating even if only one engine is defined
        $container->setDefinition('templating.engine.delegating',new Definition('%templating.engine.delegating.class%',array(new Reference('service_container'),$engines)));

        $container->setAlias('templating','templating.engine.delegating');

    }
}
