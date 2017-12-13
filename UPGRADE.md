# Upgrade from 0.6 to 1.0

## Directory structure changes

Default location of `webpack.config.js` changed to be compatible with new Symfony 4 structure. It's now stored
in `config` folder of your project directory.

To keep previous behaviour, provide this configuration:

```yaml
maba_webpack:
    config:
        path:
          %kernel.root_dir%/config/webpack.config.js
```

Also, to support `/public` directory in Symfony4 out of the box, default `webpack.config.js` scans parent directories
until it finds either `/public` or `/web` directory and uses that for dumping assets into.

## Bundle inheritance unsupported

If bundle inheritance is used, assets will be searched only in the first bundle.
Configure additional assets directory for extended bundles if needed.

This change was done to be compatible with Symfony4, which drops support for bundle inheritance.

# Upgrade from 0.5 to 0.6

## Configuration changes

Configuration of the bundle was changed so that it would be more clear
and semantic:
- `twig.function_name` was removed and is always `webpack_asset`;
- `asset_providers` was totally removed, use the following instead:
  - `enabled_bundles` to parse twig templates only in some bundles that you
  need;
  - `twig.additional_directories` to parse twig templates in additional provided
  directories. `%kernel.root_dir%/Resources/views` is always parsed if it exists;
  - custom asset providers does not need additional configuration and are always
  called;
- `aliases.register_bundles` was removed - it's now the same as `enabled_bundles`
- `bin.webpack.tty_prefix` and `bin.dev_server.tty_prefix` were removed and
`dashboard` configuration node added instead. This makes the bundle aware of
`WebpackDashboard` explicitly, so now it passes `WEBPACK_DASHBOARD` environment
variable into `webpack.config.js` (with value `enabled`) if dashboard is used,
`TTY_MODE` is no passed.
This also allows extending functionality if some other changes to dashboard
would be needed;
- default `webpack.config.js` configuration extracts CSS by default, so if you
had `config.parameters.extract_css` set to `true`, you could just remove it.
If it was not set and you do not need extracting CSS, set `config.parameters.extract_css`
to `false`;
- default executables were changed from `[node, node_modules/package/bin/package.js]`
into `[node_modules/.bin/package]` - this should be backwards compatible.

So, if you had this configuration:
```yml
maba_webpack:
    asset_providers:
        -
            type:     twig_bundles
            resource: [ApplicationBundle]
        -
            type:     twig_directory
            resource: %kernel.root_dir%/Resources/views
        -
            type:     twig_directory
            resource: %kernel.root_dir%/Resources/other-directory
    aliases:                            # allows to set aliases inside require() in your JS files
        register_bundles:               # defaults to all bundles
            - ApplicationBundle
    bin:
        webpack:
            tty_prefix: [/my/path/to/node, node_modules/webpack-dashboard/bin/webpack-dashboard.js]
        dev_server:
            tty_prefix: [/my/path/to/node, node_modules/webpack-dashboard/bin/webpack-dashboard.js]
```

Now it would be like this:
```yml
maba_webpack:
    enabled_bundles:
        - ApplicationBundle
    twig:
        additional_directories:
            - %kernel.root_dir%/Resources/other-directory
    config:
        parameters:
            extract_css: false
    dashboard:
        enabled: always
        executable: [/my/path/to/node, node_modules/webpack-dashboard/bin/webpack-dashboard.js]
```

## Changed twig tags

Removed `webpack_javascript`, `webpack_stylesheets` and `webpack_assets` twig
tags - replace them with `webpack` tag and use `js` or `css` token if needed.

Change this:
```twig
{% webpack_javascripts '@app/a.js' '@app/b.js' %}
<script src="{{ asset_url }}"></script>
{% end_webpack_javascripts %}

{% webpack_stylesheets '@app/a.js' '@app/b.js' %}
    <link rel="stylesheet" href="{{ asset_url }}"/>
{% end_webpack_stylesheets %}

{% webpack_assets '@app/a.png' '@app/b.png' %}
<img src="{{ asset_url }}"/>
{% end_webpack_assets %}
```

To this:
```twig
{% webpack js '@app/a.js' '@app/b.js' %}
<script src="{{ asset_url }}"></script>
{% end_webpack %}

{% webpack css '@app/a.js' '@app/b.js' %}
    <link rel="stylesheet" href="{{ asset_url }}"/>
{% end_webpack %}

{% webpack '@app/a.png' '@app/b.png' %}
<img src="{{ asset_url }}"/>
{% end_webpack %}
```

This allows extending functionality (like adding `named` for commons chunk assets)
and still using single tag.

## Migrating to Webpack 2

This bundle was already compatible with Webpack 2 if custom `webpack.config.js` was used - 
this configuration and your assets themselves are affected by Webpack version.

From this version, default `webpack.config.js` was added for Webpack 2 -
`maba:webpack:setup` command installs Webpack 2 by default.

If you want to keep Webpack 1, update configuration file by providing command line option:
```bash
app/console maba:webpack:setup --useWebpackV1
```

Configuration itself kept all the loaders and plugins as they were in previous version.
One notable (and backwards incompatible) change is that `''` is no longer in `resolve.modules`.
Webpack just does not allow it anymore. This affects your codebase if you `require`d relative
assets without `./` or `../` prefix.

If you have something like this:
```js
require('asset1.js');   // asset1.js is in the same directory
require('styles.css');  // styles.css is in parent directory
```

You'll have to provide relative paths explicitly:

```js
require('./asset1.js');
require('../styles.css');
```

## Asset providers refactored

Asset provider functionality was refactored - this is important only
if you implemented `AssetProviderInterface` or `CollectionResourceInterface`
in your code. Changes:
- changed `AssetResult` class - collection of assets are now `AssetItem`
instances instead of strings
- changed `AssetProviderInterface::getAssets` - it does not take `$resource`
 as an argument anymore
- removed `CollectionResourceInterface` interface
- if you implement `AssetProviderInterface` and tag service with
`maba_webpack.asset_provider`, no further configuration is needed in
`config.yml` - `getAssets` will be always called and given assets compiled.

# Upgrade from 0.4 to 0.5

## TTY prefix

`bin.dev_server.tty_prefix` was added with
`['node', 'node_modules/webpack-dashboard/bin/webpack-dashboard.js', '--']`
default value.

This requires changes in `package.json` and `webpack.config.js`.

You can set it to `[]` to disable NASA-like dashboard when compiling assets.
 
## AssetManager

Second parameter for `AssetManager::getAssetUrl` has been made optional.
If not provided, it is figured by configuration so that binary files could be loaded with `webpack_asset` twig function.

## Non-Javascript Entry File Support

Now you can use `webpack_asset` on other files than `.js` - for example, for images.

This requires changes in `package.json` and `webpack.config.js`.

You can disable this by setting `entry_file.enabled` to `false` in `config.yml`.

You should always do this procedure after updating this bundle:
- run `maba:webpack:setup`, replace files and merge changes
- run `npm install` (this should be done with `composer install` (and) as a step in deployment)

## Explicit API for semantic versioning

Some interfaces, classes and class methods were marked with `@api`.
Any other classes or methods can be changed or removed without MAJOR release bump.

Also most of the services marked as private for the same reason - you should only use those that
are public.

## Deprecated setting custom twig function

`maba_webpack.twig.function_name` configuration option is deprecated and will be removed in 0.6
