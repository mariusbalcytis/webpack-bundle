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
        $bundles = array('MyFirstBundle', 'MySecondBundle');
        $configuration = new Configuration($bundles, 'dev');
        $processor = new Processor();
        $result = $processor->processConfiguration($configuration, array($config));

        $this->assertSame($expected, $result['enabled_bundles']);
    }

    public function bundlesResourceDataProvider()
    {
        return array(
            array(
                array('MyFirstBundle', 'MySecondBundle'),
                array()
            ),
            array(
                array('MyFirstBundle', 'MySecondBundle'),
                array('enabled_bundles' => null),
            ),
            array(
                array('MyFirstBundle'),
                array('enabled_bundles' => array('MyFirstBundle')),
            ),
            array(
                array(),
                array('enabled_bundles' => array()),
            ),
        );
    }
}
