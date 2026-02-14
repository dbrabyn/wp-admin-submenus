# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.17] - 2026-02-14

### Fixed

- Replaced direct `$wpdb->get_col()` query in uninstall.php with `get_sites()` WordPress API
- Prefixed all global variables in uninstall.php with `wp_admin_submenus_` for PHPCS compliance
- Renamed `$wpAdminSubmenusUpdateChecker` to `$wp_admin_submenus_update_checker` for prefix compliance
- Shortened plugin description to stay within 150-character limit

## [1.0.15] - 2025-11-24

### Removed

- Removes description paragraph from settings page for post types selection

## [1.0.14] - 2025-11-24

### Changed

- Replaced character-based truncation with CSS-based line truncation for better responsiveness
- Title truncation now uses `text-overflow: ellipsis` for 1 line and `-webkit-line-clamp` for 2 lines
- Submenu width now uses `min-width: 160px` and `max-width: 225px` for flexible sizing
- Truncation adapts automatically to screen size and font settings

### Removed

- Removed PHP `truncate_title()` method - truncation now handled entirely by CSS
- Removed character limit constants (`WP_ADMIN_SUBMENUS_TRUNCATE_1_LINE` and `WP_ADMIN_SUBMENUS_TRUNCATE_2_LINES`)

### Technical

- Menu items now use flexbox layout with `.submenu-title` spans for proper truncation
- Added `flex: 1` and `min-width: 0` to truncated titles for correct overflow behavior
- Indent spans use `flex-shrink: 0` to maintain consistent spacing

## [1.0.13] - 2025-11-22

### Tweaked

- Changed 2 lines limit to 35 characters.
- Same set of 2 limits for all menu item types.
- Limits set as constants up top.

## [1.0.12] - 2025-11-11

### Tweaked

- Changed 2 lines limit to 28 characters.

## [1.0.11] - 2025-11-11

### Tweaked

- Changed 1 and 2 lines limits to 18 and 35 characters resp.

## [1.0.10] - 2025-11-11

### Added

- Title truncation setting with three options: no truncation (default), 1 line, or 2 lines
- New "Title Truncation" setting in Settings page with radio button options
- PHP-based truncation using character limits (~25 chars for 1 line, ~50 chars for 2 lines)
- Multibyte-safe truncation with proper ellipsis character (…)

### Changed

- Truncated titles now use server-side PHP truncation instead of CSS for better consistency
- Full title always preserved in menu page title attribute for accessibility

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
