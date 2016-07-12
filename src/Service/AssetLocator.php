<?php

namespace Maba\Bundle\WebpackBundle\Service;

use Maba\Bundle\WebpackBundle\Exception\AssetNotFoundException;
use RuntimeException;

class AssetLocator
{
    private $aliasManager;

    public function __construct(AliasManager $aliasManager)
    {
        $this->aliasManager = $aliasManager;
    }

    /**
     * @param string $asset path of an asset, possibly with alias prefix
     * @return string resolved asset path
     * @throws AssetNotFoundException if asset was not found
     */
    public function locateAsset($asset)
    {
        $position = strrpos($asset, '!');

        if ($position !== false) {
            $loader = substr($asset, 0, $position);
            $asset = substr($asset, $position + 1);
        }

        if (substr($asset, 0, 1) === '@') {
            $resolvedAsset = $this->resolveAlias($asset);
        } else {
            $resolvedAsset = $asset;
        }

        if (!file_exists($resolvedAsset)) {
            throw new AssetNotFoundException(sprintf('Asset not found (%s, resolved to %s)', $asset, $resolvedAsset));
        }

        return isset($loader) ? sprintf('%s!%s', $loader, $resolvedAsset) : $resolvedAsset;
    }

    private function resolveAlias($asset)
    {
        $position = mb_strpos($asset, '/');
        if ($position === false) {
            $position = mb_strlen($asset);
        }
        $alias = mb_substr($asset, 0, $position);
        try {
            $aliasPath = $this->aliasManager->getAliasPath($alias);
        } catch (RuntimeException $exception) {
            throw new AssetNotFoundException(
                sprintf('Cannot locate asset (%s) due to invalid alias (%s)', $asset, $alias),
                0,
                $exception
            );
        }
        return $aliasPath . mb_substr($asset, $position);
    }
}
