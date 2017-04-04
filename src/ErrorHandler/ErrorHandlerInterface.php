<?php

namespace Maba\Bundle\WebpackBundle\ErrorHandler;

use Exception;

/**
 * @api
 */
interface ErrorHandlerInterface
{
    public function processException(Exception $exception);
}
