<?php

namespace Maba\Bundle\WebpackBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    protected $availableBundles;
    /**
     * @var
     */
    private $environment;

    public function __construct(array $availableBundles, $environment)
    {
        $this->availableBundles = $availableBundles;
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $availableBundles = $this->availableBundles;

        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('maba_webpack');

        $children = $rootNode->children();

        $assetProviders = $children->arrayNode('asset_providers');
        $assetProviders->defaultValue(array(
            array('type' => 'twig_bundles', 'resource' => $availableBundles),
            array('type' => 'twig_directory', 'resource' => '%kernel.root_dir%/Resources/views')
        ));

        $assetProviders->beforeNormalization()->always(function($value) use ($availableBundles) {
            foreach ($value as &$item) {
                if ($item['type'] === 'twig_bundles' && empty($item['resource'])) {
                    $item['resource'] = $availableBundles;
                }
            }
            return $value;
        });

        /** @var ArrayNodeDefinition $prototype  */
        $prototype = $assetProviders->prototype('array');
        $prototypeChildren = $prototype->children();
        $prototypeChildren->scalarNode('type');
        $prototypeChildren->variableNode('resource');

        $twig = $children->arrayNode('twig')->addDefaultsIfNotSet()->children();
        $twig->scalarNode('function_name')
            ->defaultNull()
            ->info('Deprecated and will be removed in next version')
        ;
        $suppressErrorsNode = $twig->scalarNode('suppress_errors')->defaultValue(
            $this->environment === 'dev' ? true : 'ignore_unknowns'
        );
        $suppressErrorsNode
            ->validate()
            ->ifNotInArray(array(true, false, 'ignore_unknowns'))
            ->thenInvalid('suppress_errors must be either a boolean or "ignore_unknowns"')
        ;

        $config = $children->arrayNode('config')->addDefaultsIfNotSet()->children();
        $config->scalarNode('path')->defaultValue('%kernel.root_dir%/config/webpack.config.js');
        $config->arrayNode('parameters')->treatNullLike(array())->useAttributeAsKey('name')->prototype('variable');

        $config->scalarNode('webpack_entry_config_path')->defaultValue('%kernel.cache_dir%/webpack.config.js');
        $config->scalarNode('manifest_file_path')->defaultValue('%kernel.cache_dir%/webpack_manifest.php');
        $config->scalarNode('json_manifest_file_path')->defaultValue('%kernel.cache_dir%/webpack_manifest.json');
        $config->scalarNode('root_directory')->defaultValue('%kernel.root_dir%/..');

        $entryFile = $children->arrayNode('entry_file')->addDefaultsIfNotSet()->children();
        $entryFile->booleanNode('enabled')->defaultTrue();
        $entryFile
            ->arrayNode('disabled_extensions')
            ->defaultValue(array('js', 'jsx', 'ts', 'coffee', 'es6', 'ls'))
            ->prototype('scalar')
            ->info('For these extensions default webpack functionality will be used')
        ;
        $entryFile
            ->arrayNode('enabled_extensions')
            ->defaultValue(array())
            ->prototype('scalar')
            ->info(
                'For these extensions file itself will be provided (not JS file). '
                . 'Set to non-empty to override disabled extensions. Empty means all but disabled'
            )
        ;
        $entryFile
            ->arrayNode('type_map')
            ->defaultValue(array(
                'css' => array('less', 'scss', 'sass', 'styl'),
            ))
            ->prototype('array')
            ->info(
                'What output file type to use for what input file types. Used only for entry files. '
                . 'Defaults to same file type - needed only when preprocessors are used'
            )
        ;

        $aliases = $children->arrayNode('aliases')->addDefaultsIfNotSet()->children();
        $registerBundles = $aliases->arrayNode('register_bundles');
        $registerBundles
            ->defaultValue($availableBundles)
            ->treatNullLike($availableBundles)
        ;
        $registerBundles
            ->prototype('scalar')
            ->validate()
            ->ifNotInArray($availableBundles)
            ->thenInvalid('%s is not a valid bundle.')
        ;
        $aliases->scalarNode('path_in_bundle')->defaultValue('Resources/assets');
        $aliases->arrayNode('additional')->treatNullLike(array())->useAttributeAsKey('name')->prototype('scalar');

        $bin = $children->arrayNode('bin')->addDefaultsIfNotSet()->children();

        $bin->booleanNode('disable_tty')->defaultValue($this->environment !== 'dev');

        $webpack = $bin->arrayNode('webpack')->addDefaultsIfNotSet()->children();
        $webpack
            ->arrayNode('executable')
            ->defaultValue(array('node', 'node_modules/webpack/bin/webpack.js'))
            ->prototype('scalar')
        ;
        $webpack->arrayNode('tty_prefix')->defaultValue(array())->prototype('scalar');
        $webpack->arrayNode('arguments')->defaultValue(array())->prototype('scalar');

        $devServer = $bin->arrayNode('dev_server')->addDefaultsIfNotSet()->children();
        $devServer
            ->arrayNode('executable')
            ->defaultValue(array('node', 'node_modules/webpack-dev-server/bin/webpack-dev-server.js'))
            ->prototype('scalar')
        ;
        $devServer->arrayNode('tty_prefix')
            ->defaultValue(array('node', 'node_modules/webpack-dashboard/bin/webpack-dashboard.js', '--'))
            ->prototype('scalar')
        ;
        $devServer->arrayNode('arguments')->defaultValue(array(
            '--hot',
            '--history-api-fallback',
            '--inline',
        ))->prototype('scalar');

        return $treeBuilder;
    }
}
