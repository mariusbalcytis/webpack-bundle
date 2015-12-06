<?php

namespace Maba\Bundle\WebpackBundle\AssetProvider;

use Maba\Bundle\WebpackBundle\Exception\InvalidResourceException;

class DynamicAssetProvider implements AssetProviderInterface
{
    /**
     * @var AssetProviderInterface[]
     */
    protected $providers = array();

    /**
     * @param AssetProviderInterface $assetProvider
     * @param string $type
     */
    public function addProvider(AssetProviderInterface $assetProvider, $type)
    {
        $this->providers[$type] = $assetProvider;
    }

    public function getAssets($resource, $previousContext = null)
    {
        if (!is_array($resource) || !isset($resource['type']) || !isset($resource['resource'])) {
            throw new InvalidResourceException('`type` and `resource` expected', $resource);
        }

        $type = $resource['type'];
        $innerResource = $resource['resource'];

        if (!isset($this->providers[$type])) {
            throw new InvalidResourceException(
                sprintf('No asset provider with type "%s" registered', $type),
                $resource
            );
        }

        return $this->providers[$type]->getAssets($innerResource, $previousContext);
    }
}
