<?php

namespace Maba\Bundle\WebpackBundle\Service;

use Maba\Bundle\WebpackBundle\AssetProvider\AssetProviderInterface;
use Maba\Bundle\WebpackBundle\AssetProvider\AssetResult;

class AssetCollector
{
    private $assetProvider;
    private $config;

    public function __construct(AssetProviderInterface $assetProvider, array $config)
    {
        $this->assetProvider = $assetProvider;
        $this->config = $config;
    }

    /**
     * @param null|mixed $previousContext
     * @return AssetResult
     */
    public function getAssets($previousContext = null)
    {
        return $this->assetProvider->getAssets($this->config, $previousContext);
    }
}
