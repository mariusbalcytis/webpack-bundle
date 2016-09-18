<?php

namespace Maba\Bundle\WebpackBundle\Twig;

use Twig_Extension as Extension;
use Twig_SimpleFunction as SimpleFunction;
use Maba\Bundle\WebpackBundle\Service\AssetManager;

class WebpackExtension extends Extension
{
    const FUNCTION_NAME = 'webpack_asset';
    const TAG_NAME_STYLESHEETS = 'webpack_stylesheets';
    const TAG_NAME_JAVASCRIPTS = 'webpack_javascripts';
    const TAG_NAME_ASSETS = 'webpack_assets';

    protected $assetManager;
    protected $functionName;

    public function __construct(AssetManager $assetManager, $functionName = self::FUNCTION_NAME)
    {
        $this->assetManager = $assetManager;
        $this->functionName = $functionName;
    }

    public function getFunctions()
    {
        return array(
            new SimpleFunction($this->functionName, array($this, 'getAssetUrl')),
        );
    }

    public function getTokenParsers()
    {
        return array(
            new WebpackTokenParser(self::TAG_NAME_STYLESHEETS, $this->functionName, 'css'),
            new WebpackTokenParser(self::TAG_NAME_JAVASCRIPTS, $this->functionName, 'js'),
            new WebpackTokenParser(self::TAG_NAME_ASSETS, $this->functionName, null),
        );
    }

    public function getAssetUrl($resource, $type = null)
    {
        return $this->assetManager->getAssetUrl($resource, $type);
    }

    public function getName()
    {
        return 'maba_webpack';
    }
}
