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
