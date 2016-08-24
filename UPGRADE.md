# Upgrade from 0.4 to 0.5

## TTY prefix

`bin.webpack.tty_prefix` and `bin.dev_server.tty_prefix` were added with
`['node', 'node_modules/webpack-dashboard/bin/webpack-dashboard.js', '--']`
default value.

Both `package.json` and `webpack.config.js` were modified for this to work, so either:
 - run `maba:webpack:setup`, replace files and merge changes, then run `npm install`
 (you should always do this anyways after updating this bundle)
 - set both `bin.webpack.tty_prefix` and `bin.dev_server.tty_prefix` to `[]` to disable NASA-like dashboard
 when compiling assets
 