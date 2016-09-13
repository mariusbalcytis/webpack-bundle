<?php

namespace Maba\Bundle\WebpackBundle\Service;

class EntryFileManager
{
    private $enabledExtensions;
    private $disabledExtensions;
    private $typeMap;

    /**
     * @param array $enabledExtensions
     * @param array $disabledExtensions
     * @param array $typeMap
     */
    public function __construct(array $enabledExtensions, array $disabledExtensions, array $typeMap)
    {
        $this->enabledExtensions = $enabledExtensions;
        $this->disabledExtensions = $disabledExtensions;
        $this->typeMap = $typeMap;
    }

    public function getEntryFileType($asset)
    {
        $assetPath = $this->removeLoaders($asset);
        $extension = strtolower(pathinfo($assetPath, PATHINFO_EXTENSION));
        if ($this->isExtensionIncluded($extension)) {
            return $this->mapExtension($extension);
        }
        return null;
    }

    public function isEntryFile($asset)
    {
        return $this->getEntryFileType($asset) !== null;
    }

    private function removeLoaders($asset)
    {
        $position = strrpos($asset, '!');
        return $position === false ? $asset : substr($asset, $position + 1);
    }

    private function isExtensionIncluded($extension)
    {
        if (count($this->enabledExtensions) === 0) {
            return count($this->disabledExtensions) > 0 && !in_array($extension, $this->disabledExtensions, true);
        } else {
            return in_array($extension, $this->enabledExtensions, true);
        }
    }

    private function mapExtension($extension)
    {
        foreach ($this->typeMap as $mappedExtension => $fromExtensions) {
            if (in_array($extension, $fromExtensions, true)) {
                return $mappedExtension;
            }
        }

        return $extension;
    }
}
