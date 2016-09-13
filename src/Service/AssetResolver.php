<?php

namespace Maba\Bundle\WebpackBundle\Service;

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

    public function resolveAsset($asset)
    {
        $assetParts = array();

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
            $resolvedAsset = 'extract-file?q=' . urlencode($resolvedAsset) . '!';
        }

        return $resolvedAsset;
    }
}
