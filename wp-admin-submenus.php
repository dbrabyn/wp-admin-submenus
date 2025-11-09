<?php
/**
 * Plugin Name: WP Admin Submenus
 * Plugin URI: https://github.com/dbrabyn/wp-submenus
 * Description: Adds intelligent submenus to WordPress admin for quick access to posts, taxonomies, and users. Configure which post types to include via Settings.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-admin-submenus
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('WP_ADMIN_SUBMENUS_VERSION', '1.0.0');
define('WP_ADMIN_SUBMENUS_PLUGIN_FILE', __FILE__);
define('WP_ADMIN_SUBMENUS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_ADMIN_SUBMENUS_DEFAULT_LIMIT', 20);

// Initialize Plugin Update Checker
require WP_ADMIN_SUBMENUS_PLUGIN_DIR . 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$wpAdminSubmenusUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/dbrabyn/wp-submenus/',
    WP_ADMIN_SUBMENUS_PLUGIN_FILE,
    'wp-admin-submenus'
);

// Optionally set the branch for updates (defaults to 'main' or 'master')
// $wpAdminSubmenusUpdateChecker->setBranch('main');

/**
 * Main Plugin Class
 */
class WP_Admin_Submenus {

    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Plugin options
     */
    private $options = null;

    /**
     * Submenu configuration cache
     */
    private $config = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Admin menu hooks
        add_action('admin_menu', [$this, 'register_all_submenus'], 999);
        add_action('admin_head', [$this, 'admin_submenu_assets']);

        // Settings page hooks
        add_action('admin_menu', [$this, 'add_settings_page'], 10);
        add_action('admin_init', [$this, 'register_settings']);

