<?php

namespace Maba\Bundle\WebpackBundle\AssetProvider;

use Maba\Bundle\WebpackBundle\ErrorHandler\ErrorHandlerInterface;
use Maba\Bundle\WebpackBundle\Exception\InvalidContextException;
use Maba\Bundle\WebpackBundle\Exception\InvalidResourceException;
use Maba\Bundle\WebpackBundle\Exception\ResourceParsingException;
use Twig_Environment as Environment;
use Twig_Error_Syntax as SyntaxException;
use Twig_Node as Node;
use Twig_Source as Source;
use Twig_Node_Expression_Constant as ConstantFunction;
use Twig_Node_Expression_Function as ExpressionFunction;

class TwigAssetProvider implements AssetProviderInterface
{
    private $twig;
    private $functionName;
    private $errorHandler;

    public function __construct(
        Environment $twig,
        $functionName,
        ErrorHandlerInterface $errorHandler
    ) {
        $this->twig = $twig;
        $this->functionName = $functionName;
        $this->errorHandler = $errorHandler;
    }

    public function getAssets($resource, $previousContext = null)
    {
        if (!is_string($resource)) {
            throw new InvalidResourceException('Expected string filename as resource', $resource);
        } elseif (!is_file($resource) || !is_readable($resource) || !stream_is_local($resource)) {
            throw new InvalidResourceException('File not found, not readable or not local', $resource);
        }

        if ($previousContext !== null) {
            if (
                !is_array($previousContext)
                || !isset($previousContext['modified_at'])
                || !is_int($previousContext['modified_at'])
                || !isset($previousContext['assets'])
                || !is_array($previousContext['assets'])
            ) {
                throw new InvalidContextException(
                    'Expected context with int `modified_at` and array `assets`',
                    $previousContext
                );
            }

            if ($previousContext['modified_at'] === filemtime($resource)) {
                $assetResult = new AssetResult();
                $assetResult->setAssets($previousContext['assets']);
                $assetResult->setContext($previousContext);
                return $assetResult;
            }
        }

        try {
            $tokens = $this->twig->tokenize(new Source(file_get_contents($resource), $resource));
            $node = $this->twig->parse($tokens);
        } catch (SyntaxException $exception) {
            $this->errorHandler->processException(
                new ResourceParsingException('Got twig syntax exception while parsing', 0, $exception)
            );
            return new AssetResult();
        }

        $assets = $this->loadNode($node, $resource);

        $assetResult = new AssetResult();
        $assetResult->setAssets($assets);
        $assetResult->setContext(array('modified_at' => filemtime($resource), 'assets' => $assets));
        return $assetResult;
    }

    private function loadNode(Node $node, $resource)
    {
        if ($this->isFunctionNode($node)) {
            /** @var ExpressionFunction $node */
            return $this->parseFunctionNode($node, sprintf('File %s, line %s', $resource, $node->getTemplateLine()));
        }

        $assets = array();
        foreach ($node as $child) {
            if ($child instanceof Node) {
                $assets = array_merge($assets, $this->loadNode($child, $resource));
            }
        }

        return $assets;
    }

    private function isFunctionNode(Node $node)
    {
        if ($node instanceof ExpressionFunction) {
            return $node->getAttribute('name') === $this->functionName;
        }

        return false;
    }

    private function parseFunctionNode(ExpressionFunction $functionNode, $context)
    {
        $arguments = iterator_to_array($functionNode->getNode('arguments'));
        if (!is_array($arguments)) {
            throw new ResourceParsingException('arguments is not an array');
        }

        if (count($arguments) < 1 || count($arguments) > 3) {
            throw new ResourceParsingException(sprintf(
                'Expected one to three arguments passed to function %s. %s',
                $this->functionName,
                $context
            ));
        }

        $asset = new AssetItem();

        $resourceArgument = isset($arguments[0]) ? $arguments[0] : $arguments['resource'];
        $asset->setResource($this->getArgumentValue($resourceArgument, $context));

        $groupArgument = null;
        if (isset($arguments[2])) {
            $groupArgument = $arguments[2];
        } elseif (isset($arguments['group'])) {
            $groupArgument = $arguments['group'];
        }

        if ($groupArgument !== null) {
            $asset->setGroup($this->getArgumentValue($groupArgument, $context));
        }

        return array($asset);
    }

    private function getArgumentValue(Node $argument, $context)
    {
        if (!$argument instanceof ConstantFunction) {
            throw new ResourceParsingException(sprintf(
                'Argument passed to function %s must be text node to parse without context. %s',
                $this->functionName,
                $context
            ));
        }
        return $argument->getAttribute('value');
    }
}
