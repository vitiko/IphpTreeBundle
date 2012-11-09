<?php

namespace Iphp\TreeBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Iphp\TreeBundle\DependencyInjection\Compiler\FormPass;

class IphpTreeBundle extends Bundle
{


    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new FormPass());
    }

}
