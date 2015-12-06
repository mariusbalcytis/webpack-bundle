<?php

namespace Maba\Bundle\WebpackBundle\AssetProvider\CollectionResource;

use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\Exception\InvalidResourceException;

class BundlesDirectoriesResource implements CollectionResourceInterface
{
    protected $kernel;
    protected $relativeDirectory;

    /**
     * @param KernelInterface $kernel
     * @param string $relativeDirectory directory path relative to bundle. For example "/Resources/views"
     */
    public function __construct(KernelInterface $kernel, $relativeDirectory)
    {
        $this->kernel = $kernel;
        $this->relativeDirectory = $relativeDirectory;
    }

    public function getInternalResources($resource)
    {
        if (!is_array($resource)) {
            throw new InvalidResourceException('Expected array of bundle names as a resource', $resource);
        }

        $directories = array();
        foreach ($resource as $bundleName) {
            /** @var BundleInterface $bundle */
            $bundle = $this->kernel->getBundle($bundleName, true);
            $directory = $bundle->getPath() . $this->relativeDirectory;
            if (file_exists($directory)) {
                $directories[] = $directory;
            }
        }

        return $directories;
    }
}
