<?php

namespace Maba\Bundle\WebpackBundle\DependencyInjection;

use Maba\Bundle\WebpackBundle\Compiler\WebpackProcessBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class MabaWebpackExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $container->setParameter('maba_webpack.enabled_bundles', $config['enabled_bundles']);

        $this->configureTwig($container, $config);
        $this->configureConfig($container, $config);
        $this->configureAliases($container, $config);
        $this->configureBin($container, $config);
        $this->configureDashboard($container, $config);
        $this->configureEntryFile($container, $config);
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration(
            array_keys($container->getParameter('kernel.bundles')),
            $container->getParameter('kernel.environment')
        );
    }

    private function configureTwig(ContainerBuilder $container, array $config)
    {
        $twigDirectories = $config['twig']['additional_directories'];
        $twigDirectories[] = '%kernel.root_dir%/Resources/views';
        if ($container->hasParameter('kernel.project_dir')) {
            $twigDirectories[] = '%kernel.project_dir%/templates';
        }
        $container->setParameter('maba_webpack.twig_directories', $twigDirectories);

        if ($config['twig']['suppress_errors'] === true) {
            $errorHandlerId = 'maba_webpack.error_handler.suppressing';
        } elseif ($config['twig']['suppress_errors'] === 'ignore_unknowns') {
            $errorHandlerId = 'maba_webpack.error_handler.ignore_unknowns';
        } else {
            $errorHandlerId = 'maba_webpack.error_handler.default';
        }
        $container->setAlias('maba_webpack.error_handler', $errorHandlerId);
    }

    private function configureConfig(ContainerBuilder $container, $config)
    {
        if (
            strpos($config['config']['path'], '%kernel.project_dir%') !== false
            && !$container->hasParameter('kernel.project_dir')
        ) {
            $config['config']['path'] = strtr($config['config']['path'], [
                '%kernel.project_dir%' => '%kernel.root_dir%/..',
            ]);
        }

        $container->setParameter('maba_webpack.webpack_config_path', $config['config']['path']);
        $container->setParameter('maba_webpack.webpack_config_parameters', $config['config']['parameters']);
        $container->setParameter('maba_webpack.config.manifest_file_path', $config['config']['manifest_file_path']);
    }

    private function configureAliases(ContainerBuilder $container, $config)
    {
        $defaultAliases = [
            'app' => '%kernel.root_dir%/Resources/assets',
        ];
        if ($container->hasParameter('kernel.project_dir')) {
            $defaultAliases['root'] = '%kernel.project_dir%';
            $defaultAliases['templates'] = '%kernel.project_dir%/templates';
            $defaultAliases['assets'] = '%kernel.project_dir%/assets';
        } else {
            $defaultAliases['root'] = '%kernel.root_dir%/..';
            $defaultAliases['templates'] = '%kernel.root_dir%/../templates';
            $defaultAliases['assets'] = '%kernel.root_dir%/../assets';
        }

        $additionalAliases = $config['aliases']['additional'] + $defaultAliases;
        $container->setParameter('maba_webpack.aliases.additional', $additionalAliases);
        $container->setParameter('maba_webpack.aliases.path_in_bundle', $config['aliases']['path_in_bundle']);
    }

    private function configureBin(ContainerBuilder $container, $config)
    {
        $container->setParameter('maba_webpack.bin.disable_tty', $config['bin']['disable_tty']);
        $container->setParameter('maba_webpack.bin.working_directory', $config['bin']['working_directory']);
        $container->setParameter('maba_webpack.bin.webpack.executable', $config['bin']['webpack']['executable']);
        $container->setParameter('maba_webpack.bin.webpack.arguments', $config['bin']['webpack']['arguments']);
        $container->setParameter('maba_webpack.bin.dev_server.executable', $config['bin']['dev_server']['executable']);
        $container->setParameter('maba_webpack.bin.dev_server.arguments', $config['bin']['dev_server']['arguments']);
    }

    private function configureDashboard(ContainerBuilder $container, $config)
    {
        $dashboardModeMap = [
            'always' => WebpackProcessBuilder::DASHBOARD_MODE_ENABLED_ALWAYS,
            'dev_server' => WebpackProcessBuilder::DASHBOARD_MODE_ENABLED_ON_DEV_SERVER,
            false => WebpackProcessBuilder::DASHBOARD_MODE_DISABLED,
        ];
        $container->setParameter('maba_webpack.dashboard.mode', $dashboardModeMap[$config['dashboard']['enabled']]);
        $container->setParameter('maba_webpack.dashboard.executable', $config['dashboard']['executable']);
    }

    private function configureEntryFile(ContainerBuilder $container, $config)
    {
        if (!$config['entry_file']['enabled']) {
            // both empty disables the functionality
            $container->setParameter('maba_webpack.entry_file.disabled_extensions', []);
            $container->setParameter('maba_webpack.entry_file.enabled_extensions', []);
        } else {
            $container->setParameter(
                'maba_webpack.entry_file.disabled_extensions',
                $config['entry_file']['disabled_extensions']
            );
            $container->setParameter(
                'maba_webpack.entry_file.enabled_extensions',
                $config['entry_file']['enabled_extensions']
            );
        }
        $container->setParameter('maba_webpack.entry_file.type_map', $config['entry_file']['type_map']);
    }
}
