# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.8] - 2025-11-11

- Added title truncation to submenu titles and on/off, 1 or 2 lines options to Settings page

### Added

## [1.0.7] - 2025-11-11

### Added

- Support for WordPress default "Posts" post type
- Posts now appear in settings page and can have submenus enabled

### Fixed

- Fixed parent slug for regular posts menu (now correctly uses `edit.php` instead of `edit.php?post_type=post`)
- Fixed "See more" URL for regular posts to use proper WordPress admin URL structure
- Removed `'post'` from excluded post types list

## [1.0.5] - 2025-11-09

### Fixed

- Improved post type filtering to exclude internal/system post types from settings page
- Now filters out ACF internal post types: `acf-post-type`, `acf-taxonomy`, `acf-ui-options-page`
- Added intelligent filtering: only includes post types that are public OR have top-level admin menus (`show_in_menu === true`)
- This prevents plugin configuration post types from appearing while allowing internal admin-only content types

### Changed

- Post type detection now checks `public` property and `show_in_menu === true` (strict boolean check)
- Settings page now uses the same improved filtering logic for consistency
- Removed version constant duplication - version now read directly from plugin header using `get_plugin_data()`

### Removed

- Removed `WP_ADMIN_SUBMENUS_VERSION` constant to avoid maintaining version in multiple places

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
