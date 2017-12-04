<?php

namespace Maba\Bundle\WebpackBundle\AssetProvider\DirectoryProvider;

use Symfony\Component\HttpKernel\KernelInterface;

class BundlesDirectoryProvider implements DirectoryProviderInterface
{
    private $kernel;
    private $relativeDirectory;
    private $bundles;

    /**
     * @param KernelInterface $kernel
     * @param string $relativeDirectory directory path relative to bundle. For example "/Resources/views"
     * @param array $bundles
     */
    public function __construct(KernelInterface $kernel, $relativeDirectory, array $bundles)
    {
        $this->kernel = $kernel;
        $this->relativeDirectory = $relativeDirectory;
        $this->bundles = $bundles;
    }

    public function getDirectories()
    {
        $directories = [];
        foreach ($this->bundles as $bundleName) {
            $bundle = $this->kernel->getBundle($bundleName);
            $directory = $bundle->getPath() . $this->relativeDirectory;
            if (file_exists($directory)) {
                $directories[$directory] = true;
            }
        }

        return array_keys($directories);
    }
}
