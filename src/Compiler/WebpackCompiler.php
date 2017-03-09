<?php

namespace Maba\Bundle\WebpackBundle\Compiler;

use Maba\Bundle\WebpackBundle\Config\WebpackConfig;
use Maba\Bundle\WebpackBundle\Config\WebpackConfigManager;
use Maba\Bundle\WebpackBundle\Service\ManifestStorage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;
use Closure;
use RuntimeException;
use Exception;

class WebpackCompiler
{
    private $webpackConfigManager;
    private $manifestPath;
    private $manifestStorage;
    private $webpackProcessBuilder;
    private $logger;

    public function __construct(
        WebpackConfigManager $webpackConfigManager,
        $manifestPath,
        ManifestStorage $manifestStorage,
        WebpackProcessBuilder $webpackProcessBuilder,
        LoggerInterface $logger
    ) {
        $this->webpackConfigManager = $webpackConfigManager;
        $this->manifestPath = $manifestPath;
        $this->manifestStorage = $manifestStorage;
        $this->webpackProcessBuilder = $webpackProcessBuilder;
        $this->logger = $logger;
    }

    public function compile(Closure $callback = null, WebpackConfig $previousConfig = null)
    {
        $config = $this->webpackConfigManager->dump($previousConfig);

        $process = $this->webpackProcessBuilder->buildWebpackProcess($config);

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

        $process = $this->webpackProcessBuilder->buildDevServerProcess($config);

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
}
