<?php

namespace Maba\Bundle\WebpackBundle\Service;

class AssetNameGenerator
{
    public function generateName($asset)
    {
        return sprintf('%s-%s', pathinfo($asset, PATHINFO_FILENAME), md5($asset));
    }
}
