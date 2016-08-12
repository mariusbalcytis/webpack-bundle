<?php
namespace Helper;

use Codeception\Module\Filesystem;
use Codeception\Module\Symfony2;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Codeception\Lib\Connector\Symfony2 as Symfony2Connector;

class Functional extends Symfony2
{
    /**
     * @var CommandTester
     */
    protected $commandTester;

    /**
     * @var int
     */
    protected $errorCode;


    public function _before(\Codeception\TestCase $test)
    {
        // do nothing
    }

    protected function bootKernel($configFile = null)
    {
        if ($this->kernel) {
            return;
        }
        $this->kernel = new \TestKernel(
            $this->config['environment'] . ($configFile !== null ? $configFile : ''),
            $this->config['debug']
        );
        if ($configFile) {
            $this->kernel->setConfigFile($configFile);
        }
        $this->kernel->boot();
    }

    public function bootKernelWith($configFile = null)
    {
        $this->kernel = null;
        $this->bootKernel($configFile);
        $this->container = $this->kernel->getContainer();
        $this->client = new Symfony2Connector($this->kernel);
        $this->client->followRedirects(true);
    }


    public function cleanUp()
    {
        /** @var Filesystem $filesystem */
        $filesystem = $this->getModule('Filesystem');

        if (file_exists(__DIR__ . '/../../functional/Fixtures/package.json')) {
            unlink(__DIR__ . '/../../functional/Fixtures/package.json');
        }
        if (file_exists(__DIR__ . '/../../functional/Fixtures/app/config/webpack.config.js')) {
            unlink(__DIR__ . '/../../functional/Fixtures/app/config/webpack.config.js');
        }
        if (file_exists(__DIR__ . '/../../functional/Fixtures/web/compiled')) {
            $filesystem->cleanDir(__DIR__ . '/../../functional/Fixtures/web/compiled');
        }
        if (file_exists(__DIR__ . '/../../functional/Fixtures/web/assets')) {
            $filesystem->cleanDir(__DIR__ . '/../../functional/Fixtures/web/assets');
        }
        if (file_exists(__DIR__ . '/../../functional/Fixtures/app/cache')) {
            $filesystem->cleanDir(__DIR__ . '/../../functional/Fixtures/app/cache');
        }
    }

    public function runCommand($commandServiceId, array $input = array())
    {
        $this->errorCode = null;
        $this->commandTester = null;

        $command = $this->grabServiceFromContainer($commandServiceId);

        $application = new Application($this->kernel);
        $application->add($command);

        $commandTester = new CommandTester($command);

        try {
            $commandTester->execute(array(
                'command' => $command->getName(),
            ) + $input, array('interactive' => false));
        } catch (\Exception $e) {
            $exitCode = $e->getCode();
            if (is_numeric($exitCode)) {
                $exitCode = (int) $exitCode;
                if (0 === $exitCode) {
                    $exitCode = 1;
                }
            } else {
                $exitCode = 1;
            }
            $this->errorCode = $exitCode;
            $this->debug((string)$e);
            return;
        }

        $this->debug($commandTester->getDisplay());

        $this->commandTester = $commandTester;
    }

    public function seeCommandStatusCode($code)
    {
        $statusCode = $this->errorCode !== null ? $this->errorCode : $this->commandTester->getStatusCode();
        $this->assertEquals($code, $statusCode);
    }

    public function seeInCommandDisplay($substring)
    {
        $this->assertContains($substring, $this->commandTester->getDisplay());
    }

    public function dontSeeInCommandDisplay($substring)
    {
        $this->assertNotContains($substring, $this->commandTester->getDisplay());
    }
}
