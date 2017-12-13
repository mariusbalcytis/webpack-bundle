<?php

namespace Maba\Bundle\WebpackBundle\Command;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\RuntimeException as ProcessRuntimeException;

class InstallAssetsHelper
{
    const MODE_YARN = 'yarn';
    const MODE_NPM = 'npm';

    private $questionHelper;
    private $rootDirectory;
    private $installWithoutAsking = false;
    private $disableTty;

    public function __construct(QuestionHelper $questionHelper, $rootDirectory, $disableTty)
    {
        $this->questionHelper = $questionHelper;
        $this->rootDirectory = $rootDirectory;
        $this->disableTty = $disableTty;
    }

    /**
     * @param bool $installWithoutAsking
     */
    public function setInstallWithoutAsking($installWithoutAsking)
    {
        $this->installWithoutAsking = $installWithoutAsking;
    }

    /**
     * @param OutputInterface $output
     * @return null|string
     */
    public function decideInstalledManager(OutputInterface $output)
    {
        $yarnLockFound = file_exists($this->rootDirectory . '/yarn.lock');
        $npmLockFound = file_exists($this->rootDirectory . '/package-lock.json');

        $process = new Process('yarn --version');
        $yarnInstalled = $process->run() === 0;
        $process = new Process('npm --version');
        $npmInstalled = $process->run() === 0;

        if ($yarnLockFound && $yarnInstalled) {
            return self::MODE_YARN;
        } elseif ($npmLockFound && $npmInstalled) {
            return self::MODE_NPM;
        } elseif ($yarnLockFound) {
            $this->outputYarnDependencyError($output);
        } elseif ($npmLockFound) {
            $this->outputNpmDependencyError($output);
        } elseif ($yarnInstalled) {
            return self::MODE_YARN;
        } elseif ($npmInstalled) {
            return self::MODE_NPM;
        } else {
            $this->outputDependenciesError($output);
        }

        return null;
    }

    public function installNodeModules($mode, InputInterface $input, OutputInterface $output)
    {
        $process = new Process(
            $mode === self::MODE_YARN ? 'yarn install' : 'npm install',
            $this->rootDirectory
        );
        if (!$this->askIfInstallNeeded($input, $output, $process)) {
            return;
        }

        $this->configureTty($process);

        $this->runProcess($process, $output);
    }

    private function outputYarnDependencyError(OutputInterface $output)
    {
        $notice = <<<'NOTICE'

<error>Dependencies needed</error>
<bold>yarn.lock</bold> file found, but <bold>yarn</bold> is not installed on the system.
See https://yarnpkg.com/ for more information.
You can re-run this command after installing or run <code>yarn install</code> in root directory.

NOTICE;
        $output->writeln($notice);
    }

    private function outputNpmDependencyError(OutputInterface $output)
    {
        $notice = <<<'NOTICE'

<error>Dependencies needed</error>
<bold>package-lock.json</bold> file found, but <bold>npm</bold> is not installed on the system.
It should be installed together with node.js: https://nodejs.org/en/download/
You can re-run this command after installing or run <code>npm install</code> in root directory.

NOTICE;
        $output->writeln($notice);
    }

    private function outputDependenciesError(OutputInterface $output)
    {
        $notice = <<<'NOTICE'

<error>Dependencies needed</error>
Neither <bold>yarn</bold> nor <bold>npm</bold> was found on the system.
Install node.js from https://nodejs.org/en/download/, see https://yarnpkg.com/ for more information about yarn.
You can re-run this command after installing or run <code>yarn install</code> in root directory.

NOTICE;
        $output->writeln($notice);
    }

    private function askIfInstallNeeded(InputInterface $input, OutputInterface $output, Process $process)
    {
        if ($this->installWithoutAsking) {
            return true;
        }

        $question = new ConfirmationQuestion(sprintf(
            '<question>Should I install node_modules now?</question> (<code>%s</code>) [Yn] ',
            $process->getCommandLine()
        ), true);

        if (!$this->questionHelper->ask($input, $output, $question)) {
            $output->writeln(sprintf(
                'Please run <code>%s</code> in root directory before compiling webpack assets',
                $process->getCommandLine()
            ));
            return false;
        }

        return true;
    }

    private function configureTty(Process $process)
    {
        if ($this->disableTty) {
            return;
        }

        try {
            $process->setTty(true);
        } catch (ProcessRuntimeException $exception) {
            // thrown if TTY is not available - just ignore
        }
    }

    private function runProcess(Process $process, OutputInterface $output)
    {
        $process->setTimeout(600);
        $process->run(function ($type, $buffer) use ($output) {
            $output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $error = <<<'ERROR'
            
<error>Error running %s (exit code %s)! Please look at the log for errors and re-run command.</error>

ERROR;
            $output->writeln(sprintf($error, $process->getCommandLine(), $process->getExitCode()));
        }
    }
}
