<?php

namespace Maba\Bundle\WebpackBundle\Service;

use Maba\Bundle\WebpackBundle\AssetProvider\AssetItem;
use Maba\Bundle\WebpackBundle\AssetProvider\AssetProviderInterface;
use Maba\Bundle\WebpackBundle\AssetProvider\AssetResult;
use Maba\Bundle\WebpackBundle\ErrorHandler\ErrorHandlerInterface;
use Maba\Bundle\WebpackBundle\Exception\ResourceParsingException;

class AssetCollector
{
    private $assetProvider;
    private $config;
    private $errorHandler;

    public function __construct(
        AssetProviderInterface $assetProvider,
        array $config,
        ErrorHandlerInterface $errorHandler
    ) {
        $this->assetProvider = $assetProvider;
        $this->config = $config;
        $this->errorHandler = $errorHandler;
    }

    /**
     * @param null|mixed $previousContext
     * @return AssetResult
     */
    public function getAssets($previousContext = null)
    {
        $assetResult = $this->assetProvider->getAssets($this->config, $previousContext);

        $groupedAssets = array();
        foreach ($assetResult->getAssets() as $asset) {
            if (isset($groupedAssets[$asset->getResource()])) {
                $this->checkSameGroup($groupedAssets[$asset->getResource()], $asset);
                continue;
            }

            $groupedAssets[$asset->getResource()] = $asset;
        }

        $assetResult->setAssets(array_values($groupedAssets));

        return $assetResult;
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
}
