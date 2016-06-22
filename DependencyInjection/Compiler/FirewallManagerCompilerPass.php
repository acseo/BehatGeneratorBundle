<?php

namespace ACSEO\Bundle\BehatGeneratorBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class FirewallManagerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (false === $container->hasAlias('acseo.util.firewall_manager') && false === $container->hasDefinition('acseo.util.firewall_manager')) {
            return;
        }

        $map = $container->getDefinition('security.firewall.map');
        $maps = $map->getArgument(1);

        $refs = [];
        foreach ($maps as $serviceName => $firewall) {
            $refs[substr($serviceName, 30)] = $firewall;
        }

        $firewallManagerDef = $container->getDefinition('acseo.util.firewall_manager');
        $firewallManagerDef->replaceArgument(0, $refs);
    }
}
