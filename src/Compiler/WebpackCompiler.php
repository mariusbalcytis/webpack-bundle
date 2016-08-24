<?php

namespace Maba\Bundle\WebpackBundle\Compiler;

use Maba\Bundle\WebpackBundle\Config\WebpackConfig;
use Maba\Bundle\WebpackBundle\Config\WebpackConfigManager;
use Maba\Bundle\WebpackBundle\Service\ManifestStorage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\ProcessBuilder;
use Closure;
use RuntimeException;

class WebpackCompiler
{
    private $webpackConfigManager;
    private $manifestPath;
    private $manifestStorage;
    private $workingDirectory;
    private $logger;
    private $webpackExecutable;
    private $webpackArguments;
    private $devServerExecutable;
    private $devServerArguments;

    public function __construct(
        WebpackConfigManager $webpackConfigManager,
        $manifestPath,
        ManifestStorage $manifestStorage,
        $workingDirectory,
        LoggerInterface $logger,
        array $webpackExecutable,
        array $webpackArguments,
        array $devServerExecutable,
        array $devServerArguments
    ) {
        $this->webpackConfigManager = $webpackConfigManager;
        $this->manifestPath = $manifestPath;
        $this->manifestStorage = $manifestStorage;
        $this->workingDirectory = $workingDirectory;
        $this->logger = $logger;
        $this->webpackExecutable = $webpackExecutable;
        $this->webpackArguments = $webpackArguments;
        $this->devServerExecutable = $devServerExecutable;
        $this->devServerArguments = $devServerArguments;
    }

    public function compile(Closure $callback = null, WebpackConfig $previousConfig = null)
    {
        $config = $this->webpackConfigManager->dump($previousConfig);

        $processBuilder = new ProcessBuilder();
        $processBuilder->setArguments(array_merge(
            $this->webpackExecutable,
            array('--config', $config->getConfigPath()),
            $this->webpackArguments
        ));
        $processBuilder->setWorkingDirectory($this->workingDirectory);
        $processBuilder->setTimeout(3600);
        $process = $processBuilder->getProcess();
        try {
            $process->setTty(true);
        } catch (\Symfony\Component\Console\Exception\RuntimeException $e) {
            //intentionally left blank
        }

        // remove manifest file if exists - keep sure we create new one
        if (file_exists($this->manifestPath)) {
            unlink($this->manifestPath);
        }

        $process->mustRun($callback);
        $this->saveManifest();
    }

    public function compileAndWatch(Closure $callback = null)
    {
        $config = $this->webpackConfigManager->dump();

        $processBuilder = new ProcessBuilder();
        $processBuilder->setArguments(array_merge(
            DIRECTORY_SEPARATOR === '\\' ? array() : array('exec'),
            $this->devServerExecutable,
            array('--config', $config->getConfigPath()),
            $this->devServerArguments
        ));
        $processBuilder->setWorkingDirectory($this->workingDirectory);
        $processBuilder->setTimeout(0);
        $process = $processBuilder->getProcess();
        try {
            $process->setTty(true);
        } catch (\Symfony\Component\Console\Exception\RuntimeException $e) {
            //intentionally left blank
        }

        // remove manifest file if exists - keep sure we create new one
        if (file_exists($this->manifestPath)) {
            $this->logger->info('Deleting manifest file', array($this->manifestPath));
            unlink($this->manifestPath);
        }

        $that = $this;
        $logger = $this->logger;
        $processCallback = function($type, $buffer) use ($that, $callback, $logger) {
            $that->saveManifest(false);
            $logger->info('Processing callback from process', array($type, $buffer));
            if ($callback !== null) {
                $callback($type, $buffer);
            }
        };

        $this->logger->info('Starting process', array($process->getCommandLine()));
        $process->start($processCallback);

        while (true) {
            sleep(1);
            $this->logger->debug('Dumping webpack configuration');
            $config = $this->webpackConfigManager->dump($config);
            if ($config->wasFileDumped()) {
                $this->logger->info(
                    'File was dumped (configuration changed) - restarting process',
                    $config->getEntryPoints()
                );
                $process->stop();
                $process = $process->restart($processCallback);
            } else {
                if (!$process->isRunning()) {
                    $this->logger->info('Process has shut down - returning');
                    return;
                }
                $process->getOutput();
            }
        }
    }

    public function saveManifest($failIfMissing = true)
    {
        if (!file_exists($this->manifestPath)) {
            if ($failIfMissing) {
                throw new RuntimeException(
                    'Missing manifest file in ' . $this->manifestPath
                    . '. Keep sure assets-webpack-plugin is enabled with the same path in webpack config'
                );
            }
            return;
        }

        $manifest = json_decode(file_get_contents($this->manifestPath), true);
        $this->manifestStorage->saveManifest($manifest);

        if (!unlink($this->manifestPath)) {
            throw new RuntimeException('Cannot unlink manifest file at ' . $this->manifestPath);
        }
    }
}
