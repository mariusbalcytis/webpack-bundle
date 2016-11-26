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
3. Works with images and css/less/sass files out-of-the-box, if needed.

Look at [Symfony, Webpack and AngularJS Single Page Application Demo](https://github.com/mariusbalcytis/symfony-webpack-angular-demo)
for usage examples.

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

As part of deployment into production environment:

```bash
app/console maba:webpack:compile --env=prod
```

In development environment (this must always run in the background, similar to `assetic:watch`):

```bash
app/console maba:webpack:dev-server
```

Alternatively, if you are not actively developing your frontend, you can compile once and
forget about it, similarly to production environment:

```bash
app/console maba:webpack:compile
```

Aliases
----

Aliases are prefixed with `@` and point to some specific path.

Aliases work the same in both twig templates (parameter to `webpack_asset` function) and Javascript files
(parameter to `require` or similar Webpack provided function).

By default, these aliases are registered:

- `@app`, which points to `%kernel.root_dir%/Resources/assets`
- `@root`, which points to `%kernel.root_dir%/..` (usually the root of your repository)
- `@AcmeHelloBundle` or similar for each of your bundles. This points to root of the bundle (where `Bundle` class is), same as when locating resource in Symfony itself
- `@acme_hello` or similar for each of your bundles. This points to `@AcmeHelloBundle/Resources/assets` by default.

You can also register your own aliases, for example `@bower` or `@npm`
would be great candidates if you use any of those package managers. Or something like `@vendor`
if you use composer to install your frontend assets:

```yml
maba_webpack:
    aliases:
        additional:
            npm: %kernel.root_dir%/node_modules     # or any other path where assets are installed
            bower: %kernel.root_dir%/bower
            vendor: %kernel.root_dir%/../vendor
```

Inside your JavaScript files:

```js
var $ = require('@npm/jquery');
```

Be sure to install dependencies (either npm, bower or any other) on path not directly accessible from web.
This is not needed by webpack (it compiles them - they can be anywhere on the system) and could cause a security
flaw (some assets contain backend examples, which could be potentially used in your production environment).

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
        suppress_errors:      true              # whether files not found or twig parse errors should be ignored
                                                # defaults to true in dev environment
                                                # defaults to "ignore_unkwowns" in prod - this option ignores
                                                #     unknown functions etc., but fails on syntax errors
                                                # set to false to always fail on any twig error
    config:
        path:                 '%kernel.root_dir%/config/webpack.config.js'
        parameters:           []        # additional parameters passed to webpack config file
                                        # for example, set dev_server_public_path and public_path to overwrite
                                            # http://localhost:8080/compiled/ and /compiled/
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
            tty_prefix: []              # prefix for command, only in TTY mode
            arguments:            []    # additional parameters to pass to webpack
                                        # --config with configuration path is always passed
        dev_server:
            executable: # how maba:webpack:dev-server executes webpack-dev-server
                - node
                - node_modules/webpack-dev-server/bin/webpack-dev-server.js
            tty_prefix: # prefix for command, only in TTY mode. Set to [] to disable dashboard
                - node
                - node_modules/webpack-dashboard/bin/webpack-dashboard.js
                - "--"
            arguments:  # additional parameters to pass to webpack-dev-server; these are default ones
                - --hot
                - --history-api-fallback
                - --inline
        disable_tty: false      # disables TTY setting. Defaults to false in dev environment, true in others.
                                # TTY is needed to run dashboard and/or to display colors, but does not work
                                # in some environments like AWS
                
```

## Configuring dev-server

`app/console maba:webpack:dev-server` runs webpack-dev-server as a separate process,
it listens on `localhost:8080`. By default, assets in development
environment are pointed to `http://localhost:8080/compiled/*`.

If you run this command inside VM, docker container etc., configure
`maba_webpack.config.parameters.dev_server_public_path` to use correct host. Also, as
dev-server listens only to localhost connections by default, add this to configuration:

```yml
maba_webpack:
    bin:
        dev_server:
            arguments:
                - --hot                     # these are default arguments - leave them if needed
                - --history-api-fallback
                - --inline
                - --host                    # let's add host option
                - 0.0.0.0                   # each line is escaped, so option comes in it's own line
```

If you need to provide different port, be sure to put `--port` and the port itself into separate lines.

When compiling assets with `webpack-dev-server`, [webpack-dashboard](https://github.com/FormidableLabs/webpack-dashboard)
is used for more user-friendly experience. You can disable it by setting `tty_prefix` option to `[]`.
You can also remove `DashboardPlugin` in such case from `webpack.config.js`.

## Configuring memory for Node.js

If you are experiencing "heap out of memory" error when running `maba:webpack:compile`
and/or `maba:webpack:dev-server`, try to give more memory for Node.js process:

```yml
maba_webpack:
    bin:
        webpack:        # same with dev_server
            executable:
                - node
                - "--max-old-space-size=4096"   # 4GB
                - node_modules/webpack/bin/webpack.js
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

This is also required if you want to include css/less/sass files directly by `webpack_asset` function.

In your twig template:

```twig
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

### `webpack_stylesheets` tag

To avoid setting CSS URL to a temporary variable, you can use `webpack_stylesheets` tag, similarly as in Assetic:

```twig
{% webpack_stylesheets '@ApplicationBundle/Resources/assets/main.js' %}
    <link rel="stylesheet" href="{{ asset_url }}"/>
{% end_webpack_stylesheets %}
```

You can provide more than one input file in this tag - they will not be merged together,
the code inside the tag will just be repeated with every generated asset.

There is also `webpack_javascripts` tag (use it only for javascript content) and 
`webpack_assets` tag (type is guessed for each asset, so might be used for images).

Keep in mind that you must provide hard-coded asset paths here, same as in `webpack_asset`
function. This is to find all available assets in compile-time.

ES6, Less and Sass support
----
ES6, Less and Sass works out of the box:

- use `.js` or `.jsx` extension to compile from ES6 and ES7 to ES5 using [Babel](https://babeljs.io/);
- use `.less` extension to compile [Less](http://lesscss.org/) files;
- use `.scss` extension to compile [Sass](http://sass-lang.com/) files.

If you need any custom loaders, feel free to install them via `npm` and modify `app/config/webpack.config.js` if needed.

Loading images
----
Images are optimized by default using [image-webpack-loader](https://github.com/tcoopman/image-webpack-loader).

You can include images directly into your twig templates by using the same `webpack_asset` function.

For this to work correctly, loader for image files must remain `file` in your webpack configuration.

```twig
<img src="{{ webpack_asset('@AcmeHelloBundle/Resources/images/cat.png') }}"/>
```

Of course, you can use them in your CSS, too:

```css
.cat {
    /* cat.png will be optimized and copied to compiled directory with hashed file name */
    /* URL to generated image file will be in the css output  */
    background: url("~@AcmeHelloBundle/Resources/images/cat.png")
}
```

If you are providing webpack-compatible asset path in CSS, prefix it with `~`. Use relative paths as usual.
See [css-loader](https://github.com/webpack/css-loader) for more information.

Semantic versioning
----

This bundle follows [semantic versioning](http://semver.org/spec/v2.0.0.html).

Public API of this bundle (in other words, you should only use these features if you want to easily update
to new versions):
- only services that are not marked as `public="false"`
- only classes, interfaces and class methods that are marked with `@api`
- twig functions
- console commands
- supported tags

For example, if only class method is marked with `@api`, you should not extend that class, as constructor
could change in any release.

See [Symfony BC rules](http://symfony.com/doc/current/contributing/code/bc.html) for basic information
about what can be changed and what not in the API. Keep in mind, that in this bundle everything is
`@internal` by default.

After updating this bundle, you should re-run `maba:webpack:setup` command and review changes in files,
merge them with any of your own. This bundle **might** make an assumption that all the needed dependencies
are installed. As compiling is made beforehand as a deployment step, you should notice any errors in
your staging environment if there would be any.

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

It has more tags for twig, like inline scripts or list of scripts to load. One example is inline splitpoint
generation which allows you to generate simple splitpoints per file.

## Running tests

[![Travis status](https://travis-ci.org/mariusbalcytis/webpack-bundle.svg?branch=master)](https://travis-ci.org/mariusbalcytis/webpack-bundle)

```shell
composer install
vendor/bin/codecept run
```
