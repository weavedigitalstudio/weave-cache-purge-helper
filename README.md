![Weave Cache Purge Helper](https://weave-hk-github.b-cdn.net/weave/plugin-header.png)
# Weave Cache Purge Helper

This is a fork of the Cache Purge Helper plugin, tailored for in-house use by Weave Digital Studio and HumanKind Funeral Websites. It includes additional hooks for WordPress, ACF, WP-Umbrella and Beaver Builder to trigger NGINX Helper or LiteSpeed Cache plugin purges.

## Changes we've made from the original plugin

### Added
- Integration with ACF Options pages updates to trigger cache purges.
- Integration with WP-Umbrella to ensure proper cache clearing after plugin updates.
- Integration with WordPress REST API to purge caches when posts are created or updated through external applications or scripts.
- Proper cache clearing sequence (Beaver Builder first, then Nginx/LiteSpeed) to prevent 404 errors.

### Changed
- Logging prefix updated from `cphp` to `wcph`.
- Versioning starts at `1.0.0` to signify the fork.

### Removed
- Support for Elementor, Autoptimize, and Oxygen builders to streamline functionality for Weave Digital's specific in-house needs.

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

To enable detailed logging for cache purging events and for debugging, add the following to your wp-config.php:
Gridpane users can add this to your website user-configs.php instead.

```php
define( 'WC_PHP_DEBUG', true );
```

That's all you need! No other debug settings are required, though they may be used for other WordPress debugging purposes.


## Manual Testing

Once logging. You can manually test the cache purging functionality with the following:

1. Make sure you have `define('WC_PHP_DEBUG', true);` in your wp-config.php
2. Log in as an administrator
3. Visit any page on your site with `?test_wcph_purge=1` added to the URL
   - For example: `https://example.com/any-page/?test_wcph_purge=1`
4. You'll see a confirmation message that the cache purge was initiated
5. Check your debug log for the results

This test runs the complete cache purging process, allowing you to verify that all cache systems (WordPress, Beaver Builder, Nginx/LiteSpeed) are being properly cleared.

## Cache Clearing Order

This plugin ensures that caches are cleared in the correct order:
1. First: Beaver Builder cache is cleared
2. Second: Nginx Helper or LiteSpeed cache is cleared

NOTE: This sequence prevents 404 errors that can occur when Nginx serves cached HTML that references old, non-existent Page Builder/Beaver Themer asset files.

## REST API Support

This plugin automatically clears caches when content (including just meta fields) are created or updated through the WordPress REST API. This ensures that:

1. External applications using the REST API to manage content (like Google Sheets integration)
2. Headless WordPress implementations
3. Custom scripts or automation tools

All trigger the same complete cache purging sequence as traditional WordPress admin edits. This is crucial for maintaining site performance and preventing stale content when using automation or external content management tools.

## Fork Information
* Original Plugin URI: [Cache Purge Helper on GitHub](https://github.com/managingwp/cache-purge-helper)
* Author of this Fork: Gareth Bissland - [GitHub](https://github.com/gbissland)

### Note
For detailed changes, see the [CHANGELOG.md](CHANGELOG.md) file.
