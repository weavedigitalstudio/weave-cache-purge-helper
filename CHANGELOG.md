# Changelog
## [1.5.0] - 2026-09-24
### Added
- Purge the whole page cache when a navigation menu, synced pattern, Global Styles or database template is saved. These show on every page, but Nginx Helper only purges the homepage and the item's own URL.

### Fixed
- The Nginx purge was skipped silently outside wp-admin (REST, front end, cron) because `is_plugin_active()` wasn't loaded there.

## [1.4.0] - 2026-09-18
### Added
- **Browser cache-busting for Beaver Builder layout files:** every file served from `uploads/bb-plugin/cache/` now carries `wcph=<file modified time>`, so a regenerated file gets a new URL. Beaver Builder versions these by the post's modified time, and servers cache uploads in the browser for about a year, so a file regenerated for any other reason stayed stale for returning visitors. Found on resilienceplatform.nz, where the mega menus (2.11 dynamic global rows, whose selectors carry a hash of the row's settings) rendered unstyled after a URL change. reBusted misses these files because Beaver Builder enqueues partial layout files while rendering, after reBusted has run; `style_loader_src` and `script_loader_src` catch them as they print.

### Fixed
- **LiteSpeed Cache was never purged:** the LiteSpeed branch tested `function_exists('litespeed_purge_all')`, but LiteSpeed Cache registers `litespeed_purge_all` as an action and defines no such function, so the test never passed. On OpenLiteSpeed sites every purge this plugin triggered on its own (ACF options saves, REST post saves including block editor saves, git-deploy postdeploy, WP Umbrella clears, the Test purge link) deleted all Beaver Builder layout files while LiteSpeed kept serving cached pages that pointed at them, for up to its 7-day TTL. Reproduced on resilienceplatform.nz (95 files deleted, page still a cache hit). It now tests `has_action()`, and runs independently of Nginx Helper, since a site cloned from nginx can carry both. Verified the fixed purge from WP-CLI: page cache purged, Beaver Builder files regenerated on the next request, all CSS 200. LiteSpeed Cache's own hooks (Beaver Builder saves, plugin updates) had been covering the common cases, which is why it went unnoticed.

### Note
- 1.3.10 was never released. Its nonce fix ships in this release.

## [1.3.10] - 2026-09-15
### Fixed
- **CSRF on the manual test purge:** `?test_wcph_purge=1` ran for any administrator whose browser was sent to it, from any site. It now requires a nonce, carried by a new **Test purge** link on the Plugins row. The hand-typed URL in the README is retired; the log output and the purge itself are unchanged.

## [1.3.9] - 2026-06-17
### Fixed
- **Logging:** The GitHub updater's "Current version / Latest version" debug line no longer fires on every admin request when `WP_DEBUG` is on. It's now gated behind the opt-in `WEAVE_UPDATER_DEBUG` constant, so it stays silent by default and no longer floods client error logs. Genuine API failure logs (request failed, bad response code, missing fields) are unchanged.

# [1.3.8] - 2025-06-23
### Fixed
- **WP-Umbrella Conflict:** Resolved fatal error caused by WP-Umbrella's buggy `GlobalNginx` class calling `purge_all()` on null object
- **Compatibility:** Implemented conflict prevention system to remove WP-Umbrella's cache handler and use our properly implemented version

### Changed
- **WordPress Compatibility:** Updated "Tested up to" version from 6.3 to 6.8.1
- **WP-Umbrella Integration:** Increased filter priority to 20 to ensure our cache compatibility overrides take precedence
- **Logging:** Added debug logging when WP-Umbrella cache compatibility conflicts are detected and resolved

## [1.3.7] - 2025-05-16
- **Performance:** Removed debouncing system to improve admin panel responsiveness
- **Note:** Direct cache purging is now used for all operations to reduce overhead

## [1.3.6] - 2025-04-04
- **Refactor:** Removed the general `updated_post_meta` hook to prevent excessive cache purging from plugin/theme meta updates.
- **Note:** Cache purging for specific meta fields updated via REST API should now be handled in the relevant custom plugin or theme.

## [1.3.5] - 2025-03-12
- **Improved performance:** Prevented unnecessary cache purges triggered by WordPress internal updates (_edit_lock, _edit_last)
- 
## [1.3.4] - 2025-03-08 - Auto updater
- Implemented **automatic GitHub updates** for the plugin.
- Now updates are detected via **GitHub releases**, allowing seamless plugin updates in WordPress.

## [1.3.2] - 2025-03-06 - Performance Update
- Added debounced cache purging to significantly improve performance during post updates
- Created separate mechanisms for immediate and delayed cache purging
- Optimised post save operations to reduce the redundant cache clearing. Useful with posts with meta fields.
- Added extra logging to track debounced cache operations.

## [1.3.1] - 2025-03-06
- Improved REST API support to better handle meta field updates as well
- Added manual testing functionality for easier cache purge verification
- Refined debug logging to reduce noise and focus on important events
- Removed dependency on WP_DEBUG for logging (now only requires WC_PHP_DEBUG)
- Fixed issue with REST API cache purging not triggering properly

## [1.3.0] - 2025-03-05
### Added
- WordPress REST API integration for cache purging
- Automatic cache clearing when posts are created or updated via the REST API
- Detailed logging for REST API operations with post type and ID information
- Support for external applications and automation tools that use the WordPress REST API

## [1.2.0] - 2025-02-27
### Added
- Integration with WP-Umbrella's cache system to properly clear caches after WP-Umbrella updates.
- Improved cache clearing sequence to prevent 404 errors (Beaver Builder first, then Nginx/LiteSpeed).
- Enhanced logging for WP-Umbrella triggered events.

### Changed
- Refactored cache clearing logic to ensure proper order of operations.
- Improved compatibility checks for both Nginx Helper and LiteSpeed Cache.

### Fixed
- Resolved 404 errors on Beaver Builder assets by ensuring caches are cleared in the correct order.
- Fixed potential issues with cache synchronization between WP-Umbrella, Beaver Builder, and Nginx/LiteSpeed.

## [1.1.1] - 2024-06-30
### Changed
- Refactored plugin initialization to reduce redundant log messages.
- Added `wcph_init_hooks` function to initialize hooks once during the `init` action.
- Optimized cache purge function to avoid unnecessary operations and reduce server load.

### Fixed
- Reduced excessive logging by ensuring hooks are only initialized once per request.
- Improved efficiency of cache purge operations to minimize server load.

## [1.0.0] - 2024-06-30
### Added
- Integration with Beaver Builder and ACF to trigger NGINX Helper and LiteSpeed Cache plugin purges.
- Logging functionality to track cache purging actions.
- New hooks for ACF options page and Beaver Builder cache events.

### Changed
- Updated versioning scheme to start from `1.0.0` for the fork.
- Refactored plugin structure for improved maintainability and customizability.
- Updated plugin name, URI, and author information to reflect the fork.

### Removed
- Support for Elementor, Autoptimize, and Oxygen builders to streamline functionality for Weave Digital's specific needs.
