# Weave Cache Purge Helper

This is a fork of the Cache Purge Helper plugin, tailored for use by Weave Digital. It includes additional hooks for Beaver Builder and ACF to trigger NGINX Helper and LiteSpeed Cache plugin purges.

## Changes from Original Plugin

### Added
- Integration ACF Options pages update to trigger cache purges.

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

### Manual Install
1. Extract the zip file.
2. Upload the extracted folder to the `/wp-content/plugins/` directory on your WordPress installation.
3. Activate the plugin from the Plugins page.

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


## Fork Information
* Original Plugin URI: [Cache Purge Helper on GitHub](https://github.com/managingwp/cache-purge-helper)
* Author of the Fork: Gareth Bissland - [GitHub](https://github.com/gbissland)

### Note
For detailed changes, see the `CHANGELOG.md` file.
