<?php

namespace Maba\Bundle\WebpackBundle\Twig;

use Twig_Extension as Extension;
use Twig_SimpleFunction as SimpleFunction;
use Maba\Bundle\WebpackBundle\Service\AssetManager;

class WebpackExtension extends Extension
{
    protected $assetManager;
    protected $functionPrefix;

    public function __construct(AssetManager $assetManager, $functionPrefix = 'webpack_')
    {
        $this->assetManager = $assetManager;
        $this->functionPrefix = $functionPrefix;
    }

    public function getFunctions()
    {
        return array(
            new SimpleFunction($this->functionPrefix . 'asset', array($this, 'getAssetUrl')),
            new SimpleFunction($this->functionPrefix . 'file_asset', array($this, 'getFileAssetUrl')),
            new SimpleFunction($this->functionPrefix . 'css_asset', array($this, 'renderCssAsset'), array(
                'is_safe' => array('html'))
            ),
        );
    }

    public function getAssetUrl($resource, $type = AssetManager::TYPE_JS)
    {
        return $this->assetManager->getAssetUrl($resource, $type);
    }

    public function getFileAssetUrl($resource)
    {
        $urls = $this->assetManager->getAssetUrls('!!entry-file!' . $resource);

        return end($urls);
    }

    public function renderCssAsset($resource)
    {
        if (!$url = $this->assetManager->getAssetUrl($resource, AssetManager::TYPE_CSS)) {
            return '';
        }

        return sprintf('<link rel="stylesheet" href="%s"/>', $url);
    }

    public function getName()
    {
        return 'maba_webpack';
    }
}
