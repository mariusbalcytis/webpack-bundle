<?php

namespace Maba\Bundle\WebpackBundle\ErrorHandler;

use Exception;

class DefaultErrorHandler implements ErrorHandlerInterface
{
    public function processException(Exception $exception)
    {
        throw $exception;
    }
}
