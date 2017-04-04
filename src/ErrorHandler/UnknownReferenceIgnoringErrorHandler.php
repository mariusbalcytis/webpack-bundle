<?php

namespace Maba\Bundle\WebpackBundle\ErrorHandler;

use Exception;
use Maba\Bundle\WebpackBundle\Exception\ResourceParsingException;
use Psr\Log\LoggerInterface;
use Twig_Error_Syntax as SyntaxError;

/**
 * This class ignores twig syntax exceptions that designate "Unknowns":
 * - unknown function;
 * - unknown filter;
 * - unknown tag;
 * - unknown test.
 *
 * It still rethrows the exception if asset path is incorrect or any other twig syntax error is found.
 *
 * This is used in production environment by default, as there might be some twig templates that use
 * functions, filters etc. defined in bundles, loaded only in dev environment (like WebProfilerBundle).
 */
class UnknownReferenceIgnoringErrorHandler implements ErrorHandlerInterface
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function processException(Exception $exception)
    {
        if (!$exception instanceof ResourceParsingException) {
            throw $exception;
        }

        $innerException = $exception->getPrevious();
        if (
            $innerException instanceof SyntaxError
            && (
                strpos($innerException->getMessage(), 'Unknown ') === 0
                || strpos($innerException->getMessage(), ' does not exist') !== false
            )
        ) {
            $this->logger->warning((string)$innerException);
        } else {
            throw $exception;
        }
    }
}
