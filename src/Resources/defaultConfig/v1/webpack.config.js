'use strict';

var webpack = require('webpack');
var path = require('path');
var fs = require('fs');
var autoprefixer = require('autoprefixer');
var ExtractTextPlugin = require('extract-text-webpack-plugin');
var AssetsPlugin = require('assets-webpack-plugin');
var ExtractFilePlugin = require('extract-file-loader/Plugin');
var DashboardPlugin = require('webpack-dashboard/plugin');

module.exports = function makeWebpackConfig(options) {
    /**
     * Whether we are generating minified assets for production
     */
    var BUILD = options.environment === 'prod';

    /**
     * Whether we are running in dev-server mode (versus simple compile)
     */
    var DEV_SERVER = process.env.WEBPACK_MODE === 'watch';

    /**
     * Whether we are running inside webpack-dashboard
     */
    var DASHBOARD = process.env.WEBPACK_DASHBOARD === 'enabled';

    var publicPath;
    if (options.parameters.dev_server_public_path && DEV_SERVER) {
        publicPath = options.parameters.dev_server_public_path;

    } else if (options.parameters.public_path) {
        publicPath = options.parameters.public_path;
    } else {
        publicPath = DEV_SERVER ? '//localhost:8080/compiled/' : '/compiled/';
    }

    var outputPath;
    if (options.parameters.path) {
        outputPath = options.parameters.path;
    } else {
        const findPublicDirectory = function(currentDirectory, fallback) {
            var parentDirectory = path.dirname(currentDirectory);
            if (parentDirectory === currentDirectory) {
                return fallback;
            }

            var publicDirectory = parentDirectory + '/public';
            if (fs.existsSync(publicDirectory)) {
                return publicDirectory;
            }

            var webDirectory = parentDirectory + '/web';
            if (fs.existsSync(webDirectory)) {
                return webDirectory;
            }

            return findPublicDirectory(parentDirectory, fallback);
        };
        outputPath = findPublicDirectory(__dirname, __dirname + '../../web') + '/compiled/';
    }

    /**
     * Config
     * Reference: http://webpack.github.io/docs/configuration.html
     * This is the object where all configuration gets set
     */
    var config = {
        entry: options.entry,
        resolve: {
            alias: options.alias,
            extensions: ['', '.js', '.jsx'],
            modulesDirectories: ['node_modules', '']
        },

        output: {
            // Absolute output directory
            path: outputPath,

            // Output path from the view of the page
            publicPath: publicPath,

            // Filename for entry points
            // Only adds hash in build mode
            filename: BUILD ? '[name].[chunkhash].js' : '[name].bundle.js',

            // Filename for non-entry points
            // Only adds hash in build mode
            chunkFilename: BUILD ? '[name].[chunkhash].js' : '[name].bundle.js'
        }
    };


    /**
     * Loaders
     * Reference: http://webpack.github.io/docs/configuration.html#module-loaders
     * List: http://webpack.github.io/docs/list-of-loaders.html
     * This handles most of the magic responsible for converting modules
     */
    config.module = {
        preLoaders: [
            /**
             * Minify PNG, JPEG, GIF and SVG images with imagemin
             * Reference: https://github.com/tcoopman/image-webpack-loader
             *
             * See `config.imageWebpackLoader` for configuration options
             *
             * Query string is needed for URLs inside css files, like bootstrap
             */
            {
                test: /\.(gif|png|jpe?g|svg)(\?.*)?$/i,
                loader: 'image-webpack'
            }
        ],

        loaders: [
            /**
             * Compiles ES6 and ES7 into ES5 code
             * Reference: https://github.com/babel/babel-loader
             */
            {
                test: /\.jsx?$/i,
                loaders: ['babel'],
                exclude: /node_modules/
            },

            /**
             * Copy files to output directory
             * Rename the file using the asset hash
             * Pass along the updated reference to your code
             *
             * Reference: https://github.com/webpack/file-loader
             *
             * Query string is needed for URLs inside css files, like bootstrap
             * Overwrites name parameter to put original name in the destination filename, too
             */
            {
                test: /\.(png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot)(\?.*)?$/i,
                loader: 'file?name=[name].[hash].[ext]'
            },

            /**
             * Loads HTML files as strings inside JavaScript - can be used for templates
             *
             * Reference: https://github.com/webpack/raw-loader
             */
            {
                test: /\.html$/i,
                loader: 'raw'
            },

            /**
             * Allow loading CSS through JS
             * Reference: https://github.com/webpack/css-loader
             *
             * postcss: Postprocess your CSS with PostCSS plugins
             * Reference: https://github.com/postcss/postcss-loader
             * See config.postcss for more information
             *
             * ExtractTextPlugin: Extract CSS files into separate ones to load directly
             * Reference: https://github.com/webpack/extract-text-webpack-plugin
             *
             * If ExtractTextPlugin is disabled, use style loader
             * Reference: https://github.com/webpack/style-loader
             */
            {
                test: /\.css$/i,
                loader: ExtractTextPlugin.extract('style', 'css?sourceMap!postcss')
            },

            /**
             * Compile LESS to CSS, then use same rules
             * Reference: https://github.com/webpack-contrib/less-loader
             */
            {
                test: /\.less$/i,
                loader: ExtractTextPlugin.extract('style', 'css?sourceMap!postcss!less?sourceMap')
            },

            /**
             * Compile SASS to CSS, then use same rules
             * Reference: https://github.com/webpack-contrib/sass-loader
             */
            {
                test: /\.scss$/i,
                loader: ExtractTextPlugin.extract('style', 'css?sourceMap!postcss!sass?sourceMap')
            }
        ]
    };

    /**
     * Configuration for image-loader
     * Reference: https://github.com/tcoopman/image-webpack-loader
     */
    config.imageWebpackLoader = options.parameters.image_loader_options || {
        progressive: true,
        optimizationLevel: 7
    };

    /**
     * Add vendor prefixes to CSS
     *
     * Reference: https://github.com/postcss/autoprefixer
     */
    config.postcss = [
        autoprefixer({
            browsers: ['last 2 version']
        })
    ];

    /**
     * Plugins
     * Reference: http://webpack.github.io/docs/configuration.html#plugins
     * List: http://webpack.github.io/docs/list-of-plugins.html
     */
    config.plugins = [
        /**
         * Used for CSS files to extract from JavaScript
         * Reference: https://github.com/webpack/extract-text-webpack-plugin
         */
        new ExtractTextPlugin(
            BUILD ? '[name].[hash].css' : '[name].bundle.css',
            {
                disable: options.parameters.extract_css === false
            }
        ),

        /**
         * Webpack plugin that emits a json file with assets paths - used by the bundle
         * Reference: https://github.com/kossnocorp/assets-webpack-plugin
         */
        new AssetsPlugin({
            filename: path.basename(options.manifest_path),
            path: path.dirname(options.manifest_path)
        }),

        /**
         * Adds assets loaded with extract-file-loader as chunk files to be available in generated manifest
         * Used by the bundle to use binary files (like images) as entry-points
         * Reference: https://github.com/mariusbalcytis/extract-file-loader
         */
        new ExtractFilePlugin()
    ];

    /**
     * Adds CLI dashboard when compiling assets instead of the standard output
     * Reference: https://github.com/FormidableLabs/webpack-dashboard
     */
    if (DASHBOARD) {
        config.plugins.push(new DashboardPlugin());
    }

    /**
     * Build specific plugins - used only in production environment
     */
    if (BUILD) {
        config.plugins.push(
            /**
             * Only emit files when there are no errors
             * Reference: http://webpack.github.io/docs/list-of-plugins.html#noerrorsplugin
             */
            new webpack.NoErrorsPlugin(),

            /**
             * Dedupe modules in the output
             * Reference: http://webpack.github.io/docs/list-of-plugins.html#dedupeplugin
             */
            new webpack.optimize.DedupePlugin(),

            /**
             * Minify all javascript, switch loaders to minimizing mode
             * Reference: http://webpack.github.io/docs/list-of-plugins.html#uglifyjsplugin
             */
            new webpack.optimize.UglifyJsPlugin(),

            /**
             * Assign the module and chunk ids by occurrence count
             * Reference: https://webpack.github.io/docs/list-of-plugins.html#occurrenceorderplugin
             */
            new webpack.optimize.OccurenceOrderPlugin(true)
        );
    }

    /**
     * Devtool - type of sourcemap to use per build type
     * Reference: http://webpack.github.io/docs/configuration.html#devtool
     */
    if (BUILD) {
        config.devtool = 'source-map';
    } else {
        config.devtool = 'eval';
    }

    return config;
};
