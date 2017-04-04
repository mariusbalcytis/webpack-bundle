<?php

namespace Maba\Bundle\WebpackBundle\AssetProvider\DirectoryProvider;

interface DirectoryProviderInterface
{
    /**
     * @return array directories
     */
    public function getDirectories();
}
