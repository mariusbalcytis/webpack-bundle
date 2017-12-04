<?php

namespace Maba\Bundle\WebpackBundle\Twig;

use Twig_TokenStream as TokenStream;
use Twig_Error_Syntax as SyntaxError;

class ParsedTag
{
    private $stream;

    private $inputs = [];
    private $group = null;
    private $type = null;
    private $named = false;

    public function __construct(TokenStream $stream)
    {
        $this->stream = $stream;
    }

    /**
     * @param string $input
     */
    public function addInput($input)
    {
        $this->inputs[] = $input;
    }

    /**
     * @param string $group
     */
    public function setGroup($group)
    {
        if ($this->group !== null) {
            $this->throwException(sprintf(
                'Assets can have only a single group, which was already defined for this tag ("%s")',
                $this->group
            ));
        }

        $this->group = $group;
        $this->checkNamedWithGroup();
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        if ($this->type !== null) {
            $this->throwException(sprintf(
                'Type can be provided only once, type ("%s") was already defined for this tag',
                $this->type
            ));
        }

        $this->type = $type;
    }

    public function markAsNamed()
    {
        $this->named = true;
        $this->checkNamedWithGroup();
    }

    /**
     * @return array of strings
     */
    public function getInputs()
    {
        return $this->inputs;
    }

    /**
     * @return string|null
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isNamed()
    {
        return $this->named;
    }

    private function throwException($description)
    {
        $token = $this->stream->getCurrent();
        /* @noinspection PhpInternalEntityUsedInspection */
        throw new SyntaxError($description, $token->getLine(), $this->stream->getSourceContext());
    }

    private function checkNamedWithGroup()
    {
        if ($this->named && $this->group !== null) {
            $this->throwException(sprintf(
                'Named assets cannot have group assigned, group "%s" was assigned for named asset in this tag',
                $this->group
            ));
        }
    }
}