        // Plugin action links
        add_filter('plugin_action_links_' . plugin_basename(WP_ADMIN_SUBMENUS_PLUGIN_FILE), [$this, 'add_action_links']);
    }

    /**
     * Get plugin options with defaults
     */
    private function get_options() {
        if (null !== $this->options) {
            return $this->options;
        }

        $defaults = [
            'enabled_post_types' => $this->get_default_post_types(),
            'item_limit' => WP_ADMIN_SUBMENUS_DEFAULT_LIMIT,
        ];

        $saved_options = get_option('wp_admin_submenus_options', []);
        $this->options = wp_parse_args($saved_options, $defaults);

        return $this->options;
    }

    /**
     * Get all eligible post types (all public post types enabled by default)
     */
    private function get_default_post_types() {
        $excluded = [
            'post', 'attachment', 'revision', 'nav_menu_item', 'custom_css',
            'customize_changeset', 'oembed_cache', 'user_request', 'wp_block',
            'wp_template', 'wp_template_part', 'wp_global_styles', 'wp_navigation',
            'acf-field-group', 'acf-field',
            'frm_form', 'frm_display', 'frm_style', 'frm_styles', 'frm_payment', 'frm_notification',
            'nf_sub'
        ];

        // Allow filtering of excluded post types
        $excluded = apply_filters('wp_admin_submenus_excluded_post_types', $excluded);

        $post_types = get_post_types(['public' => true], 'names');
        return array_values(array_diff($post_types, $excluded));
    }

    /**
     * Get submenu configuration
     */
    private function get_submenu_config($force_refresh = false) {
        if ($force_refresh) {
            $this->config = null;
        }

        if (null !== $this->config) {
            return $this->config;
        }

        $options = $this->get_options();

        // Get excluded taxonomies
        $excluded_taxonomies = [
            'post_format', 'nav_menu', 'link_category', 'wp_theme',
            'wp_template_part_area', 'language', 'term_language',
            'post_translations', 'term_translations',
            'acf-field-group-category'
        ];

        $excluded_taxonomies = apply_filters('wp_admin_submenus_excluded_taxonomies', $excluded_taxonomies);

        // Get all public taxonomies
        $taxonomies = get_taxonomies(['public' => true], 'names');

        // Get excluded user roles
        $excluded_roles = ['subscriber'];

        $this->config = [
            'post_types' => !empty($options['enabled_post_types']) ? $options['enabled_post_types'] : [],
            'taxonomies' => array_diff($taxonomies, $excluded_taxonomies),
            'user_roles' => array_diff(array_keys(wp_roles()->roles), $excluded_roles),
            'post_types_sort_desc' => apply_filters('wp_admin_submenus_desc_sorted_post_types', []),
            'item_limit' => absint($options['item_limit']),
        ];

        return $this->config;
    }

    /**
     * Get submenu posts for a post type
     */
    private function get_submenu_posts($post_type, $limit) {
        if (!post_type_exists($post_type)) {
            return ['items' => [], 'has_more' => false];
        }

        $config = $this->get_submenu_config();

        // Determine sort order
        $orderby = 'title';
        $order = in_array($post_type, $config['post_types_sort_desc'], true) ? 'DESC' : 'ASC';

        $query_args = [
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => $limit + 1,
            'orderby' => $orderby,
            'order' => $order,
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ];

        // Polylang support
        if (function_exists('pll_default_language')) {
            $default_lang = pll_default_language();
            if ($default_lang) {
                $query_args['lang'] = $default_lang;
            }
        }

        $posts = get_posts($query_args);

        $has_more = count($posts) > $limit;
        if ($has_more) {
            array_pop($posts);
        }

        return [
            'items' => $posts,
            'has_more' => $has_more,
        ];
    }

    /**
     * Get submenu terms for a taxonomy
     */
    private function get_submenu_terms($taxonomy, $limit) {
        if (!taxonomy_exists($taxonomy)) {
            return ['items' => [], 'has_more' => false];
        }

        $query_args = [
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'number' => $limit + 1,
            'orderby' => 'name',
            'order' => 'ASC'
        ];

        // Polylang support
        if (function_exists('pll_default_language')) {
            $default_lang = pll_default_language();
            if ($default_lang) {
                $query_args['lang'] = $default_lang;
            }
        }

        $terms = get_terms($query_args);

        if (is_wp_error($terms)) {
            return ['items' => [], 'has_more' => false];
        }

        $has_more = count($terms) > $limit;
        if ($has_more) {
            array_pop($terms);
        }

        return [
            'items' => $terms,
            'has_more' => $has_more
        ];
    }

    /**
     * Get submenu users by role
     */
    private function get_submenu_users_by_role($role, $limit) {
        if (!get_role($role)) {
            return ['items' => [], 'has_more' => false, 'total' => 0];
        }

        static $user_counts = null;
        if (null === $user_counts) {
            $user_counts = count_users();
        }

        $role_count = $user_counts['avail_roles'][$role] ?? 0;

        $users = get_users([
            'role' => $role,
            'number' => $limit,
            'orderby' => 'display_name',
            'order' => 'ASC',
            'fields' => ['ID', 'display_name', 'user_login']
        ]);

        return [
            'items' => $users,
            'total' => $role_count,
            'has_more' => $role_count > $limit
        ];
    }

    /**
     * Generate admin URL for submenu items
     */
    private function generate_submenu_url($type, $object, $extra = []) {
        switch ($type) {
            case 'post':
                return add_query_arg(['post' => $object->ID, 'action' => 'edit'], admin_url('post.php'));

            case 'term':
                if (!$object || empty($object->term_id)) {
                    return '';
                }
                return get_edit_term_link($object->term_id, $extra['taxonomy'] ?? '') ?: '';

            case 'user':
                return add_query_arg('user_id', $object->ID, admin_url('user-edit.php'));

            case 'user_role':
                return add_query_arg('role', $extra['role'] ?? '', admin_url('users.php'));

            case 'see_more_posts':
                return add_query_arg('post_type', $extra['post_type'] ?? '', admin_url('edit.php'));

            case 'see_more_terms':
                $args = ['taxonomy' => $extra['taxonomy'] ?? ''];
                $post_type = $extra['post_type'] ?? 'post';
                if ($post_type !== 'post') {
                    $args['post_type'] = $post_type;
                }
                return add_query_arg($args, admin_url('edit-tags.php'));

            default:
                return '';
        }
    }

    /**
     * Register all submenus
     */
    public function register_all_submenus() {
        if (!current_user_can('edit_posts')) {
            return;
        }

        $config = $this->get_submenu_config();

        // Post type submenus
        foreach ($config['post_types'] as $post_type) {
            $this->register_post_type_submenus($post_type, $config['item_limit']);
        }

        // Taxonomy submenus
        foreach ($config['taxonomies'] as $taxonomy) {
            $this->register_taxonomy_submenus($taxonomy, $config['item_limit']);
        }

        // User submenus
        if (current_user_can('list_users')) {
            $this->register_user_submenus($config['item_limit']);
        }
    }

    /**
     * Register post type submenus
     */
    private function register_post_type_submenus($post_type, $limit) {
        $posts_data = $this->get_submenu_posts($post_type, $limit);
        if (empty($posts_data['items'])) {
            return;
        }

        $post_type_obj = get_post_type_object($post_type);
        $parent_slug = "edit.php?post_type={$post_type}";
        $capability = $post_type_obj->cap->edit_posts ?? 'edit_posts';

        foreach ($posts_data['items'] as $post) {
            add_submenu_page(
                $parent_slug,
                esc_html($post->post_title),
                '<span aria-hidden="true">&nbsp;&nbsp;-&nbsp;</span>' . esc_html($post->post_title),
                $capability,
                $this->generate_submenu_url('post', $post)
            );
        }

        if ($posts_data['has_more']) {
            add_submenu_page(
                $parent_slug,
                __('See more →', 'wp-admin-submenus'),
                '<span class="see-more-link">' . esc_html__('See more →', 'wp-admin-submenus') . '</span>',
                $capability,
                $this->generate_submenu_url('see_more_posts', null, ['post_type' => $post_type])
            );
        }
    }

    /**
     * Register taxonomy submenus
     */
    private function register_taxonomy_submenus($taxonomy, $limit) {
        $terms_data = $this->get_submenu_terms($taxonomy, $limit);
        if (empty($terms_data['items'])) {
            return;
        }

        $taxonomy_obj = get_taxonomy($taxonomy);
        $post_type = $taxonomy_obj->object_type[0] ?? 'post';
        $parent_slug = "edit-tags.php?taxonomy={$taxonomy}";
        if ($post_type !== 'post') {
            $parent_slug .= "&post_type={$post_type}";
        }
        $capability = $taxonomy_obj->cap->edit_terms ?? 'manage_categories';

        foreach ($terms_data['items'] as $term) {
            add_submenu_page(
                $parent_slug,
                esc_html($term->name),
                '<span aria-hidden="true">&nbsp;&nbsp;-&nbsp;</span>' . esc_html($term->name),
                $capability,
                $this->generate_submenu_url('term', $term, ['taxonomy' => $taxonomy, 'post_type' => $post_type])
            );
        }

        if ($terms_data['has_more']) {
            add_submenu_page(
                $parent_slug,
                __('See more →', 'wp-admin-submenus'),
                '<span class="see-more-link">' . esc_html__('See more →', 'wp-admin-submenus') . '</span>',
                $capability,
                $this->generate_submenu_url('see_more_terms', null, ['taxonomy' => $taxonomy, 'post_type' => $post_type])
            );
        }
    }

    /**
     * Register user submenus
     */
    private function register_user_submenus($limit) {
        if (!current_user_can('list_users')) {
            return;
        }

        $parent_slug = 'users.php';
        $capability = 'list_users';
        $config = $this->get_submenu_config();

        foreach ($config['user_roles'] as $role) {
            $users_data = $this->get_submenu_users_by_role($role, $limit);
            if (empty($users_data['items'])) {
                continue;
            }

            $role_obj = get_role($role);
            $role_name = $role_obj ? translate_user_role($role_obj->name) : $role;

            add_submenu_page(
                $parent_slug,
                "{$role_name} ({$users_data['total']})",
                '<span class="role-name">' . esc_html($role_name) . '</span><span class="dotted-line" aria-hidden="true"></span><span class="user-count">(' . absint($users_data['total']) . ')</span>',
                $capability,
                '#'
            );

            foreach ($users_data['items'] as $user) {
                $display_name = $user->display_name ?: $user->user_login;
                add_submenu_page(
                    $parent_slug,
                    esc_html($display_name),
                    '<span aria-hidden="true">&nbsp;&nbsp;-&nbsp;</span>' . esc_html($display_name),
                    $capability,
                    $this->generate_submenu_url('user', $user)
                );
            }

            if ($users_data['has_more']) {
                /* translators: %s: role name */
                add_submenu_page(
                    $parent_slug,
                    sprintf(__('See more %s', 'wp-admin-submenus'), $role_name),
                    '<span class="see-more-link">' . __('See more →', 'wp-admin-submenus') . '</span>',
                    $capability,
                    $this->generate_submenu_url('user_role', null, ['role' => $role])
                );
            }
        }
    }

    /**
     * Add admin CSS for submenus
     */
    public function admin_submenu_assets() {
        if (!current_user_can('edit_posts')) {
            return;
        }
        ?>
        <style>
            /* Make long submenus scrollable */
            #adminmenu .wp-submenu {
                min-width: 160px !important;
                max-height: 60vh;
                overflow-y: auto;
            }
            #adminmenu .wp-submenu a {
                white-space: normal !important;
                word-wrap: break-word !important;
            }
            /* Indent individual item links */
            #adminmenu .wp-submenu li a[href*="post.php?post="],
            #adminmenu .wp-submenu li a[href*="term.php?taxonomy="],
            #adminmenu .wp-submenu li a[href*="user-edit.php?user_id="] {
                text-indent: -18px !important;
                padding-left: 26px !important;
                padding-right: 20px !important;
            }
            /* Style user role headers */
            #adminmenu .wp-submenu a[href="#"] {
                display: flex !important;
                align-items: baseline !important;
                font-weight: 500 !important;
                color: #bbb !important;
                gap: 8px !important;
            }
            #adminmenu .wp-submenu .role-name {
                flex-shrink: 0 !important;
            }
            #adminmenu .wp-submenu .dotted-line {
                flex: 1 !important;
                height: 1px !important;
                background: repeating-linear-gradient(to right, #888 0px, #888 2px, transparent 2px, transparent 4px) !important;
                min-width: 20px !important;
            }
            #adminmenu .wp-submenu .user-count {
                flex-shrink: 0 !important;
                font-size: 13px !important;
            }
            /* Style "See more" links */
            #adminmenu .wp-submenu .see-more-link {
                font-weight: 500 !important;
                font-style: italic !important;
                padding-top: 8px !important;
                text-align: right !important;
                display: block;
                margin-top: 0.5rem;
            }
            #adminmenu .wp-submenu li:has(.see-more-link) a:hover {
                box-shadow: none;
            }
        </style>
        <?php
    }

    /**
     * Add settings page
     */
    public function add_settings_page() {
        add_options_page(
            __('WP Admin Submenus Settings', 'wp-admin-submenus'),
            __('Admin Submenus', 'wp-admin-submenus'),
            'manage_options',
            'wp-admin-submenus',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting(
            'wp_admin_submenus_options',
            'wp_admin_submenus_options',
            [$this, 'sanitize_options']
        );

        add_settings_section(
            'wp_admin_submenus_post_types',
            __('Post Type Submenus', 'wp-admin-submenus'),
            [$this, 'render_post_types_section'],
            'wp-admin-submenus'
        );

        add_settings_field(
            'enabled_post_types',
            __('Enable Submenus For', 'wp-admin-submenus'),
            [$this, 'render_post_types_field'],
            'wp-admin-submenus',
            'wp_admin_submenus_post_types'
        );

        add_settings_field(
            'item_limit',
            __('Items Per Menu', 'wp-admin-submenus'),
            [$this, 'render_item_limit_field'],
            'wp-admin-submenus',
            'wp_admin_submenus_post_types'
        );
    }

    /**
     * Sanitize options
     */
    public function sanitize_options($input) {
        $sanitized = [];

        // Sanitize enabled post types
        if (isset($input['enabled_post_types']) && is_array($input['enabled_post_types'])) {
            $sanitized['enabled_post_types'] = array_map('sanitize_key', $input['enabled_post_types']);
        } else {
            $sanitized['enabled_post_types'] = [];
        }

        // Sanitize item limit
        $sanitized['item_limit'] = isset($input['item_limit']) ? absint($input['item_limit']) : WP_ADMIN_SUBMENUS_DEFAULT_LIMIT;
        if ($sanitized['item_limit'] < 1) {
            $sanitized['item_limit'] = WP_ADMIN_SUBMENUS_DEFAULT_LIMIT;
        }

        // Clear config cache
        $this->config = null;
        $this->options = null;

        return $sanitized;
    }

    /**
     * Render post types section description
     */
    public function render_post_types_section() {
        echo '<p>' . esc_html__('Configure which post types should display submenus in the WordPress admin.', 'wp-admin-submenus') . '</p>';
    }

    /**
     * Render post types field
     */
    public function render_post_types_field() {
        $options = $this->get_options();
        $enabled_post_types = $options['enabled_post_types'];
        $available_post_types = $this->get_default_post_types();

        if (empty($available_post_types)) {
            echo '<p>' . esc_html__('No eligible post types found.', 'wp-admin-submenus') . '</p>';
            return;
        }

        echo '<fieldset>';
        foreach ($available_post_types as $post_type) {
            $post_type_obj = get_post_type_object($post_type);
            if (!$post_type_obj) {
                continue;
            }

            $checked = in_array($post_type, $enabled_post_types, true);
            $id = 'post_type_' . esc_attr($post_type);
            ?>
            <label for="<?php echo $id; ?>" style="display: block; margin-bottom: 8px;">
                <input
                    type="checkbox"
                    name="wp_admin_submenus_options[enabled_post_types][]"
                    id="<?php echo $id; ?>"
                    value="<?php echo esc_attr($post_type); ?>"
                    <?php checked($checked); ?>
                />
                <?php echo esc_html($post_type_obj->labels->name); ?>
                <span style="color: #666; font-size: 12px;">(<?php echo esc_html($post_type); ?>)</span>
            </label>
            <?php
        }
        echo '</fieldset>';
        echo '<p class="description">' . esc_html__('Select which post types should have quick-access submenus in the admin sidebar.', 'wp-admin-submenus') . '</p>';
    }

    /**
     * Render item limit field
     */
    public function render_item_limit_field() {
        $options = $this->get_options();
        $item_limit = $options['item_limit'];
        ?>
        <input
            type="number"
            name="wp_admin_submenus_options[item_limit]"
            id="item_limit"
            value="<?php echo esc_attr($item_limit); ?>"
            min="1"
            max="100"
            step="1"
            class="small-text"
        />
        <p class="description">
            <?php esc_html_e('Maximum number of items to show in each submenu before "See more" link appears.', 'wp-admin-submenus'); ?>
        </p>
        <?php
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('wp_admin_submenus_options');
                do_settings_sections('wp-admin-submenus');
                submit_button(__('Save Settings', 'wp-admin-submenus'));
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Add plugin action links
     */
    public function add_action_links($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('options-general.php?page=wp-admin-submenus'),
            __('Settings', 'wp-admin-submenus')
        );
        array_unshift($links, $settings_link);
        return $links;
    }
}

// Initialize the plugin
function wp_admin_submenus_init() {
    return WP_Admin_Submenus::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'wp_admin_submenus_init');
