<?php

namespace Maba\Bundle\WebpackBundle\Exception;

use Exception;
use RuntimeException;

class InvalidContextException extends RuntimeException
{
    /**
     * @var mixed
     */
    protected $context;

    /**
     * @param string $message
     * @param mixed $context
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($message, $context, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * @return mixed
     */
    public function getContext()
    {
        return $this->context;
    }
}
