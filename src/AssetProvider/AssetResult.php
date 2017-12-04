<?php

namespace Maba\Bundle\WebpackBundle\AssetProvider;

/**
 * @api
 */
class AssetResult
{
    /**
     * @var AssetItem[]
     */
    private $assets = [];

    /**
     * @var mixed
     */
    private $context;

    /**
     * @return AssetItem[]
     */
    public function getAssets()
    {
        return $this->assets;
    }

    /**
     * @param array|AssetItem[] $assets
     * @return AssetResult
     */
    public function setAssets(array $assets)
    {
        $this->assets = $assets;
        return $this;
    }

    /**
     * @param AssetItem $asset
     * @return $this
     */
    public function addAsset(AssetItem $asset)
    {
        $this->assets[] = $asset;
        return $this;
    }

    /**
     * @param array|AssetItem[] $assets array of strings
     * @return $this
     */
    public function addAssets(array $assets)
    {
        $this->assets = array_merge($this->assets, $assets);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param mixed $context
     * @return AssetResult
     */
    public function setContext($context)
    {
        $this->context = $context;
        return $this;
    }
}
