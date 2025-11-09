# WP Admin Submenus

A performant, robust, and secure WordPress plugin that adds intelligent submenus to the WordPress admin for quick access to posts, taxonomies, and users.

## Features

- **Smart Submenus**: Automatically adds submenus to post types, taxonomies, and user roles for quick access
- **Configurable**: Settings page to enable/disable submenus for specific post types
- **Performance Optimized**: Efficient queries with proper caching and no persistent object cache issues
- **Polylang Compatible**: Automatically filters content by default language when Polylang is active
- **Secure**: Built with WordPress security best practices (nonces, sanitization, capability checks)
- **Responsive Design**: Scrollable submenus with clean, accessible styling
- **Automatic Updates**: Integrates with GitHub releases for seamless plugin updates

## Installation

1. Upload the plugin files to `/wp-content/plugins/wp-admin-submenus/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Admin Submenus to configure which post types should have submenus

## Automatic Updates

This plugin uses the [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) library to enable automatic updates from GitHub releases.

**How it works:**
- The plugin automatically checks for new releases on GitHub
- When a new version is released, WordPress will notify you in the Plugins page
- You can update directly from your WordPress admin, just like WordPress.org plugins
- No manual downloading or uploading required

**For developers:**
- Create a new release on GitHub with a version tag (e.g., `v1.0.1`)
- The plugin will automatically detect the new version
- Users will be notified and can update with one click

## Configuration

### Settings Page

Navigate to **Settings > Admin Submenus** to configure:

- **Enable Submenus For**: Select which post types should display submenus (all eligible post types are enabled by default)
- **Items Per Menu**: Set the maximum number of items to show before the "See more" link appears (default: 20)

### Default Behavior

- All user-facing post types are enabled by default (except core WordPress types like posts, attachments, revisions, etc.)
- Post types must be either public OR have `show_in_menu` enabled to be included
- Shows up to 20 items per submenu
- Automatically excludes internal/system post types:
  - Core WordPress internal types (blocks, templates, navigation, etc.)
  - ACF internal types (field groups, fields, post types, taxonomies, options pages)
  - Formidable Forms internal types
  - Ninja Forms submissions
- Taxonomies and user roles are always shown (following the same exclusion rules)

## Filters

### `wp_admin_submenus_excluded_post_types`

Filter the list of post types to exclude from submenus:

```php
add_filter('wp_admin_submenus_excluded_post_types', function($excluded) {
    $excluded[] = 'my_custom_post_type';
    return $excluded;
});
```

### `wp_admin_submenus_excluded_taxonomies`

Filter the list of taxonomies to exclude from submenus:

```php
add_filter('wp_admin_submenus_excluded_taxonomies', function($excluded) {
    $excluded[] = 'my_custom_taxonomy';
    return $excluded;
});
```

### `wp_admin_submenus_desc_sorted_post_types`

Specify post types that should be sorted in descending order (useful for year-based content):

```php
add_filter('wp_admin_submenus_desc_sorted_post_types', function($post_types) {
    $post_types[] = 'yearly_reports';
    return $post_types;
});
```

## Security Features

- **Capability Checks**: All menu items respect WordPress capabilities
- **Nonce Verification**: Settings page uses WordPress Settings API with automatic nonce handling
- **Input Sanitization**: All user input is properly sanitized
- **Output Escaping**: All output is escaped to prevent XSS attacks
- **Direct Access Prevention**: Blocks direct file access

## Performance Optimizations

- **Efficient Queries**: Uses `no_found_rows`, disables meta/term cache updates when not needed
- **Smart Pagination**: Fetches `limit + 1` items to efficiently determine if "See more" is needed
- **Static Caching**: Configuration cached per request to prevent redundant calculations
- **No Persistent Cache**: Avoids Object Cache Pro compatibility issues

## Translation (i18n)

The plugin is fully translation-ready and includes:

- **Text Domain**: `wp-admin-submenus`
- **Translation Template**: `languages/wp-admin-submenus.pot` - Use this as a base for new translations
- **Included Translations**:
  - French (`fr_FR`)
  - Spanish (`es_ES`)
  - German (`de_DE`)

### For Translators

1. Copy `languages/wp-admin-submenus.pot` to `languages/wp-admin-submenus-{locale}.po`
2. Translate the strings using a tool like Poedit or your preferred translation editor
3. Compile the `.po` file to `.mo` using `msgfmt` or the included script:
   ```bash
   cd languages
   bash compile-translations.sh
   ```

## Accessibility

The plugin follows WCAG 2.1 Level AA accessibility guidelines:

- **ARIA Labels**: All form controls have proper ARIA labels for screen readers
- **Keyboard Navigation**: Full keyboard accessibility throughout
- **Semantic HTML**: Proper use of fieldsets, legends, and form labels
- **Screen Reader Support**: Hidden labels and descriptions for assistive technologies

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher

## License

GPL v2 or later

## Support

For issues and feature requests, please use the GitHub issue tracker.
