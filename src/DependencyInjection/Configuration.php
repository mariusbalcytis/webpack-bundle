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
        /** @var ArrayNodeDefinition $prototype  */
        $prototype = $assetProviders->prototype('array');
        $prototypeChildren = $prototype->children();
        $prototypeChildren->scalarNode('type');
        $prototypeChildren->variableNode('resource');

        $twig = $children->arrayNode('twig')->addDefaultsIfNotSet()->children();
        $twig->scalarNode('function_name')->defaultValue('webpack_asset');
        $twig->scalarNode('suppress_errors')->defaultValue($this->environment === 'dev');

        $config = $children->arrayNode('config')->addDefaultsIfNotSet()->children();
        $config->scalarNode('path')->defaultValue('%kernel.root_dir%/config/webpack.config.js');
        $config->arrayNode('parameters')->treatNullLike(array())->useAttributeAsKey('name')->prototype('variable');

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
        $webpack = $bin->arrayNode('webpack')->addDefaultsIfNotSet()->children();
        $webpack
            ->arrayNode('executable')
            ->defaultValue(array('node', 'node_modules/webpack/bin/webpack.js'))
            ->prototype('scalar')
            ->beforeNormalization()
            ->ifString()
            ->then(function($value) {
                return array($value);
            })
        ;
        $webpack->arrayNode('arguments')->defaultValue(array())->prototype('scalar');
        $devServer = $bin->arrayNode('dev_server')->addDefaultsIfNotSet()->children();
        $devServer
            ->arrayNode('executable')
            ->defaultValue(array('node', 'node_modules/webpack-dev-server/bin/webpack-dev-server.js'))
            ->prototype('scalar')
            ->beforeNormalization()
            ->ifString()
            ->then(function($value) {
                return array($value);
            })
        ;
        $devServer->arrayNode('arguments')->defaultValue(array(
            '--hot',
            '--history-api-fallback',
            '--inline',
        ))->prototype('scalar');

        return $treeBuilder;
    }
}
