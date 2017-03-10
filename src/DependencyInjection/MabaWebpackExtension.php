<?php

namespace Maba\Bundle\WebpackBundle\DependencyInjection;

use Maba\Bundle\WebpackBundle\Twig\WebpackExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class MabaWebpackExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $additionalAliases = $config['aliases']['additional'] + array(
            'app' => '%kernel.root_dir%/Resources/assets',
            'root' => '%kernel.root_dir%/..',
        );

        $container->setParameter('maba_webpack.provider_config', $config['asset_providers']);

        if ($config['twig']['function_name'] !== null) {
            @trigger_error(
                'maba_webpack.twig.function_name configuration option is deprecated and will be removed in 0.6',
                E_USER_DEPRECATED
            );
        } else {
            $config['twig']['function_name'] = WebpackExtension::FUNCTION_NAME;
        }
        $container->setParameter('maba_webpack.twig_function_name', $config['twig']['function_name']);

        $container->setParameter('maba_webpack.webpack_config_path', $config['config']['path']);
        $container->setParameter('maba_webpack.webpack_config_parameters', $config['config']['parameters']);

        if (!$config['entry_file']['enabled']) {
            // both empty disables the functionality
            $container->setParameter('maba_webpack.entry_file.disabled_extensions', array());
            $container->setParameter('maba_webpack.entry_file.enabled_extensions', array());
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

        $container->setParameter('maba_webpack.aliases.register_bundles', $config['aliases']['register_bundles']);
        $container->setParameter('maba_webpack.aliases.path_in_bundle', $config['aliases']['path_in_bundle']);
        $container->setParameter('maba_webpack.aliases.additional', $additionalAliases);

        $container->setParameter('maba_webpack.bin.disable_tty', $config['bin']['disable_tty']);
        $container->setParameter('maba_webpack.bin.webpack.executable', $config['bin']['webpack']['executable']);
        $container->setParameter('maba_webpack.bin.webpack.tty_prefix', $config['bin']['webpack']['tty_prefix']);
        $container->setParameter('maba_webpack.bin.webpack.arguments', $config['bin']['webpack']['arguments']);
        $container->setParameter('maba_webpack.bin.dev_server.executable', $config['bin']['dev_server']['executable']);
        $container->setParameter('maba_webpack.bin.dev_server.tty_prefix', $config['bin']['dev_server']['tty_prefix']);
        $container->setParameter('maba_webpack.bin.dev_server.arguments', $config['bin']['dev_server']['arguments']);
        $container->setParameter("maba_webpack.config.manifest_file_path", $config['config']['manifest_file_path']);

        if ($config['twig']['suppress_errors'] === true) {
            $errorHandlerId = 'maba_webpack.error_handler.suppressing';
        } elseif ($config['twig']['suppress_errors'] === 'ignore_unknowns') {
            $errorHandlerId = 'maba_webpack.error_handler.ignore_unknowns';
        } else {
            $errorHandlerId = 'maba_webpack.error_handler.default';
        }
        $container->setAlias('maba_webpack.error_handler', $errorHandlerId);
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration(
            array_keys($container->getParameter('kernel.bundles')),
            $container->getParameter('kernel.environment')
        );
    }
}
