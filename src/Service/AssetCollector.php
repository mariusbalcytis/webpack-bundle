<?php

namespace Maba\Bundle\WebpackBundle\Service;

use ArrayObject;
use Maba\Bundle\WebpackBundle\AssetProvider\AssetItem;
use Maba\Bundle\WebpackBundle\AssetProvider\AssetProviderInterface;
use Maba\Bundle\WebpackBundle\AssetProvider\AssetResult;
use Maba\Bundle\WebpackBundle\ErrorHandler\ErrorHandlerInterface;
use Maba\Bundle\WebpackBundle\Exception\ResourceParsingException;

class AssetCollector
{
    /**
     * @var AssetProviderInterface[]
     */
    private $assetProviders = [];
    private $errorHandler;

    public function __construct(
        ErrorHandlerInterface $errorHandler
    ) {
        $this->errorHandler = $errorHandler;
    }

    /**
     * @param AssetProviderInterface $assetProvider
     */
    public function addAssetProvider(AssetProviderInterface $assetProvider)
    {
        $this->assetProviders[] = $assetProvider;
    }

    /**
     * @param null|mixed $previousContext
     * @return AssetResult
     */
    public function getAssets($previousContext = null)
    {
        $context = [];
        $groupedAssets = new ArrayObject();
        foreach ($this->assetProviders as $i => $assetProvider) {
            $assetResult = $assetProvider->getAssets(isset($previousContext[$i]) ? $previousContext[$i] : null);
            $context[$i] = $assetResult->getContext();
            $this->mergeAssets($groupedAssets, $assetResult->getAssets());
        }

        return $this->buildResult($groupedAssets, $context);
    }

    /**
     * @param ArrayObject $groupedAssets
     * @param AssetItem[] $assets
     */
    private function mergeAssets(ArrayObject $groupedAssets, $assets)
    {
        foreach ($assets as $asset) {
            if (isset($groupedAssets[$asset->getResource()])) {
                $this->checkSameGroup($groupedAssets[$asset->getResource()], $asset);
                continue;
            }

            $groupedAssets[$asset->getResource()] = $asset;
        }
    }

    private function checkSameGroup(AssetItem $assetOne, AssetItem $assetTwo)
    {
        if ($assetOne->getGroup() !== $assetTwo->getGroup()) {
            $this->errorHandler->processException(
                new ResourceParsingException(sprintf(
                    'Same assets must have same groups. Different groups (%s and %s) found for asset "%s"',
                    $assetOne->getGroup() === null ? 'none' : '"' . $assetOne->getGroup() . '"',
                    $assetTwo->getGroup() === null ? 'none' : '"' . $assetTwo->getGroup() . '"',
                    $assetOne->getResource()
                ))
            );
        }
    }

    private function buildResult(ArrayObject $groupedAssets, $context)
    {
        $result = new AssetResult();
        $result->setAssets(array_values((array)$groupedAssets));
        $result->setContext($context);
        return $result;
    }
}
