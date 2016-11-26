<?php

namespace Maba\Bundle\WebpackBundle\Compiler;

use Maba\Bundle\WebpackBundle\Config\WebpackConfig;
use Maba\Bundle\WebpackBundle\Config\WebpackConfigManager;
use Maba\Bundle\WebpackBundle\Service\ManifestStorage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use Closure;
use RuntimeException;
use Symfony\Component\Process\Exception\RuntimeException as ProcessRuntimeException;
use Exception;

class WebpackCompiler
{
    private $webpackConfigManager;
    private $manifestPath;
    private $manifestStorage;
    private $workingDirectory;
    private $logger;
    private $webpackExecutable;
    private $webpackTtyPrefix;
    private $webpackArguments;
    private $devServerExecutable;
    private $devServerTtyPrefix;
    private $devServerArguments;
    private $disableTty;

    public function __construct(
        WebpackConfigManager $webpackConfigManager,
        $manifestPath,
        ManifestStorage $manifestStorage,
        $workingDirectory,
        LoggerInterface $logger,
        array $webpackExecutable,
        array $webpackTtyPrefix,
        array $webpackArguments,
        array $devServerExecutable,
        array $devServerTtyPrefix,
        array $devServerArguments,
        $disableTty
    ) {
        $this->webpackConfigManager = $webpackConfigManager;
        $this->manifestPath = $manifestPath;
        $this->manifestStorage = $manifestStorage;
        $this->workingDirectory = $workingDirectory;
        $this->logger = $logger;
        $this->webpackExecutable = $webpackExecutable;
        $this->webpackTtyPrefix = $webpackTtyPrefix;
        $this->webpackArguments = $webpackArguments;
        $this->devServerExecutable = $devServerExecutable;
        $this->devServerTtyPrefix = $devServerTtyPrefix;
        $this->devServerArguments = $devServerArguments;
        $this->disableTty = $disableTty;
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

        $process = $this->buildProcess($processBuilder, array(), $this->webpackTtyPrefix);

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
            $this->devServerExecutable,
            array('--config', $config->getConfigPath()),
            $this->devServerArguments
        ));
        $processBuilder->setWorkingDirectory($this->workingDirectory);
        $processBuilder->setTimeout(0);

        $prefix = DIRECTORY_SEPARATOR === '\\' ? array() : array('exec');
        $ttyPrefix = array_merge($prefix, $this->devServerTtyPrefix);
        $process = $this->buildProcess($processBuilder, $prefix, $ttyPrefix);
        $this->addEnvironment($process, 'WEBPACK_MODE=watch');

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

        try {
            $this->loop($process, $config, $processCallback);
        } catch (Exception $exception) {
            $process->stop();
            throw $exception;
        }
    }

    private function loop(Process $process, WebpackConfig $config, $processCallback)
    {
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

                // try to save the manifest - output callback is not called in dashboard mode
                $this->saveManifest(false);
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

    private function buildProcess(ProcessBuilder $processBuilder, $prefix, $ttyPrefix)
    {
        if ($this->disableTty) {
            $processBuilder->setPrefix($prefix);
            return $processBuilder->getProcess();
        }

        // try to set prefix with TTY support
        $processBuilder->setPrefix($ttyPrefix);
        $process = $processBuilder->getProcess();
        try {
            $process->setTty(true);
            $this->addEnvironment($process, 'TTY_MODE=on');
        } catch (ProcessRuntimeException $exception) {
            // if TTY is not available, fall back to default prefix if it's different
            if ($prefix !== $ttyPrefix) {
                $processBuilder->setPrefix($prefix);
                $process = $processBuilder->getProcess();
            }
        }

        return $process;
    }

    /**
     * Modifies process command to add environment variable
     * Used instead of setEnv because:
     *  1) currently practically used only in TTY mode, which is only available in Linux
     *  2) setEnv resets all other environment variables, like PATH - this breaks things
     *  3) there is no portable way to get all current environment variables, $_ENV is empty by default
     *
     * @param Process $process
     * @param string $environment
     */
    private function addEnvironment(Process $process, $environment)
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            $process->setCommandLine($environment . ' ' . $process->getCommandLine());
        }
    }
}
