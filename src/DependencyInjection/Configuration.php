<?php

namespace Maba\Bundle\WebpackBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @var array
     */
    protected $availableBundles;

    /**
     * @var string
     */
    private $environment;

    /**
     * Configuration constructor.
     *
     * @param array $availableBundles
     * @param string $environment
     */
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

        $rootNode->children() // @formatter:off

            /**
             * Root working and cache directories
             */
            ->scalarNode('working_dir')
                ->defaultValue('%kernel.root_dir%/..')
            ->end()
            ->scalarNode('cache_dir')
                ->defaultValue('%kernel.cache_dir%')
            ->end()

            /**
             * Asset paths
             */
            ->arrayNode('asset_providers')
				->defaultValue([
					[
						'type' => 'twig_bundles',
						'resource' => $availableBundles,
					],
					[
						'type' => 'twig_directory',
						'resource' => '%kernel.root_dir%/Resources/views',
					],
				])
				->beforeNormalization()
					->always(function ($value) use ($availableBundles) {

						foreach ($value as &$item) {

							if ($item['type'] === 'twig_bundles' && empty($item['resource'])) {
								$item['resource'] = $availableBundles;
							}
						}

						return $value;
					})
                ->end()
				->prototype('array')
					->children()
						->scalarNode('type')->end()
						->variableNode('resource')->end()
					->end()
                ->end()
            ->end() // asset_providers

            /**
             * Twig error handling
             */
            ->arrayNode('twig')
                ->addDefaultsIfNotSet()
                ->children()

                    ->scalarNode('function_name')
                        ->defaultNull()
						->info('Deprecated and will be removed in next version')
                    ->end()

					->scalarNode('suppress_errors')
						->defaultValue(
							$this->environment === 'dev' ? true : 'ignore_unknowns'
						)
						->validate()
							->ifNotInArray([
								true,
								false,
								'ignore_unknowns',
							])
							->thenInvalid('suppress_errors must be either a boolean or "ignore_unknowns"')
                        ->end()
					->end()

                ->end()
            ->end() // twig

            /**
             * Webpack config path and parameters
             */
            ->arrayNode('config')
				->addDefaultsIfNotSet()
				->children()

                    ->scalarNode('path')
				    	->defaultValue('%kernel.root_dir%/config/webpack.config.js')
                    ->end()

                    ->arrayNode('parameters')
						->treatNullLike([])
						->useAttributeAsKey('name')
						->prototype('variable')->end()
                    ->end()

                ->end()
            ->end() // config

            /**
             * Entry file
             */
            ->arrayNode('entry_file')
                ->addDefaultsIfNotSet()
				->children()

					->booleanNode('enabled')
						->defaultTrue()
					->end()

                    ->arrayNode('disabled_extensions')
						->defaultValue([
							'js',
							'jsx',
							'ts',
							'tsx',
							'coffee',
							'es6',
							'ls',
						])
						->prototype('scalar')
						    ->info('For these extensions default webpack functionality will be used')
                        ->end()
					->end()

					->arrayNode('enabled_extensions')
						->defaultValue([])
						->prototype('scalar')
							->info(
								'For these extensions file itself will be provided (not JS file). '
								. 'Set to non-empty to override disabled extensions. Empty means all but disabled'
							)
                        ->end()
					->end()

					->arrayNode('type_map')
						->defaultValue([
							'css' => [
								'less',
								'scss',
								'sass',
								'styl',
							],
						])
						->prototype('array')
							->info(
								'What output file type to use for what input file types. Used only for entry files. '
								. 'Defaults to same file type - needed only when preprocessors are used'
							)
                        ->end()
					->end()

				->end()
            ->end() // entry_file

            /**
             * Aliases
             */
            ->arrayNode('aliases')
                ->addDefaultsIfNotSet()
				->children()

					->arrayNode('register_bundles')
						->defaultValue($availableBundles)
						->treatNullLike($availableBundles)
						->prototype('scalar')
							->validate()
								->ifNotInArray($availableBundles)
								->thenInvalid('%s is not a valid bundle.')
							->end()
						->end()
					->end()

					->scalarNode('path_in_bundle')
						->defaultValue('Resources/assets')
					->end()

					->arrayNode('additional')
						->treatNullLike([])
						->useAttributeAsKey('name')
						->prototype('scalar')->end()
					->end()

				->end()
			->end() // aliases

            /**
             * Webpack and webpack-dev-server executables
             */
            ->arrayNode('bin')
                ->addDefaultsIfNotSet()
                ->children()

                    ->booleanNode('disable_tty')
                        ->defaultValue($this->environment !== 'dev')
                    ->end()

					/**
					 * Webpack executable and arguments
					 */
                    ->arrayNode('webpack')
                        ->addDefaultsIfNotSet()
                        ->children()
							->arrayNode('executable')
								->defaultValue([
									'node',
									'node_modules/webpack/bin/webpack.js',
								])
								->prototype('scalar')->end()
							->end()
							->arrayNode('tty_prefix')
								->defaultValue([])
								->prototype('scalar')->end()
							->end()
							->arrayNode('arguments')
								->defaultValue([])
								->prototype('scalar')->end()
							->end()
                        ->end()
                    ->end()

					/**
					 * Webpack dev server executable and arguments
					 */
                    ->arrayNode('dev_server')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->arrayNode('executable')
                                ->defaultValue([
									'node',
									'node_modules/webpack-dev-server/bin/webpack-dev-server.js',
								])
								->prototype('scalar')->end()
                            ->end()
							->arrayNode('tty_prefix')
								->defaultValue([
									'node',
									'node_modules/webpack-dashboard/bin/webpack-dashboard.js',
									'--',
								])
								->prototype('scalar')->end()
                            ->end()
                            ->arrayNode('arguments')
                                ->defaultValue([
									'--hot',
									'--history-api-fallback',
									'--inline',
								])
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()

                ->end()
            ->end() // bin
        ;

        return $treeBuilder;
    }
}

