<?php

namespace Maba\Bundle\WebpackBundle\Service;

use Maba\Bundle\WebpackBundle\Exception\AssetNotFoundException;

class AssetResolver
{
    private $assetLocator;
    private $entryFileManager;

    public function __construct(
        AssetLocator $assetLocator,
        EntryFileManager $entryFileManager
    ) {
        $this->assetLocator = $assetLocator;
        $this->entryFileManager = $entryFileManager;
    }

    /**
     * @param string $asset
     * @return string
     * @throws AssetNotFoundException
     */
    public function resolveAsset($asset)
    {
        $assetParts = [];

        $position = strrpos($asset, '!');
        if ($position !== false) {
            $loader = substr($asset, 0, $position);
            $assetPath = substr($asset, $position + 1);
            $assetParts[] = $loader;
        } else {
            $assetPath = $asset;
        }

        $locatedAsset = $this->assetLocator->locateAsset($assetPath);
        $assetParts[] = $locatedAsset;

        $resolvedAsset = implode('!', $assetParts);

        if ($this->entryFileManager->isEntryFile($locatedAsset)) {
            $resolvedAsset = 'extract-file-loader?q=' . rawurlencode($resolvedAsset) . '!';
        }

        return $resolvedAsset;
    }
}
