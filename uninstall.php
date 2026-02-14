<?php
/**
 * Uninstall script for WP Admin Submenus
 *
 * This file runs when the plugin is deleted via the WordPress admin.
 * It cleans up all plugin data from the database.
 *
 * @package WP_Admin_Submenus
 */

// Exit if uninstall not called from WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('wp_admin_submenus_options');

// For multisite installations, delete options from all sites
if (is_multisite()) {
    $wp_admin_submenus_sites = get_sites(array('fields' => 'ids'));

    foreach ($wp_admin_submenus_sites as $wp_admin_submenus_blog_id) {
        switch_to_blog($wp_admin_submenus_blog_id);
        delete_option('wp_admin_submenus_options');
        restore_current_blog();
    }
}

// Clear any transients (if we add caching in the future)
// delete_transient('wp_admin_submenus_cache');
