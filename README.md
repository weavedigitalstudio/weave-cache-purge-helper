![Weave Cache Purge Helper](https://weave-hk-github.b-cdn.net/weave/plugin-header.png)
# Weave Cache Purge Helper

This is a fork of the Cache Purge Helper plugin, tailored for use by Weave Digital on GridPane. It includes additional hooks for Beaver Builder, ACF, and WP-Umbrella to trigger NGINX Helper and LiteSpeed Cache plugin purges.

## Changes from Original Plugin

### Added
- Integration with ACF Options pages updates to trigger cache purges.
- Integration with WP-Umbrella to ensure proper cache clearing after plugin updates.
- Integration with WordPress REST API to purge caches when posts are created or updated through external applications or scripts.
- Proper cache clearing sequence (Beaver Builder first, then Nginx/LiteSpeed) to prevent 404 errors.

### Changed
- Logging prefix updated from `cphp` to `wcph`.
- Versioning starts at `1.0.0` to signify the fork.

### Removed
- Support for Elementor, Autoptimize, and Oxygen builders to streamline functionality for Weave Digital's specific needs and Beaver Builder.

## Original Plugin
This fork is based on the Cache Purge Helper plugin by Paul Stoute, Jordan Trask, and Jeff Cleverley.

### Contributions
* Paul Stoute - [Stoute Web Solutions](https://stoutewebsolutions.com/)
* Jordan Trask - [GitHub](https://github.com/jordantrizz)
* Jeff Cleverley - [GridPane](https://gridpane.com)
* Gareth Bissland - [Weave Digital Studio](https://weave.co.nz) (Fork Author)

## Installation

### Installation from GitHub

When installing this plugin from GitHub:

1. Go to the [Releases](https://github.com/weavedigitalstudio/weave-cache-purge-helper/releases) page
2. Download the latest release ZIP file (e.g., `weave-cache-purge-helper-1.2.0.zip`)
3. In your WordPress admin panel, go to Plugins → Add New → Upload Plugin
4. Upload the ZIP file and activate the plugin

### Manual Installation

If you prefer to manually install:

1. Download the source code from the repository
2. Extract the files on your computer 
3. Rename the folder to `weave-cache-purge-helper` if needed
4. Upload the folder to your `/wp-content/plugins/` directory via FTP
5. Activate the plugin through the WordPress 'Plugins' menu

### Updates

To update the plugin, download the latest release from GitHub and follow the installation steps again. The new version will replace the existing plugin.


## Logging

Enable PHP error_log for WP:

```
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false ); // Optional
define( 'WC_PHP_DEBUG', true ); // Custom logging
```

### For Gridpane Hosting

Enable Secure WP Debug via toggle switch then add   `define( 'WC_PHP_DEBUG', true );`   to your website user-configs.php

## Cache Clearing Order

This plugin ensures that caches are cleared in the correct order:
1. First: Beaver Builder cache is cleared
2. Second: Nginx Helper or LiteSpeed cache is cleared

This sequence prevents 404 errors that can occur when Nginx serves cached HTML that references old, non-existent Beaver Builder asset files.

## REST API Support

This plugin automatically clears caches when content is created or updated through the WordPress REST API. This ensures that:

1. External applications using the REST API to manage content (like Google Sheets integration)
2. Headless WordPress implementations
3. Custom scripts or automation tools

All trigger the same complete cache purging sequence as traditional WordPress admin edits. This is crucial for maintaining site performance and preventing stale content when using automation or external content management tools.

## Fork Information
* Original Plugin URI: [Cache Purge Helper on GitHub](https://github.com/managingwp/cache-purge-helper)
* Author of the Fork: Gareth Bissland - [GitHub](https://github.com/gbissland)

### Note
For detailed changes, see the `CHANGELOG.md` file.


