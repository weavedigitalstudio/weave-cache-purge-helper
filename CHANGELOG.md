# Changelog

## [1.0.1] - 2024-06-30
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
