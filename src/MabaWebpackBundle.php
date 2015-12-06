<?php

namespace Maba\Bundle\WebpackBundle;

use Maba\Component\DependencyInjection\AddTaggedCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MabaWebpackBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AddTaggedCompilerPass(
            'maba_webpack.asset_provider.dynamic',
            'maba_webpack.asset_provider',
            'addProvider',
            array('type')
        ));
    }
}
