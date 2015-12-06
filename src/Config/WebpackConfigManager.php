<?php

namespace Maba\Bundle\WebpackBundle\Config;

use Maba\Bundle\WebpackBundle\ErrorHandler\ErrorHandlerInterface;
use Maba\Bundle\WebpackBundle\Service\AliasManager;
use Maba\Bundle\WebpackBundle\Service\AssetCollector;
use Maba\Bundle\WebpackBundle\Service\AssetNameGenerator;
use Symfony\Component\Config\FileLocatorInterface;
use InvalidArgumentException;

class WebpackConfigManager
{
    private $aliasManager;
    private $assetCollector;
    private $configDumper;
    private $fileLocator;
    private $assetNameGenerator;
    private $errorHandler;

    public function __construct(
        AliasManager $aliasManager,
        AssetCollector $assetCollector,
        WebpackConfigDumper $configDumper,
        FileLocatorInterface $fileLocator,
        AssetNameGenerator $assetNameGenerator,
        ErrorHandlerInterface $errorHandler
    ) {
        $this->aliasManager = $aliasManager;
        $this->assetCollector = $assetCollector;
        $this->configDumper = $configDumper;
        $this->fileLocator = $fileLocator;
        $this->assetNameGenerator = $assetNameGenerator;
        $this->errorHandler = $errorHandler;
    }

    /**
     * @param WebpackConfig $previousConfig
     * @return WebpackConfig
     */
    public function dump(WebpackConfig $previousConfig = null)
    {
        $aliases = $this->aliasManager->getAliases();
        $assetResult = $this->assetCollector->getAssets(
            $previousConfig !== null ? $previousConfig->getCacheContext() : null
        );
        $entryPoints = array();
        foreach ($assetResult->getAssets() as $asset) {
            $assetName = $this->assetNameGenerator->generateName($asset);
            try {
                $entryPoints[$assetName] = $this->fileLocator->locate($asset);
            } catch (InvalidArgumentException $exception) {
                $this->errorHandler->processException($exception);
            }
        }

        $config = new WebpackConfig();
        $config->setAliases($aliases);
        $config->setEntryPoints($entryPoints);
        $config->setCacheContext($assetResult->getContext());

        if (
            $previousConfig === null
            || $aliases !== $previousConfig->getAliases()
            || $entryPoints !== $previousConfig->getEntryPoints()
            || !file_exists($previousConfig->getConfigPath())
        ) {
            $config->setConfigPath($this->configDumper->dump($config));
            $config->setFileDumped(true);
        } else {
            $config->setConfigPath($previousConfig->getConfigPath());
        }

        return $config;
    }
}
