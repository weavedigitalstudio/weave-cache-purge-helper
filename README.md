![Weave Cache Purge Helper](https://weave-hk-github.b-cdn.net/weave/plugin-header.png)
# Weave Cache Purge Helper

This is a fork of the Cache Purge Helper plugin updated and customised for in-house use by Weave Digital Studio and HumanKind Funeral Websites. 
It includes additional hooks for WordPress, ACF, WP-Umbrella and Beaver Builder to trigger NGINX Helper or LiteSpeed Cache plugin purges.

---

## Changes we've made from the original plugin

### Added
- Integration with ACF Options pages updates to trigger cache purges.
- Integration with WP-Umbrella to ensure proper cache clearing after plugin updates.
- Integration with WordPress REST API to purge caches when posts are created or updated through external applications or scripts.
- Proper cache clearing sequence (Beaver Builder first, then Nginx/LiteSpeed) to prevent 404 errors.
- **Automatic GitHub Updates**: The plugin now supports automatic updates via GitHub releases, appearing directly in the WordPress updates screen.

### Changed
- Logging prefix updated from `cphp` to `wcph`.
- Versioning starts at `1.0.0` to signify the fork.
- Optimised cache purging mechanism for improved admin performance.

### Removed / Not used
- Support for Elementor, Autoptimize, and Oxygen builders to streamline functionality for Weave Digital's specific in-house needs.
- All internationalisation functions as this is for our in-house use in English.

## Original Plugin
This fork is based on the Cache Purge Helper plugin by Paul Stoute, Jordan Trask, and Jeff Cleverley.

### Contributions
* Paul Stoute - [Stoute Web Solutions](https://stoutewebsolutions.com/)
* Jordan Trask - [GitHub](https://github.com/jordantrizz)
* Jeff Cleverley - [GridPane](https://gridpane.com)
* Gareth Bissland - [Weave Digital Studio](https://weave.co.nz) (Fork Author)

---

## Installation

### Installation from GitHub

When installing this plugin from GitHub:

1. Go to the [Releases](https://github.com/weavedigitalstudio/weave-cache-purge-helper/releases) page
2. Download the latest release ZIP file
3. In your WordPress admin panel, go to Plugins → Add New → Upload Plugin
4. Upload the ZIP file and activate the plugin

### Updates

The plugin now **automatically checks for updates** via GitHub releases.

- Updates will appear in **WordPress → Plugins → Updates**, just like regular WordPress plugins.
- No need to manually download updates from GitHub.
- The update process ensures that WordPress fetches the **correct release ZIP**.

If you prefer to update manually, you can still download the latest release from GitHub and follow the installation steps again.

---

## Logging

To enable detailed logging for cache purging events and for debugging, add the following to your wp-config.php:
Gridpane users can add this to your website user-configs.php instead.

```php
define( 'WC_PHP_DEBUG', true );
```

That's all you need! No other debug settings are required, though they may be used for other WordPress debugging purposes.

---

## Manual Testing

You can manually test the cache purging functionality with the following:

1. Make sure you have `define('WC_PHP_DEBUG', true);` in your wp-config.php
2. Log in as an administrator
3. Visit any page on your site with `?test_wcph_purge=1` added to the URL
```php
?test_wcph_purge=1
```
   - For example: `https://example.com/any-page/?test_wcph_purge=1`
4. You'll see a confirmation message that the cache purge was initiated
5. Check your debug log for the results

This test runs the complete cache purging process, allowing you to verify that all cache systems (WordPress, Beaver Builder, Nginx/LiteSpeed) are being properly cleared.

## Cache Clearing Order

This plugin ensures that caches are cleared in the correct order:
1. First: Beaver Builder cache is cleared
2. Second: Nginx Helper or LiteSpeed cache is cleared

This sequence prevents 404 errors that can occur when Nginx serves cached HTML that references old, non-existent Page Builder/Beaver Themer asset files.

---

## BunnyCDN Integration (Optional)

As of version 1.4.0, this plugin includes optional BunnyCDN clearing for sites using BunnyCDN as their content delivery network. This feature is disabled by default and only activates when configured via constants.

### Requirements

- A BunnyCDN account with an active pull zone
- Your BunnyCDN API key (found in your BunnyCDN dashboard under Account Settings)
- Your Pull Zone ID (found in your BunnyCDN dashboard)
- **No additional plugins required** - this integration is self-contained

### Configuration

To enable BunnyCDN cache purging, add these constants to your `wp-config.php` or GridPane `user-configs.php`:

```php
// BunnyCDN Integration
define('WCPH_BUNNY_ENABLED', true);
define('WCPH_BUNNY_API_KEY', 'your-bunnycdn-api-key');
define('WCPH_BUNNY_PULL_ZONE_ID', '123456'); // Your numeric pull zone ID
define('WCPH_BUNNY_CDN_URL', 'https://your-cdn.b-cdn.net'); // Your CDN URL (optional, for logging)
```

### How It Works

When BunnyCDN is enabled, the plugin adds a third cache layer to the purge sequence:
1. **First**: Beaver Builder cache is cleared
2. **Second**: Nginx Helper or LiteSpeed cache is cleared  
3. **Third**: BunnyCDN cache is purged via API (when enabled)

### Purging Strategy

- **Single Page Purge**: When editing with Beaver Builder, only the specific page URL is purged from BunnyCDN
- **Full Zone Purge**: System events (plugin updates, ACF options, WP-Umbrella updates) trigger a complete CDN purge
- **REST API**: Posts updated via REST API trigger individual URL purges

### Testing BunnyCDN Integration

Once configured, you can test the BunnyCDN purging using the manual test function:
1. Ensure `define('WC_PHP_DEBUG', true);` is set
2. Visit any page with `?test_wcph_purge=1` added to the URL
3. Check your debug log for "BunnyCDN full purge successful" message

See the [Manual Testing](#manual-testing) section above for full details.

### Important Notes

- This feature only affects sites with the constants defined (~3% of our sites)
- Sites without BunnyCDN constants continue to work exactly as before
- The integration follows GridPane's [BunnyCDN setup guide](https://gridpane.com/kb/how-to-set-up-bunny-cdn/)
- Works alongside CDN URL rewriting plugins like Perfmatters
- Does NOT require any additional BunnyCDN plugins

---

## REST API Support

This plugin automatically clears caches when content is created or updated through the WordPress REST API via the `rest_after_insert_post` hook. This ensures that:

1. External applications using the REST API to manage content (like Google Sheets integration)
2. Headless WordPress implementations
3. Custom scripts or automation tools

All trigger the same complete cache purging sequence as traditional WordPress admin edits. This is crucial for maintaining site performance and preventing stale content when using automation or external content management tools.

---

### Fork Information
* Original Plugin URI: [Cache Purge Helper on GitHub](https://github.com/managingwp/cache-purge-helper)
* Author of this Fork: Gareth Bissland - [GitHub](https://github.com/gbissland)

### Changelog
For detailed changes, see the [CHANGELOG.md](CHANGELOG.md) file.
