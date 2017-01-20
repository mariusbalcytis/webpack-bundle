<?php

namespace Maba\Bundle\WebpackBundle\Tests\DependencyInjection;

use Codeception\TestCase\Test;
use Maba\Bundle\WebpackBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends Test
{
    /**
     * Test resulting bundles list
     * according to applied `asset_providers` value in custom config
     *
     * @param array|null $expected
     * @param array $config
     * @dataProvider bundlesResourceDataProvider
     */
    public function testBundlesResource($expected, $config)
    {
        $bundles = [
            'MyFirstBundle',
            'MySecondBundle',
        ];

        $configuration = new Configuration($bundles, 'dev');
        $processor = new Processor();

        $result = $processor->processConfiguration($configuration, [$config]);
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

    /**
     * @return array
     */
    public function bundlesResourceDataProvider()
    {
        return [
            '`asset_providers` not specified: all bundles should be added' => [
                // expected
                [
                    'MyFirstBundle',
                    'MySecondBundle',
                ],
                // config.yml
                [],
            ],
            '`twig_bundles` is null: all bundles should be added' => [
                // expected
                [
                    'MyFirstBundle',
                    'MySecondBundle',
                ],
                // config.yml
                [
                    'asset_providers' => [
                        [
                            'type'     => 'twig_bundles',
                            'resource' => null,
                        ],
                    ],
                ],
            ],
            '`twig_bundles` is empty: all bundles should be added' => [
                // expected
                [
                    'MyFirstBundle',
                    'MySecondBundle',
                ],
                // config.yml
                [
                    'asset_providers' => [
                        [
                            'type'     => 'twig_bundles',
                            'resource' => [],
                        ],
                    ],
                ],
            ],
            '`twig_bundles` are specified: default list of bundles should be overwritten' => [
                // expected
                ['MyFirstBundle'],
                // config.yml
                [
                    'asset_providers' => [
                        [
                            'type'     => 'twig_bundles',
                            'resource' => ['MyFirstBundle'],
                        ],
                    ],
                ],
            ],
            '`twig_directory` is specified: list of bundles should be removed when' => [
                // expected
                null,
                // config.yml
                [
                    'asset_providers' => [
                        [
                            'type'     => 'twig_directory',
                            'resource' => '%kernel.root_dir%/Resources/views',
                        ],
                    ],
                ],
            ],
        ];
    }
}
