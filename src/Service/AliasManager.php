<?php

namespace Maba\Bundle\WebpackBundle\Service;

use Symfony\Component\Config\FileLocatorInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Container;
use RuntimeException;

class AliasManager
{
    private $fileLocator;
    private $registerBundles;
    private $pathInBundle;
    private $additionalAliases;

    /**
     * @var null|array
     */
    private $aliases = null;

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
        if ($this->aliases !== null) {
            return $this->aliases;
        }

        $aliases = [];
        foreach ($this->registerBundles as $bundleName) {
            $aliases['@' . $bundleName] = rtrim($this->fileLocator->locate('@' . $bundleName), '/');
            try {
                $shortName = $this->getShortNameForBundle($bundleName);
                $aliases['@' . $shortName] = $this->fileLocator->locate('@' . $bundleName . '/' . $this->pathInBundle);
            } catch (InvalidArgumentException $exception) {
                // ignore if directory not found, as all bundles are registered by default
            }
        }

        // give priority to additional to be able to overwrite bundle aliases
        foreach ($this->additionalAliases as $alias => $path) {
            $realPath = realpath($path);
            if ($realPath === false) {
                // just skip - allow non-existing aliases, like default ones
                unset($aliases['@' . $alias]);
                continue;
            }
            $aliases['@' . $alias] = $realPath;
        }

        $this->aliases = $aliases;

        return $aliases;
    }

    public function getAliasPath($alias)
    {
        $aliases = $this->getAliases();
        if (!isset($aliases[$alias])) {
            throw new RuntimeException(sprintf('Alias not registered: %s', $alias));
        }

        return $aliases[$alias];
    }

    private function getShortNameForBundle($bundleName)
    {
        $shortName = $bundleName;
        if (mb_substr($bundleName, -6) === 'Bundle') {
            $shortName = mb_substr($shortName, 0, -6);
        }
        // this is used by SensioGenerator bundle when generating extension name from bundle name
        return Container::underscore($shortName);
    }
}
