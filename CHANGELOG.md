# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.5] - 2025-11-09

### Fixed

- Improved post type filtering to exclude internal/system post types from settings page
- Now filters out ACF internal post types: `acf-post-type`, `acf-taxonomy`, `acf-ui-options-page`
- Added intelligent filtering: only includes post types that are public OR have `show_in_menu` enabled
- This prevents internal plugin post types (that only have `show_ui=true`) from appearing in the settings

### Changed

- Post type detection now checks both `public` and `show_in_menu` properties
- Settings page now uses the same improved filtering logic for consistency

## [1.0.4] - 2025-11-09

### Added

- Plugin description translation

## [1.0.3] - 2025-11-09

### Fixed

- Fixed plugin not detecting Custom Post Types (CPTs) registered by other plugins
- Changed post type detection from `public => true` to `show_ui => true` to include CPTs with admin UI regardless of public status
- Added cache invalidation when post types are registered via `registered_post_type` hook
- Settings page now dynamically fetches all available post types at render time

### Added

- New `clear_cache()` method to invalidate cached options and configuration when post types are registered

## [1.0.2] - 2025-11-09

### Changed

- Updated version number in plugin header

## [1.0.1] - 2025-11-09

### Changed

- Updated author information in plugin header
- Refined README and plugin description for clarity

## [1.0.0] - Initial Release

### Added

- Initial release of WP Admin Submenus plugin
- Intelligent submenus for WordPress admin menu
- Quick access to posts, taxonomies, and users
- Configurable post types via Settings page
- Configurable item limit per submenu
- Support for Polylang (default language filtering)
- User role submenus with counts
- "See more" links for large lists
- Accessibility-friendly markup
- GitHub auto-update support
