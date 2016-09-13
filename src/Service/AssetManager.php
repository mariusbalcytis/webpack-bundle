<?php

namespace Maba\Bundle\WebpackBundle\Service;

class AssetManager
{
    const TYPE_JS = 'js';
    const TYPE_CSS = 'css';

    private $manifestStorage;
    private $manifest = null;
    private $assetNameGenerator;
    private $entryFileManager;

    public function __construct(
        ManifestStorage $manifestStorage,
        AssetNameGenerator $assetNameGenerator,
        EntryFileManager $entryFileManager
    ) {
        $this->manifestStorage = $manifestStorage;
        $this->assetNameGenerator = $assetNameGenerator;
        $this->entryFileManager = $entryFileManager;
    }

    public function getAssetUrl($asset, $type = null)
    {
        $manifest = $this->getManifest();
        $assetName = $this->assetNameGenerator->generateName($asset);
        if (!isset($manifest[$assetName])) {
            throw new \RuntimeException(sprintf(
                'No information in manifest for %s (key %s). %s',
                $asset,
                $assetName,
                'Is maba:webpack:dev-server running in the background?'
            ));
        }

        if ($type === null) {
            $entryFileType = $this->entryFileManager->getEntryFileType($asset);
            $type = $entryFileType !== null ? $entryFileType : self::TYPE_JS;
            if (!isset($manifest[$assetName][$type])) {
                throw new \RuntimeException(sprintf(
                    'No information in the manifest for type %s (key %s, asset %s). %s',
                    $type,
                    $assetName,
                    $asset,
                    'Probably extension is unsupported or some misconfiguration issue. '
                        . 'If this file should compile to javascript, please extend '
                        . 'entry_file.disabled_extensions in config.yml'
                ));
            }
        }

        return isset($manifest[$assetName][$type]) ? $manifest[$assetName][$type] : null;
    }

    protected function getManifest()
    {
        if ($this->manifest === null) {
            $this->manifest = $this->manifestStorage->getManifest();
        }
        return $this->manifest;
    }
}
