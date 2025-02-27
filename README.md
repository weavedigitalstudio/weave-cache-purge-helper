# Weave Cache Purge Helper

This is a fork of the Cache Purge Helper plugin, tailored for use by Weave Digital on GridPane. It includes additional hooks for Beaver Builder, ACF, and WP-Umbrella to trigger NGINX Helper and LiteSpeed Cache plugin purges.

## Changes from Original Plugin

### Added
- Integration with ACF Options pages updates to trigger cache purges.
- Integration with WP-Umbrella to ensure proper cache clearing after plugin updates.
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

## Installation from GitHub

When installing this plugin from GitHub:

1. Go to the [Releases](https://github.com/weavedigitalstudio/weave-cache-purge-helper/releases) page
2. Download the latest release ZIP file
3. Extract the ZIP file on your computer
4. Rename the extracted folder to remove the version number  
   (e.g., from `weave-cache-purge-helper-1.1.0` to `weave-cache-purge-helper`)
5. Create a new ZIP file from the renamed folder
6. In your WordPress admin panel, go to Plugins → Add New → Upload Plugin
7. Upload your new ZIP file and activate the plugin

**Note**: The folder renaming step is necessary for WordPress to properly handle plugin updates and functionality.

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

## Fork Information
* Original Plugin URI: [Cache Purge Helper on GitHub](https://github.com/managingwp/cache-purge-helper)
* Author of the Fork: Gareth Bissland - [GitHub](https://github.com/gbissland)

### Note
For detailed changes, see the `CHANGELOG.md` file.


