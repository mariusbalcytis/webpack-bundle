<?php

namespace Maba\Bundle\WebpackBundle\AssetProvider\CollectionResource;

use Maba\Bundle\WebpackBundle\Exception\InvalidResourceException;

/**
 * @api
 */
interface CollectionResourceInterface
{

    /**
     * @param mixed $resource
     * @return array of mixed
     *
     * @throws InvalidResourceException
     */
    public function getInternalResources($resource);
}
