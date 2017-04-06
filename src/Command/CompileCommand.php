<?php

namespace Maba\Bundle\WebpackBundle\Command;

use Maba\Bundle\WebpackBundle\Compiler\WebpackCompiler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class CompileCommand extends Command
{
    private $compiler;
    private $logger;

    public function __construct(WebpackCompiler $compiler, LoggerInterface $logger)
    {
        parent::__construct('maba:webpack:compile');

        $this->compiler = $compiler;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setDescription('Compile webpack assets')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command compiles webpack assets.

    <info>%command.full_name%</info>

Pass the --env=prod flag to compile for production.

    <info>%command.full_name% --env=prod</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->logger;
        $this->compiler->compile(function ($type, $buffer) use ($output, $logger) {
            if (Process::ERR === $type) {
                $logger->error($buffer);
                $output->write('<error>' . $buffer . '</error>');
            } else {
                $logger->debug($buffer);
                $output->write($buffer);
            }
        });
    }
}
