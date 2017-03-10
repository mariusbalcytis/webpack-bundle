<?php

namespace Maba\Bundle\WebpackBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Process;

class SetupCommand extends Command
{
    private $pathToPackageV1;
    private $pathToWebpackConfigV1;
    private $pathToPackageV2;
    private $pathToWebpackConfigV2;
    private $rootDirectory;
    private $configPath;

    public function __construct(
        $pathToPackageV1,
        $pathToWebpackConfigV1,
        $pathToPackageV2,
        $pathToWebpackConfigV2,
        $rootDirectory,
        $configPath
    ) {
        parent::__construct('maba:webpack:setup');

        $this->pathToPackageV1 = $pathToPackageV1;
        $this->pathToWebpackConfigV1 = $pathToWebpackConfigV1;
        $this->pathToPackageV2 = $pathToPackageV2;
        $this->pathToWebpackConfigV2 = $pathToWebpackConfigV2;
        $this->rootDirectory = realpath($rootDirectory);
        $this->configPath = $configPath;
    }

    protected function configure()
    {
        $this
            ->addOption(
                'useWebpackV1',
                'w1',
                InputOption::VALUE_NONE,
                'If default configuration for webpack v1 should be used'
            )
            ->setDescription('Initial setup for maba webpack bundle')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command copies a default <info>webpack.config.js</info> and <info>package.json</info> files and runs <info>npm install</info>. 

After executing this command, you should commit the following files to your repository.

    <info>git add package.json app/config/webpack.config.js</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $useWebpackV1 = $input->getOption('useWebpackV1');
        $pathToPackage = $useWebpackV1 ? $this->pathToPackageV1 : $this->pathToPackageV2;
        $pathToWebpackConfig = $useWebpackV1 ? $this->pathToWebpackConfigV1 : $this->pathToWebpackConfigV2;

        $this->copyPackage($pathToPackage, $input, $output);
        $this->copyWebpackConfig($pathToWebpackConfig, $input, $output);
        $this->installNodeModules($input, $output);
    }

    private function copyPackage($pathToPackage, InputInterface $input, OutputInterface $output)
    {
        $target = $this->rootDirectory . '/' . basename($pathToPackage);
        $question = new ConfirmationQuestion(sprintf(
            '<question>File in %s already exists. Replace?</question> [yN] ',
            $target
        ), false);
        if (
            !file_exists($target)
            || $this->ask($input, $output, $question)
        ) {
            copy($pathToPackage, $target);
            $output->writeln(sprintf('Dumped default package to <info>%s</info>', $target));
        } else {
            $output->writeln(sprintf(
                'Please update <info>%s</info> by example in <info>%s</info> manually',
                $target,
                $pathToPackage
            ));
        }
    }

    private function copyWebpackConfig($pathToWebpackConfig, InputInterface $input, OutputInterface $output)
    {
        $question = new ConfirmationQuestion(sprintf(
            '<question>File in %s already exists. Replace?</question> [yN] ',
            $this->configPath
        ), false);
        if (
            !file_exists($this->configPath)
            || $this->ask($input, $output, $question)
        ) {
            copy($pathToWebpackConfig, $this->configPath);
            $output->writeln(sprintf('Dumped default webpack config to <info>%s</info>', $this->configPath));
        } else {
            $output->writeln(sprintf(
                'Please update <info>%s</info> by example in <info>%s</info> manually',
                $this->configPath,
                $pathToWebpackConfig
            ));
        }
    }

    private function installNodeModules(InputInterface $input, OutputInterface $output)
    {
        $process = new Process('npm install', $this->rootDirectory);
        $question = new ConfirmationQuestion(
            sprintf('<question>Should I install node dependencies?</question> (%s) [Yn] ', $process->getCommandLine()),
            true
        );
        if ($this->ask($input, $output, $question)) {
            $process->setTimeout(600);
            $process->run(function($type, $buffer) use ($output) {
                $output->write($buffer);
            });
        } else {
            $output->writeln('Please update dependencies manually before compiling webpack assets');
        }
        $output->writeln(
            'Run <bg=white;fg=black>maba:webpack:compile</> to compile assets when deploying'
        );
        $output->writeln(
            'Always run <bg=white;fg=black>maba:webpack:dev-server</> in dev environment'
        );
    }

    private function ask(InputInterface $input, OutputInterface $output, Question $question)
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        return $helper->ask($input, $output, $question);
    }
}
