<?php

namespace Maba\Bundle\WebpackBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    private $availableBundles;
    private $environment;

    public function __construct(array $availableBundles, $environment)
    {
        $this->availableBundles = $availableBundles;
        $this->environment = $environment;
    }

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('maba_webpack');

        $rootChildren = $rootNode->children();

        $this->configureEnabledBundles($rootChildren);
        $this->configureTwig($rootChildren);
        $this->configureConfig($rootChildren);
        $this->configureAliases($rootChildren);
        $this->configureBin($rootChildren);
        $this->configureDashboard($rootChildren);
        $this->configureEntryFile($rootChildren);

        return $treeBuilder;
    }

    private function configureEnabledBundles(NodeBuilder $rootChildren)
    {
        $enabledBundlesNode = $rootChildren->arrayNode('enabled_bundles');
        $enabledBundlesNode->prototype('scalar');
        $enabledBundlesNode->defaultValue($this->availableBundles);
        $enabledBundlesNode->treatNullLike($this->availableBundles);
    }

    private function configureTwig(NodeBuilder $rootChildren)
    {
        $twigNode = $rootChildren->arrayNode('twig')->addDefaultsIfNotSet()->children();

        $additionalDirectoriesNode = $twigNode->arrayNode('additional_directories');
        $additionalDirectoriesNode->prototype('scalar');

        $suppressErrorsNode = $twigNode->scalarNode('suppress_errors')->defaultValue(
            $this->environment === 'dev' ? true : 'ignore_unknowns'
        );
        $suppressErrorsNode
            ->validate()
            ->ifNotInArray([true, false, 'ignore_unknowns'])
            ->thenInvalid('suppress_errors must be either a boolean or "ignore_unknowns"')
        ;
    }

    private function configureConfig(NodeBuilder $rootChildren)
    {
        $config = $rootChildren->arrayNode('config')->addDefaultsIfNotSet()->children();
        $config->scalarNode('path')->defaultValue('%kernel.project_dir%/config/webpack.config.js');
        $config->arrayNode('parameters')->treatNullLike([])->useAttributeAsKey('name')->prototype('variable');
        $config->scalarNode('manifest_file_path')->defaultValue('%kernel.cache_dir%/webpack_manifest.php');
    }

    private function configureAliases(NodeBuilder $rootChildren)
    {
        $aliases = $rootChildren->arrayNode('aliases')->addDefaultsIfNotSet()->children();
        $aliases->scalarNode('path_in_bundle')->defaultValue('Resources/assets');
        $aliases->arrayNode('additional')->treatNullLike([])->useAttributeAsKey('name')->prototype('scalar');
    }

    private function configureBin(NodeBuilder $rootChildren)
    {
        $bin = $rootChildren->arrayNode('bin')->addDefaultsIfNotSet()->children();

        $bin->booleanNode('disable_tty')->defaultValue($this->environment !== 'dev');
        $bin->scalarNode('working_directory')->defaultValue('%kernel.root_dir%/..');

        $webpack = $bin->arrayNode('webpack')->addDefaultsIfNotSet()->children();
        $webpack
            ->arrayNode('executable')
            ->defaultValue(['node_modules/.bin/webpack'])
            ->prototype('scalar')
        ;
        $webpack->arrayNode('arguments')->defaultValue([])->prototype('scalar');

        $devServer = $bin->arrayNode('dev_server')->addDefaultsIfNotSet()->children();
        $devServer
            ->arrayNode('executable')
            ->defaultValue(['node_modules/.bin/webpack-dev-server'])
            ->prototype('scalar')
        ;
        $devServer->arrayNode('arguments')->defaultValue([
            '--hot',
            '--history-api-fallback',
            '--inline',
        ])->prototype('scalar');
    }

    private function configureDashboard(NodeBuilder $rootChildren)
    {
        $dashboardNode = $rootChildren->arrayNode('dashboard')->addDefaultsIfNotSet()->children();

        $enabledNode = $dashboardNode->scalarNode('enabled')->defaultValue('dev_server');
        $enabledNode
            ->validate()
            ->ifNotInArray(['dev_server', 'always', false])
            ->thenInvalid('enabled must be one of "dev_server", "always" or a boolean false')
        ;

        $dashboardNode
            ->arrayNode('executable')
            ->defaultValue(['node_modules/.bin/webpack-dashboard'])
            ->prototype('scalar')
        ;
    }

    private function configureEntryFile(NodeBuilder $rootChildren)
    {
        $entryFile = $rootChildren->arrayNode('entry_file')->addDefaultsIfNotSet()->children();
        $entryFile->booleanNode('enabled')->defaultTrue();
        $entryFile
            ->arrayNode('disabled_extensions')
            ->defaultValue(['js', 'jsx', 'ts', 'tsx', 'coffee', 'es6', 'ls'])
            ->prototype('scalar')
            ->info('For these extensions default webpack functionality will be used')
        ;
        $entryFile
            ->arrayNode('enabled_extensions')
            ->defaultValue([])
            ->prototype('scalar')
            ->info(
                'For these extensions file itself will be provided (not JS file). '
                . 'Set to non-empty to override disabled extensions. Empty means all but disabled'
            )
        ;
        $entryFile
            ->arrayNode('type_map')
            ->defaultValue([
                'css' => ['less', 'scss', 'sass', 'styl'],
            ])
            ->prototype('array')
            ->info(
                'What output file type to use for what input file types. Used only for entry files. '
                . 'Defaults to same file type - needed only when preprocessors are used'
            )
        ;
    }
}
