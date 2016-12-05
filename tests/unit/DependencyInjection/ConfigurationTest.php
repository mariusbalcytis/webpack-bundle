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

        $found = false;
        foreach ($result['asset_providers'] as $assetProvider) {
            if ($assetProvider['type'] === 'twig_bundles') {
                $found = true;
                $this->assertSame($expected, $assetProvider['resource']);
            }
        }

        if ($expected === null) {
            $this->assertFalse($found);
        }
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
                array('asset_providers' => array(array('type' => 'twig_bundles', 'resource' => null))),
            ),
            array(
                array('MyFirstBundle', 'MySecondBundle'),
                array('asset_providers' => array(array('type' => 'twig_bundles', 'resource' => array()))),
            ),
            array(
                array('MyFirstBundle'),
                array('asset_providers' => array(array(
                    'type' => 'twig_bundles',
                    'resource' => array('MyFirstBundle'),
                ))),
            ),
            array(
                null,
                array('asset_providers' => array(array(
                    'type' => 'twig_directory',
                    'resource' => '%kernel.root_dir%/Resources/views',
                ))),
            ),
        );
    }
}
