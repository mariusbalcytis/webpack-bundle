<?php

namespace Maba\Bundle\WebpackBundle\AssetProvider;

use Maba\Bundle\WebpackBundle\Exception\InvalidContextException;
use Maba\Bundle\WebpackBundle\Exception\InvalidResourceException;

/**
 * @api
 */
interface AssetProviderInterface
{
    /**
     * @param mixed|null $previousContext
     * @return AssetResult
     *
     * @throws InvalidResourceException
     * @throws InvalidContextException
     */
    public function getAssets($previousContext = null);
}
