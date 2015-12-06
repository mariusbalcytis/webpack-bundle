<?php

namespace Maba\Bundle\WebpackBundle\AssetProvider;

class AssetResult
{
    /**
     * @var array keys are actual result - to avoid array_unique operations
     */
    protected $assets = array();

    /**
     * Context must be json_encode'able (scalar|array)
     *
     * @var mixed
     */
    protected $context;

    /**
     * @return array
     */
    public function getAssets()
    {
        return array_keys($this->assets);
    }

    /**
     * @param array $assets
     * @return AssetResult
     */
    public function setAssets(array $assets)
    {
        $this->assets = array_fill_keys($assets, true);
        return $this;
    }

    /**
     * @param string $asset
     * @return $this
     */
    public function addAsset($asset)
    {
        $this->assets[$asset] = true;
        return $this;
    }

    /**
     * @param array $assets array of strings
     * @return $this
     */
    public function addAssets(array $assets)
    {
        $this->assets = array_merge($this->assets, array_fill_keys($assets, true));
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
     * @param mixed $context value must be json_encode'able (scalar|array)
     * @return AssetResult
     */
    public function setContext($context)
    {
        $this->context = $context;
        return $this;
    }
}
