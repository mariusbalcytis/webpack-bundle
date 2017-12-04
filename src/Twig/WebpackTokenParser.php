<?php

namespace Maba\Bundle\WebpackBundle\Twig;

use Maba\Bundle\WebpackBundle\Service\AssetManager;
use Twig_Token as Token;
use Twig_TokenParser as TokenParser;
use Twig_Node_Expression_Function as FunctionExpression;
use Twig_Node as Node;
use Twig_Node_If as IfNode;
use Twig_Node_Set as SetNode;
use Twig_Node_Expression_AssignName as AssignNameExpression;
use Twig_Node_Expression_Constant as ConstantExpression;
use Twig_Error_Syntax as SyntaxError;
use Twig_TokenStream as TokenStream;

class WebpackTokenParser extends TokenParser
{
    const TAG_NAME = 'webpack';

    private $functionName;
    private $namedAssetFunctionName;

    /**
     * @param string $functionName function name to call to get asset, usually webpack_asset
     * @param string $namedAssetFunctionName function name to call to get named asset, usually webpack_named_asset
     */
    public function __construct($functionName, $namedAssetFunctionName)
    {
        $this->functionName = $functionName;
        $this->namedAssetFunctionName = $namedAssetFunctionName;
    }

    public function parse(Token $token)
    {
        $stream = $this->parser->getStream();
        $parsedTag = new ParsedTag($stream);

        while (!$stream->test(Token::BLOCK_END_TYPE)) {
            $this->parseStream($stream, $parsedTag);
        }
        $stream->expect(Token::BLOCK_END_TYPE);

        $body = $this->parseBody($stream);

        $nodes = $this->createNodesForInputs($parsedTag, $body, $token->getLine());

        return new Node($nodes);
    }

    private function parseStream(TokenStream $stream, ParsedTag $parsedTag)
    {
        if ($stream->test(Token::STRING_TYPE)) {
            $parsedTag->addInput($stream->next()->getValue());
        } elseif ($stream->test(Token::NAME_TYPE, AssetManager::TYPE_JS)) {
            $stream->next();
            $parsedTag->setType(AssetManager::TYPE_JS);
        } elseif ($stream->test(Token::NAME_TYPE, AssetManager::TYPE_CSS)) {
            $stream->next();
            $parsedTag->setType(AssetManager::TYPE_CSS);
        } elseif ($stream->test(Token::NAME_TYPE, 'named')) {
            $stream->next();
            $parsedTag->markAsNamed();
        } elseif ($stream->test(Token::NAME_TYPE, 'group')) {
            $stream->next();
            $stream->expect(\Twig_Token::OPERATOR_TYPE, '=');
            $parsedTag->setGroup($stream->expect(\Twig_Token::STRING_TYPE)->getValue());
        } else {
            $token = $stream->getCurrent();
            /* @noinspection PhpInternalEntityUsedInspection */
            throw new SyntaxError(sprintf(
                'Unexpected token "%s" of value "%s"',
                Token::typeToEnglish($token->getType()),
                $token->getValue()
            ), $token->getLine(), $stream->getSourceContext());
        }
    }

    private function parseBody(TokenStream $stream)
    {
        $endTag = 'end_' . $this->getTag();
        $body = $this->parser->subparse(function (Token $token) use ($endTag) {
            return $token->test([$endTag]);
        }, true);
        $stream->expect(Token::BLOCK_END_TYPE);

        return $body;
    }

    private function createNodesForInputs(ParsedTag $parsedTag, Node $body, $lineNo)
    {
        $nodes = [];
        foreach ($parsedTag->getInputs() as $input) {
            $valueExpression = $this->createFunctionExpression($input, $parsedTag, $lineNo);
            $nodes[] = $this->createAssignAndCheckNode($valueExpression, $body, $lineNo);
        }

        return $nodes;
    }

    private function createFunctionExpression($input, ParsedTag $parsedTag, $lineNo)
    {
        $functionName = $parsedTag->isNamed() ? $this->namedAssetFunctionName : $this->functionName;

        /** @noinspection PhpParamsInspection */
        $arguments = [
            new ConstantExpression($input, $lineNo),
            new ConstantExpression($parsedTag->getType(), $lineNo),
        ];

        if ($parsedTag->getGroup() !== null) {
            /* @noinspection PhpParamsInspection */
            $arguments[] = new ConstantExpression($parsedTag->getGroup(), $lineNo);
        }

        /* @noinspection PhpParamsInspection */
        return new FunctionExpression(
            $functionName,
            new Node($arguments),
            $lineNo
        );
    }

    private function createAssignAndCheckNode(FunctionExpression $functionExpression, $body, $lineNo)
    {
        // set asset_url = webpack_asset('path/asset.css', 'css')
        /** @noinspection PhpParamsInspection */
        $assignExpression = new SetNode(
            false,
            new AssignNameExpression('asset_url', $lineNo),
            $functionExpression,
            $lineNo,
            $this->getTag()
        );

        // if (asset_url) { ... }
        $ifBlock = new IfNode(new Node([
            new AssignNameExpression('asset_url', $lineNo),
            $body,
        ]), null, $lineNo, $this->getTag());

        return new Node([$assignExpression, $ifBlock]);
    }

    public function getTag()
    {
        return self::TAG_NAME;
    }
}
