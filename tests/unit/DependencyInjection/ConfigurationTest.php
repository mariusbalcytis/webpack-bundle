<?php

namespace Maba\Bundle\WebpackBundle\Tests\DependencyInjection;

use Codeception\TestCase\Test;
use Maba\Bundle\WebpackBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends Test
{
    /**
     * @param array|null $expected
     * @param array $config
     * @dataProvider bundlesResourceDataProvider
     */
    public function testBundlesResource($expected, $config)
    {
        $bundles = ['MyFirstBundle', 'MySecondBundle'];
        $configuration = new Configuration($bundles, 'dev');
        $processor = new Processor();
        $result = $processor->processConfiguration($configuration, [$config]);

        $this->assertSame($expected, $result['enabled_bundles']);
    }

    public function bundlesResourceDataProvider()
    {
        return [
            [
                ['MyFirstBundle', 'MySecondBundle'],
                [],
            ],
            [
                ['MyFirstBundle', 'MySecondBundle'],
                ['enabled_bundles' => null],
            ],
            [
                ['MyFirstBundle'],
                ['enabled_bundles' => ['MyFirstBundle']],
            ],
            [
                [],
                ['enabled_bundles' => []],
            ],
        ];
    }
}
