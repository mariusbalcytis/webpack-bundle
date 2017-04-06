<?php

namespace Maba\Bundle\WebpackBundle\Service;

use RuntimeException;

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

    /**
     * Gets URL for specified asset (usually provided to webpack_asset twig function).
     * If type not specified, it is guessed.
     *
     * Exception is thrown if manifest does not exit, asset is not in the manifest or
     *      type is not provided and cannot be guessed.
     *
     * @param string $asset
     * @param string|null $type specifies type in manifest, usually "js" or "css"
     *
     * @return string|null null is returned if type is provided and missing in manifest
     *
     * @throws RuntimeException
     *
     * @api
     */
    public function getAssetUrl($asset, $type = null)
    {
        $assetName = $this->assetNameGenerator->generateName($asset);

        $manifestEntry = $this->getManifestEntry($assetName, sprintf('%s (key %s)', $asset, $assetName));

        if ($type === null) {
            $type = $this->guessFileType($assetName, $asset, $manifestEntry);
        }

        return isset($manifestEntry[$type]) ? $manifestEntry[$type] : null;
    }

    /**
     * Gets URL for specified named asset - should be used for commons chunks.
     *
     * Exception is thrown if manifest does not exit or asset is not in the manifest.
     *
     * Type is not guessed as commons chunk only has a name and no path.
     *
     * @param string $assetName
     * @param string|null $type specifies type in manifest, usually "js" or "css"
     *
     * @return string|null null is returned if type is provided and missing in manifest
     *
     * @throws RuntimeException
     *
     * @api
     */
    public function getNamedAssetUrl($assetName, $type = null)
    {
        $manifestEntry = $this->getManifestEntry(
            $assetName,
            $assetName,
            'This is probably a commons chunk - is it configured by this name in webpack.config.js?'
        );

        if ($type === null) {
            $type = self::TYPE_JS;
        }

        return isset($manifestEntry[$type]) ? $manifestEntry[$type] : null;
    }

    private function getManifestEntry($assetName, $assetDescription, $additionalErrorInfo = '')
    {
        if ($this->manifest === null) {
            $this->manifest = $this->manifestStorage->getManifest();
        }

        if (!isset($this->manifest[$assetName])) {
            $additionalErrorInfo .= ' Is maba:webpack:dev-server running in the background?';
            throw new RuntimeException(sprintf(
                'No information in manifest for %s. %s',
                $assetDescription,
                trim($additionalErrorInfo)
            ));
        }

        return $this->manifest[$assetName];
    }

    private function guessFileType($assetName, $asset, $manifestEntry)
    {
        $entryFileType = $this->entryFileManager->getEntryFileType($asset);
        $type = $entryFileType !== null ? $entryFileType : self::TYPE_JS;
        if (!isset($manifestEntry[$type])) {
            throw new RuntimeException(sprintf(
                'No information in the manifest for type %s (key %s, asset %s). %s',
                $type,
                $assetName,
                $asset,
                'Probably extension is unsupported or some misconfiguration issue. '
                . 'If this file should compile to javascript, please extend '
                . 'entry_file.disabled_extensions in config.yml'
            ));
        }

        return $type;
    }
}
