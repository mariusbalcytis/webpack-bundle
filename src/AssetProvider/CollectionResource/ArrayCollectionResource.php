<?php

namespace Maba\Bundle\WebpackBundle\AssetProvider\CollectionResource;

use Maba\Bundle\WebpackBundle\Exception\InvalidResourceException;

class ArrayCollectionResource implements CollectionResourceInterface
{
    public function getInternalResources($resource)
    {
        if (!is_array($resource)) {
            throw new InvalidResourceException('Expected array as a resource', $resource);
        }

        return $resource;
    }
}
