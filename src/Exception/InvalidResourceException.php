<?php

namespace Maba\Bundle\WebpackBundle\Exception;

use Exception;
use RuntimeException;

class InvalidResourceException extends RuntimeException
{
    /**
     * @var mixed
     */
    protected $resource;

    /**
     * @param string $message
     * @param mixed $resource
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($message, $resource, $code = 0, Exception $previous = null)
    {
        parent::__construct($message . '. Got ' . gettype($resource), $code, $previous);
        $this->resource = $resource;
    }

    /**
     * @return mixed
     */
    public function getResource()
    {
        return $this->resource;
    }
}
