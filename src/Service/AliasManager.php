<?php

namespace Maba\Bundle\WebpackBundle\Service;

use Symfony\Component\Config\FileLocatorInterface;
use InvalidArgumentException;

class AliasManager
{
    private $fileLocator;
    private $registerBundles;
    private $pathInBundle;
    private $additionalAliases;

    /**
     * @param FileLocatorInterface $fileLocator
     * @param array $registerBundles
     * @param string $pathInBundle
     * @param array $additionalAliases
     */
    public function __construct(
        FileLocatorInterface $fileLocator,
        array $registerBundles,
        $pathInBundle,
        array $additionalAliases
    ) {
        $this->fileLocator = $fileLocator;
        $this->registerBundles = $registerBundles;
        $this->pathInBundle = $pathInBundle;
        $this->additionalAliases = $additionalAliases;
    }

    public function getAliases()
    {
        $aliases = array();
        foreach ($this->registerBundles as $bundleName) {
            try {
                $aliases['@' . $bundleName] = $this->fileLocator->locate('@' . $bundleName . '/' . $this->pathInBundle);
            } catch (InvalidArgumentException $exception) {
                // ignore if directory not found, as all bundles are registered by default
            }
        }

        // give priority to additional to be able to overwrite bundle aliases
        return $this->additionalAliases + $aliases;
    }
}
