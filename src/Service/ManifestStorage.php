<?php

namespace Maba\Bundle\WebpackBundle\Service;

use RuntimeException;

class ManifestStorage
{
    private $manifestPath;

    public function __construct($manifestPath)
    {
        $this->manifestPath = $manifestPath;
    }

    public function saveManifest(array $manifest)
    {
        file_put_contents($this->manifestPath, '<?php return ' . var_export($manifest, true) . ';');
    }

    public function getManifest()
    {
        if (!file_exists($this->manifestPath)) {
            throw new RuntimeException(
                'Manifest file not found: ' . $this->manifestPath
                . '. You must run compile command before twig can render webpack assets'
            );
        }
        return require $this->manifestPath;
    }
}
