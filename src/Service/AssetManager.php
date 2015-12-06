<?php

namespace Maba\Bundle\WebpackBundle\Service;

class AssetManager
{
    const TYPE_JS = 'js';
    const TYPE_CSS = 'css';

    private $manifestStorage;
    private $manifest = null;
    private $assetNameGenerator;

    public function __construct(ManifestStorage $manifestStorage, AssetNameGenerator $assetNameGenerator)
    {
        $this->manifestStorage = $manifestStorage;
        $this->assetNameGenerator = $assetNameGenerator;
    }

    public function getAssetUrl($asset, $type)
    {
        $manifest = $this->getManifest();
        $assetName = $this->assetNameGenerator->generateName($asset);
        if (!isset($manifest[$assetName])) {
            throw new \RuntimeException('No information in manifest for ' . $asset . ' (key ' . $assetName . ')');
        }
        return isset($manifest[$assetName][$type]) ? $manifest[$assetName][$type] : null;
    }

    protected function getManifest()
    {
        if ($this->manifest === null) {
            $this->manifest = $this->manifestStorage->getManifest();
        }
        return $this->manifest;
    }
}
