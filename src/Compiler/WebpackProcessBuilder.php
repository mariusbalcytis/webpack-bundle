<?php

namespace Maba\Bundle\WebpackBundle\Compiler;

use Maba\Bundle\WebpackBundle\Config\WebpackConfig;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\Exception\RuntimeException as ProcessRuntimeException;
use Symfony\Component\Process\ProcessUtils;

class WebpackProcessBuilder
{
    const DASHBOARD_MODE_ENABLED_ALWAYS = 'enabled_always';
    const DASHBOARD_MODE_ENABLED_ON_DEV_SERVER = 'enabled_on_dev_server';
    const DASHBOARD_MODE_DISABLED = 'disabled';

    private $workingDirectory;
    private $disableTty;
    private $webpackExecutable;
    private $webpackArguments;
    private $devServerExecutable;
    private $devServerArguments;
    private $dashboardExecutable;
    private $dashboardMode;

    public function __construct(
        $workingDirectory,
        $disableTty,
        array $webpackExecutable,
        array $webpackArguments,
        array $devServerExecutable,
        array $devServerArguments,
        array $dashboardExecutable,
        $dashboardMode
    ) {
        $this->workingDirectory = $workingDirectory;
        $this->disableTty = $disableTty;
        $this->webpackExecutable = $webpackExecutable;
        $this->webpackArguments = $webpackArguments;
        $this->devServerExecutable = $devServerExecutable;
        $this->devServerArguments = $devServerArguments;
        $this->dashboardExecutable = $dashboardExecutable;
        $this->dashboardMode = $dashboardMode;
    }

    public function buildWebpackProcess(WebpackConfig $config)
    {
        $processBuilder = new ProcessBuilder();
        $processBuilder->setArguments(array_merge(
            $this->webpackExecutable,
            array('--config', $config->getConfigPath()),
            $this->webpackArguments
        ));
        $processBuilder->setTimeout(3600);

        $process = $this->buildProcess($processBuilder);

        if ($this->dashboardMode === self::DASHBOARD_MODE_ENABLED_ALWAYS) {
            $this->addDashboard($process);
        }

        return $process;
    }

    public function buildDevServerProcess(WebpackConfig $config)
    {
        $processBuilder = new ProcessBuilder();
        $processBuilder->setArguments(array_merge(
            $this->devServerExecutable,
            array('--config', $config->getConfigPath()),
            $this->devServerArguments
        ));
        $processBuilder->setTimeout(0);
        $processBuilder->setEnv('WEBPACK_MODE', 'watch');

        $process = $this->buildProcess($processBuilder);

        $dashboardEnabled = in_array($this->dashboardMode, array(
            self::DASHBOARD_MODE_ENABLED_ALWAYS,
            self::DASHBOARD_MODE_ENABLED_ON_DEV_SERVER
        ), true);

        if ($dashboardEnabled) {
            $this->addDashboard($process);
        }

        // from symfony 3.3 exec is added automatically
        if (DIRECTORY_SEPARATOR !== '\\' && substr($process->getCommandLine(), 0, 5) !== 'exec ') {
            $process->setCommandLine('exec ' . $process->getCommandLine());
        }

        return $process;
    }

    private function buildProcess(ProcessBuilder $processBuilder)
    {
        $processBuilder->setWorkingDirectory($this->workingDirectory);

        $process = $processBuilder->getProcess();
        if ($this->disableTty) {
            return $process;
        }

        try {
            $process->setTty(true);
        } catch (ProcessRuntimeException $exception) {
            // thrown if TTY is not available - just ignore
        }

        return $process;
    }

    private function addDashboard(Process $process)
    {
        if (!$process->isTty()) {
            return;
        }

        $prefix = implode(' ', array_map(function($part) {
            return ProcessUtils::escapeArgument($part);
        }, $this->dashboardExecutable));

        $process->setCommandLine($prefix . ' ' . $process->getCommandLine());

        $process->setEnv(array('WEBPACK_DASHBOARD' => 'enabled') + $process->getEnv());
    }
}
