<?php

namespace Maba\Bundle\WebpackBundle\ErrorHandler;

use Exception;

interface ErrorHandlerInterface
{
    public function processException(Exception $exception);
}