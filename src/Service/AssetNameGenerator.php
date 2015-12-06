<?php

namespace Maba\Bundle\WebpackBundle\Service;

class AssetNameGenerator
{

    public function generateName($asset)
    {
        return sha1($asset);
    }
}
