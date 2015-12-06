<?php

namespace Maba\Bundle\WebpackBundle\AssetProvider;

use Maba\Bundle\WebpackBundle\Exception\InvalidContextException;
use Maba\Bundle\WebpackBundle\Exception\InvalidResourceException;

interface AssetProviderInterface
{
    /**
     * @param mixed $resource value must be json_encode'able (scalar|array)
     * @param mixed|null $previousContext
     * @return AssetResult
     *
     * @throws InvalidResourceException
     * @throws InvalidContextException
     */
    public function getAssets($resource, $previousContext = null);
}
