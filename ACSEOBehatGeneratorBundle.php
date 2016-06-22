<?php

namespace ACSEO\Bundle\BehatGeneratorBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use ACSEO\Bundle\BehatGeneratorBundle\DependencyInjection\Compiler\FirewallManagerCompilerPass;

class ACSEOBehatGeneratorBundle extends Bundle
{

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new FirewallManagerCompilerPass());
    }
}
