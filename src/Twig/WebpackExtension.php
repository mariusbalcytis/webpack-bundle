<?php

namespace Maba\Bundle\WebpackBundle\Twig;

use Twig_Extension as Extension;
use Twig_SimpleFunction as SimpleFunction;
use Maba\Bundle\WebpackBundle\Service\AssetManager;

class WebpackExtension extends Extension
{
    protected $assetManager;
    protected $functionName;

    public function __construct(AssetManager $assetManager, $functionName = 'webpack_asset')
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

    public function getAssetUrl($resource, $type = null)
    {
        return $this->assetManager->getAssetUrl($resource, $type);
    }

    public function getName()
    {
        return 'maba_webpack';
    }
}
