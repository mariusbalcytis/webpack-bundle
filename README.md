Bundle to Integrate Webpack into Symfony
====

Symfony bundle to help integrating [webpack](https://webpack.github.io/) into Symfony project.

What is webpack?
----

Module bundler and CommonJS / AMD dependency manager.

For me, it replaces both grunt/gulp and RequireJS.

See [what is webpack?](http://webpack.github.io/docs/what-is-webpack.html)
and [it's documentation](http://webpack.github.io/docs/) for more information.

What does this bundle do?
----

1. Finds javascript entry points inside your twig templates.
2. Runs webpack with [assets-webpack-plugin](https://github.com/sporto/assets-webpack-plugin).
3. Saves generated file names, so that twig function returns correct URL to generated asset.

Additionally, for development environment:

1. Runs [webpack-dev-server](https://webpack.github.io/docs/webpack-dev-server.html), which serves and regenerates assets if they are changed.
2. Watches twig templates for changes, updates entry points and
restarts webpack-dev-server if webpack configuration changes.

More goodies:
1. Lets you configure webpack config as you want, while still providing needed parameters from Symfony, like
entry points, aliases, environment and additional parameters.
2. Lets you define custom entry point providers if you don't use twig or include scripts in any other way.

Also look at [MabaWebpackMigrationBundle](https://github.com/mariusbalcytis/webpack-migration-bundle) for 
easier migration from [AsseticBundle](https://github.com/symfony/assetic-bundle) to webpack.

How does this compare to assetic?
----

Webpack lets you create components, which know their own dependencies.

With assetic, you must explicitly provide all needed javascript and stylesheet files in your templates.
If you split one of your javascript files into two files, you need to update all templates where that new dependency
is required. With webpack, your could just `require('newFile.js');` inside the javascript file and you're done.

Moreover, from javascript your can require CSS files as easily as other javascripts - `require('styles.css');`
and you're set to go.

If your application is frontend-based, sooner or later you're gonna need to load your assets asynchronously.
This comes by default in webpack. Assetic just bundles the assets, you need to use library like RequireJS
to do that (for example, you can look at [HearsayRequireJSBundle](https://github.com/hearsayit/HearsayRequireJSBundle)
as an alternative).

webpack-dev-server supports hot-reload of your files, sometimes without page refresh
(perfect for styling and some JS frameworks).

Installation
----

```shell
composer require maba/webpack-bundle
```

Inside `AppKernel`:

```php
new Maba\Bundle\WebpackBundle\MabaWebpackBundle(),
```

Run command:

```bash
app/console maba:webpack:setup
```

It copies default `webpack.config.js` and `package.json` files and runs `npm install`.

If any of the files already exists, you'll be asked if you'd like to overwrite them.

`webpack.config.js` must export a function that takes `options` as an argument and returns webpack config.

Default configuration was based on
[Foxandxss/angular-webpack-workflow](https://github.com/Foxandxss/angular-webpack-workflow).
Some customizations were made for integration. Also `TEST` mode was removed as at this point bundle does not
let running javascript tests.

Empty string was added to module directories, this allows to 1) require files without
prefixing them with `./` 2) if file is not found inside same directory, it's searched in descendant directories.

Feel free to modify this configuration file as you'd like - bundle just provides default one as a starting point.

You should add `webpack.config.js` and `package.json` into your repository. You should also add `node_modules` into
`.gitignore` file and run `npm install` similarly to `composer install` (after cloning repository, after `package.json`
is updated and as a task in your deployment). Of course, you could just add it to your repository, too.

```bash
git add package.json app/config/webpack.config.js
```

After updating this bundle, you should re-run setup command and review changes in files, merge them with any of
your own. This bundle **might** make an assumption that all the needed dependencies are installed. As compiling
is made beforehand as a deployment step, you should notice any errors in your staging environment if there would be any.

Usage
----

Inside twig templates:

```twig
<script src="{{ webpack_asset('@ApplicationBundle/Resources/assets/script.js') }}"></script>
```

Inside `script.js`:

```js
require('./script2.js');

function loadScript3() {
    require.ensure([], function() {
        require('@AnotherBundle/Resources/assets/script3.js');
        require('style.css');
    });
}
```

In development environment (this must always run in the background, similar to `assetic:watch`):

```bash
app/console maba:webpack:dev-server
```

This runs webpack-dev-server as a separate process, it listens on `localhost:8080`. By default, assets in development
environment are pointed to `http://localhost:8080/compiled/*`. If you run this command inside VM, docker container etc.,
configure `maba_webpack.config.parameters.public_path` to use correct host in your `config_dev.yml`.

As part of deployment into production environment:

```bash
app/console maba:webpack:compile --env=prod
```

Aliases
----

By default, these aliases are registered:

- `@app`, which points to `%kernel.root_dir%/Resources/assets`
- `@root`, which points to `%kernel.root_dir%/..` (usually the root of your repository)
- `@AcmeHelloBundle` or similar for each of your bundles. This points to root of the bundle (where `Bundle` class is), same as when locating resource in Symfony itself
- `@acme_hello` or similar for each of your bundles. This points to `@AcmeHelloBundle/Resources/assets` by default.

You can also register your own aliases, for example `@bower` or `@npm`
would be great candidates if you use any of those package managers. Or something like `@vendor`
if you use composer to install your frontend assets.

Aliases work the same in both twig templates (parameter to `webpack_asset` function) and Javascript files
(parameter to `require` or similar Webpack provided function).

Configuration
----

See example with explanations.

```yml
maba_webpack:
    # this configures providers which gives all entry points
    # you can create your own type if you need to provide entry points in any other way
    asset_providers:    # if you overwrite this, be sure to explicitly provide needed configuration like all bundles
        -
            type:                 twig_bundles  # analyses twig templates inside given bundles
            resource:             [ApplicationBundle, AnyOtherBundle] # all by default
        -
            type:                 twig_directory # analyses twig templates inside given directory
            resource:             %kernel.root_dir%/Resources/views
    twig:
        function_name:        webpack_asset     # function name in twig templates
        suppress_errors:      true              # whether files not found or twig parse errors should be ignored
                                                # defaults to true in dev environment
    config:
        path:                 '%kernel.root_dir%/config/webpack.config.js'
        parameters:           []        # additional parameters passed to webpack config file
                                        # for example, set public_path to overwrite
                                            # http://localhost:8080/compiled/ in dev environment
                                            # see inside your webpack.config.js for more info
    aliases:                            # allows to set aliases inside require() in your JS files
        register_bundles:               # defaults to all bundles
            - ApplicationBundle
            - AnyOtherBundle
        path_in_bundle:       /Resources/assets     # this means that require('@acme_hello/a.js')
                                                    # will include something like
                                                    # src/Acme/Bundles/AcmeHelloBundle/Resources/assets/a.js
                                                    # see "Aliases" for more information
        additional:           []            # provide any other aliases, prefix (@) is always added automatically
    bin:
        webpack:
            executable: # how maba:webpack:compile executes webpack
                        # should be array, for example [webpack]
                - node
                - node_modules/webpack/bin/webpack.js
            arguments:            []    # additional parameters to pass to webpack
                                        # --config with configuration path is always passed
        dev_server:
            executable: # how maba:webpack:dev-server executes webpack-dev-server
                - node
                - node_modules/webpack-dev-server/bin/webpack-dev-server.js
            arguments:  # additional parameters to pass to webpack-dev-server; these are default ones
                - --hot
                - --history-api-fallback
                - --inline
                
```

Loading CSS with stylesheets
----

By default, CSS is loaded and applied from inside your javascript code. There are no additional requests to the server,
but if you want to load CSS instantly from `<link>` tag, enable `ExtractTextPlugin`:

```yml
maba_webpack:
    config:
        parameters:
            extract_css: true
```

In your twig template:

```
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    {% set cssUrl = webpack_asset('@ApplicationBundle/Resources/assets/main.js', 'css') %}
    {% if cssUrl %}
        <link rel="stylesheet" href="{{ cssUrl }}"/>
    {% endif %}
</head>
<body>
    <script src="{{ webpack_asset('@ApplicationBundle/Resources/assets/main.js') }}"></script>
</body>
</html>
```

Normally you would only need to load `<script>` tag and all `require()`d styles would be loaded automatically.

ES6, Less and Sass support
----
ES6, Less and Sass works out of the box:

- use `.js` or `.jsx` extension to compile from ES6 and ES7 to ES5 using [Babel](https://babeljs.io/);
- use `.less` extension to compile [Less](http://lesscss.org/) files;
- use `.scss` extension to compile [Sass](http://sass-lang.com/) files.

If you need any custom loaders, feel free to install them via `npm` and modify `app/config/webpack.config.js` if needed.

Alternatives?
----

There are a few alternatives out there for integrating webpack.

### Plain simple webpack commands

I would really recommend this approach - just split your frontend code (HTML, CSS, JS) from
backend code (PHP + some HTTP API).

Especially if you have single-page application, there is really not much point in integrating your webpack
workflow with Symfony.

In this case you could use [html-webpack-plugin](https://github.com/ampedandwired/html-webpack-plugin) to generate HTML with correct URL to your bundled javascript file.

### [ju1ius/WebpackAssetsBundle](https://github.com/ju1ius/WebpackAssetsBundle)

This is minimal bundle to provide compiled file names into your twig templates.
It also uses [assets-webpack-plugin](https://github.com/sporto/assets-webpack-plugin).

What it's missing when compared to this bundle - gathering all entry points. You must manually set them inside your webpack config.
If there are only few of them, this is usually not that hard to maintain.

Webpack config is completely manual and does not integrate with your Symfony application.

### [hostnet/webpack-bundle](https://github.com/hostnet/webpack-bundle)

This bundle gathers all entry points, but does not provide generated URLs to use inside your twig templates.
It does solve this problem by appending modification timestamp for cache busting.

Webpack config is completely integrated into your Symfony application and requires custom PHP code for
any custom configuration changes.

In development environment, it generates assets on request. This can be more convenient than having
to run specific command in the background, but is usually slower. Also at this point it's kind of hard
to integrate it with webpack-dev-server.

It has more tags for twig, like inline scripts or list of scripts to load.

## Running tests

[![Travis status](https://travis-ci.org/mariusbalcytis/webpack-bundle.svg?branch=master)](https://travis-ci.org/mariusbalcytis/webpack-bundle)

```shell
composer install
vendor/bin/codecept run
```
