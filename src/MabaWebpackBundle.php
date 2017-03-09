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
            'maba_webpack.asset_collector',
            'maba_webpack.asset_provider',
            'addAssetProvider'
        ));
    }
}
