Craft CMS Plugin Installer for Composer
=======================================

This is the Composer installer for [Craft CMS](https://craftcms.com/) plugins. It implements a new Composer package type named `craft-plugin`, which should be used by all Craft CMS plugins if they are distributed as Composer packages.

Usage
-----

To create a Craft CMS plugin that can be installed with Composer, set the `type` property in your plugin’s composer.json file to `"craft-plugin"`.

```json
{
  "type": "craft-plugin",
}
```

The following properties can also be placed within the `extra` array:

- `class` – The primary Plugin class name. If not set, the installer will look for a Plugin.php file at each of the `autoload` path roots.
- `basePath` – The base path to your plugin’s source files. This can begin with one of your `autoload` namespaces formatted as a [Yii alias](http://www.yiiframework.com/doc-2.0/guide-concept-aliases.html) (e.g. `@vendorname/foo`). If not set, the directory that contains your primary Plugin class will be used.    
- `name` – The plugin name. If not set, the package name (sans vendor prefix) will be used.
- `handle` – The plugin handle. If not set, the package name (sans vendor prefix) will be used.
- `version` - The plugin version. If not set, the current package version will be used.
- `schemaVersion` – The plugin schema version.
- `description` – The plugin description. If not set, the main `description` property will be used.
- `developer` – The developer name. If not set, the first author’s `name` will be used (via the `authors` property).
- `developerUrl` – The developer URL. If not set, the `homepage` property will be used, or toe first author’s `homepage` (via the `authors` property).
- `documentationUrl` – The plugin’s documentation URL. If not set, the `support.docs` property will be used.
- `components` – Object defining any [component configs](http://www.yiiframework.com/doc-2.0/guide-structure-application-components.html) that should be present on the plugin.

Complete Example
----------------

Here’s what a plugin’s complete composer.json file might look like:

```json
{
  "name": "pixelandtonic/foo",
  "description": "Foo plugin for Craft CMS",
  "type": "craft-plugin",
  "license": "MIT",
  "minimum-stability": "stable",
  "support": {
    "docs": "https://pixelandtonic.com/foo/docs"
  },
  "require": {
    "craftcms/cms": "^3.0.0-alpha.1"
  },
  "autoload": {
    "psr-4": {
      "pixelandtonic\\foo\\": "src/"
    }
  },
  "extra": {
    "name": "Foo",
    "developer": "Pixel & Tonic",
    "developerUrl": "https://pixelandtonic.com/"
  }
}
```

In that example,

- `class` will be `pixelandtonic\foo\Plugin` per the `autoload` property (assuming that a src/Plugin.php file exists).
- `basePath` will be `path/to/vendor/pixelandtonic/foo/src` (the directory that contains the `pixelandtonic\foo\Plugin` class).
- `name` will be `Foo`, per the `extra.name` property.
- `handle` will be `foo`, per the `name` property.
- `version` will be whatever the current package version is.
- `description` will be `Foo plugin for Craft CMS` per the `description` property.
- `developer` will be `Pixel & Tonic`, per the `extra.developer` property.
- `developerUrl` will be `https://pixelandtonic.com/`, per the `extra.developerUrl` property.
- `documentationUrl` will be `https://pixelandtonic.com/foo/docs`, per the `support.docs` property.
