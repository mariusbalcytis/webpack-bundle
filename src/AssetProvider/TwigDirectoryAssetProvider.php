<?php

namespace Maba\Bundle\WebpackBundle\AssetProvider;

use Maba\Bundle\WebpackBundle\AssetProvider\DirectoryProvider\DirectoryProviderInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class TwigDirectoryAssetProvider implements AssetProviderInterface
{
    private $twigAssetProvider;
    private $pattern;
    private $directoryProvider;

    public function __construct(
        TwigAssetProvider $twigAssetProvider,
        $pattern,
        DirectoryProviderInterface $directoryProvider
    ) {
        $this->twigAssetProvider = $twigAssetProvider;
        $this->pattern = $pattern;
        $this->directoryProvider = $directoryProvider;
    }

    public function getAssets($previousContext = null)
    {
        $resources = [];
        foreach ($this->directoryProvider->getDirectories() as $directory) {
            foreach ($this->createFinder($directory) as $file) {
                $resources[] = $file->getRealPath();
            }
        }

        $result = new AssetResult();
        $context = [];
        foreach ($resources as $fileName) {
            $assetResult = $this->twigAssetProvider->getAssets(
                $fileName,
                isset($previousContext[$fileName]) ? $previousContext[$fileName] : null
            );
            $context[$fileName] = $assetResult->getContext();
            $result->addAssets($assetResult->getAssets());
        }
        $result->setContext($context);

        return $result;
    }

    /**
     * @param string $resource
     * @return Finder|SplFileInfo[]
     */
    private function createFinder($resource)
    {
        if (!is_dir($resource)) {
            return [];
        }

        $finder = new Finder();
        $finder->in($resource)->followLinks()->files()->name($this->pattern);
        return $finder;
    }
}
