# Changelog

## [1.3.3] - 2025-03-07 - Auto updater
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
