<?php

namespace Maba\Bundle\WebpackBundle\ErrorHandler;

use Exception;
use Psr\Log\LoggerInterface;

class SuppressingErrorHandler implements ErrorHandlerInterface
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function processException(Exception $exception)
    {
        $this->logger->error((string)$exception);
    }
}
