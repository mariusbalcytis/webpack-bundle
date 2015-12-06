<?php

namespace Maba\Bundle\WebpackBundle\Command;

use Maba\Bundle\WebpackBundle\Compiler\WebpackCompiler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class DevServerCommand extends Command
{
    private $compiler;

    public function __construct(WebpackCompiler $compiler)
    {
        parent::__construct('maba:webpack:dev-server');

        $this->compiler = $compiler;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->compiler->compileAndWatch(function($type, $buffer) use ($output) {
            if (Process::ERR === $type) {
                $output->write('<error>' . $buffer . '</error>');
            } else {
                $output->write($buffer);
            }
        });
    }
}
