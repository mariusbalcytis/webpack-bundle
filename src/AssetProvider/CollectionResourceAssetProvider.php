<?php

namespace Maba\Bundle\WebpackBundle\AssetProvider;

use Maba\Bundle\WebpackBundle\AssetProvider\CollectionResource\CollectionResourceInterface;
use Maba\Bundle\WebpackBundle\Exception\InvalidContextException;

class CollectionResourceAssetProvider implements AssetProviderInterface
{
    protected $collectionResource;
    protected $internalResourceProvider;

    public function __construct(
        CollectionResourceInterface $collectionResource,
        AssetProviderInterface $internalResourceProvider
    ) {
        $this->collectionResource = $collectionResource;
        $this->internalResourceProvider = $internalResourceProvider;
    }

    public function getAssets($resource, $previousContext = null)
    {
        $internalResources = array();
        foreach ($this->collectionResource->getInternalResources($resource) as $internalResource) {
            $internalResourceKey = json_encode($internalResource);
            $internalResources[$internalResourceKey] = $internalResource;
        }

        $result = new AssetResult();
        $context = array();

        $internalContexts = $this->getResourceContexts(array_keys($internalResources), $previousContext);
        foreach ($internalResources as $internalResourceKey => $internalResource) {
            $internalContext = isset($internalContexts[$internalResourceKey])
                ? $internalContexts[$internalResourceKey]
                : null;

            $internalResult = $this->internalResourceProvider->getAssets($internalResource, $internalContext);
            $result->addAssets($internalResult->getAssets());
            $context[$internalResourceKey] = $internalResult->getContext();
        }

        $result->setContext($context);

        return $result;
    }

    /**
     * @param array $internalResourceKeys
     * @param array|null $previousContext key => value pairs
     *                                    keys are resources and values are mixed depending on internal provider
     * @return array
     */
    protected function getResourceContexts(array $internalResourceKeys, $previousContext)
    {
        if ($previousContext === null) {
            return array();
        }

        if (!is_array($previousContext)) {
            throw new InvalidContextException('Context must be array', $previousContext);
        }

        // if at least one removed - we need to recalculate everything as some asset might be removed also
        $removedResources = array_diff(array_keys($previousContext), $internalResourceKeys);
        if (count($removedResources) > 0) {
            return array();
        }

        return $previousContext;
    }
}
