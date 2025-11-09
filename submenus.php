<?php
/**
 * Simplified WordPress Admin Submenu System
 * No caching to avoid Object Cache Pro compatibility issues
 */

if (!defined('ABSPATH')) exit;

// Configuration constants
if (!defined('ADMIN_SUBMENU_DEFAULT_LIMIT')) {
    define('ADMIN_SUBMENU_DEFAULT_LIMIT', 20);
}

/**
 * Get the textdomain for admin submenu translations
 * Falls back to current theme's textdomain if not defined
 */
function get_admin_submenu_textdomain() {
    static $textdomain = null;

    if ($textdomain === null) {
        // Check if a custom textdomain is defined
        if (defined('ADMIN_SUBMENU_TEXTDOMAIN')) {
            $textdomain = ADMIN_SUBMENU_TEXTDOMAIN;
        } else {
            // Fall back to current theme's textdomain
            $theme = wp_get_theme();
            $textdomain = $theme->get('TextDomain') ?: 'default';
        }
    }

    return $textdomain;
}

/**
 * Retrieves the configuration for which content types to display in the admin submenus.
 * This function determines which post types, taxonomies, and user roles are eligible
 * by fetching all public ones and then filtering out a predefined exclusion list.
 * The result is cached for the duration of the request to prevent redundant calculations.
 */
function get_submenu_config($force_refresh = false) {
    // Use a static variable to ensure this runs only once per request.
    static $config = null;

    // If a force refresh is requested, clear the static cache.
    if ($force_refresh) {
        $config = null;
    }

    if ($config === null) {
        // Define post types, taxonomies, and roles to explicitly exclude.
        $excluded_post_types = [
            'post', 'attachment', 'revision', 'nav_menu_item', 'custom_css',
            'customize_changeset', 'oembed_cache', 'user_request', 'wp_block',
            'wp_template', 'wp_template_part', 'wp_global_styles', 'wp_navigation', // Core block editor types
            'acf-field-group', 'acf-field', // Exclude ACF internal types
            'frm_form', 'frm_display', 'frm_style', 'frm_styles', 'frm_payment', 'frm_notification', // Formidable Forms
            'nf_sub' // Ninja Forms submissions
        ];

        $excluded_taxonomies = [
            'post_format', 'nav_menu', 'link_category', 'wp_theme',
            'wp_template_part_area', 'language', 'term_language',
            'post_translations', 'term_translations',
            'acf-field-group-category' // Exclude ACF internal taxonomies
        ];

        // Define post types that should be sorted in descending order by title (e.g., for years).
        $desc_sorted_post_types = [];

        $excluded_roles = ['subscriber'];

        // Allow other systems to exclude post types and taxonomies
        $excluded_post_types = apply_filters('admin_submenu_excluded_post_types', $excluded_post_types);
        $excluded_taxonomies = apply_filters('admin_submenu_excluded_taxonomies', $excluded_taxonomies);

        // Get all public post types and taxonomies.
        $post_types = get_post_types(['public' => true], 'names');
        $taxonomies = get_taxonomies(['public' => true], 'names');

        $config = [
            'post_types' => array_diff(
                $post_types,
                $excluded_post_types
            ),
            'taxonomies' => array_diff(
                $taxonomies,
                $excluded_taxonomies
            ),
            'user_roles' => array_diff(
                array_keys(wp_roles()->roles),
                $excluded_roles
            ),
            'post_types_sort_desc' => apply_filters(
                'admin_submenu_desc_sorted_post_types',
                $desc_sorted_post_types,
                (array) $desc_sorted_post_types
            ),
        ];
    }

    return $config;
}

/**
 * Retrieves a list of posts for a given post type, optimized for speed.
 * Only returns posts from the default language when Polylang is active.
 *
 * @param string $post_type The post type to query.
 * @param int    $limit     The maximum number of posts to return.
 * @return array An array containing 'items' (the posts) and 'has_more' (a boolean).
 */
function get_submenu_posts($post_type, $limit = ADMIN_SUBMENU_DEFAULT_LIMIT) {
    if (!post_type_exists($post_type)) {
        return ['items' => [], 'has_more' => false];
    }

    // Get the global submenu configuration.
    $config = get_submenu_config();

    // Default sorting parameters
    $orderby = 'title';
    $order = 'ASC';

    // Custom sorting for specified post types to show most recent year first.
    if (in_array($post_type, $config['post_types_sort_desc'], true)) {
        $order = 'DESC';
    }

    // Build query args
    $query_args = [
        'post_type' => $post_type,
        'post_status' => 'publish',
        'posts_per_page' => $limit + 1, // Fetch one extra to check for more
        'orderby' => $orderby,
        'order' => $order,
        'no_found_rows' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ];

    // Filter by default language if Polylang is active
    if (function_exists('pll_default_language')) {
        $default_lang = pll_default_language();
        if ($default_lang) {
            $query_args['lang'] = $default_lang;
        }
    }

    $posts = get_posts($query_args);

    $has_more = count($posts) > $limit;
    if ($has_more) {
        array_pop($posts); // Remove the extra post used for the check
    }

    $result = [
        'items' => $posts,
        'has_more' => $has_more,
    ];

    return $result;
}

/**
 * Retrieves a list of terms for a given taxonomy.
 * Only returns terms from the default language when Polylang is active.
 *
 * @param string $taxonomy The taxonomy to query.
 * @param int    $limit    The maximum number of terms to return.
 * @return array An array containing 'items' (the terms) and 'has_more' (a boolean).
 */
function get_submenu_terms($taxonomy, $limit = ADMIN_SUBMENU_DEFAULT_LIMIT) {
    if (!taxonomy_exists($taxonomy)) {
        return ['items' => [], 'has_more' => false];
    }

    // Build query args
    $query_args = [
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
        'number' => $limit + 1, // Get one extra to check for more
        'orderby' => 'name',
        'order' => 'ASC'
    ];

    // Filter by default language if Polylang is active
    if (function_exists('pll_default_language')) {
        $default_lang = pll_default_language();
        if ($default_lang) {
            $query_args['lang'] = $default_lang;
        }
    }

    // We fetch one more item than the limit. This is an efficient way to check
    // if there are more terms available without running a separate count query.
    $terms = get_terms($query_args);

    // Gracefully handle any errors from get_terms.
    if (is_wp_error($terms)) {
        return ['items' => [], 'has_more' => false];
    }

    // Check if we have more terms than the limit.
    $has_more = count($terms) > $limit;
    if ($has_more) {
        array_pop($terms); // Remove the extra item used for the check.
    }

    // Prepare the result array.
    $result = [
        'items' => $terms,
        'has_more' => $has_more
    ];

    return $result;
}

/**
 * Retrieves a list of users for a given role.
 *
 * @param string $role  The user role to query.
 * @param int    $limit The maximum number of users to return.
 * @return array An array containing 'items' (the users) and 'has_more' (a boolean).
 */
function get_submenu_users_by_role($role, $limit = ADMIN_SUBMENU_DEFAULT_LIMIT) {
  if (!get_role($role)) {
    return ['items' => [], 'has_more' => false, 'total' => 0];
  }

  // Cache count_users() result per request
  static $user_counts = null;
  if ($user_counts === null) {
    $user_counts = count_users();
  }
  $role_count = $user_counts['avail_roles'][$role] ?? 0;

  // Get the limited list of users to display
  $users = get_users([
    'role' => $role,
    'number' => $limit,
    'orderby' => 'display_name',
    'order' => 'ASC',
    'fields' => ['ID', 'display_name', 'user_login']
  ]);

  $result = [
    'items' => $users,
    'total' => $role_count,
    'has_more' => $role_count > $limit
  ];

  return $result;
}

/**
 * Generates the correct admin URL for various object types.
 * This is a helper function to keep the submenu registration code clean.
 *
 * @param string $type   The type of object ('post', 'term', 'user', etc.).
 * @param object|null $object The WordPress object (WP_Post, WP_Term, WP_User).
 * @param array  $extra  Additional data needed for some URL types (e.g., taxonomy slug).
 * @return string The generated admin URL.
 */
function generate_submenu_url($type, $object, $extra = []) {
  switch ($type) {
    // URL for editing a post, page, or custom post type item.
    case 'post': {
        $args = ['post' => $object->ID, 'action' => 'edit'];
        return add_query_arg($args, admin_url('post.php'));
    }

    case 'term': {
        if (!$object || empty($object->term_id)) {
            return '';
        }
        // Use the standard WordPress function to get the edit link for a term.
        return get_edit_term_link($object->term_id, $extra['taxonomy'] ?? '') ?: '';
    }

    // URL for editing a user profile.
    case 'user': {
        return add_query_arg('user_id', $object->ID, admin_url('user-edit.php'));
    }

    // URL for the main user list, filtered by a specific role.
    case 'user_role': {
        $role = $extra['role'] ?? '';
        return add_query_arg('role', $role, admin_url('users.php'));
    }

    // URL for the "See more" link for post types (e.g., "edit.php?post_type=journee").
    case 'see_more_posts': {
        $post_type = $extra['post_type'] ?? '';
        $base_url = 'edit.php';
        return add_query_arg('post_type', $post_type, admin_url($base_url));
    }

    // URL for the "See more" link for taxonomies (e.g., "edit-tags.php?taxonomy=category").
    case 'see_more_terms': {
        $taxonomy = $extra['taxonomy'] ?? '';
        $post_type = $extra['post_type'] ?? 'post';
        $args = ['taxonomy' => $taxonomy];
        if ($post_type !== 'post') { // post_type is implicit for 'post'
            $args['post_type'] = $post_type;
        }
        return add_query_arg($args, admin_url('edit-tags.php'));
    }

    default:
        return '';
  }
}

/**
 * Registers all dynamic admin submenus.
 */
function register_all_submenus() {
    if (!current_user_can('edit_posts')) {
        return;
    }

    $config = get_submenu_config();

    // Post type submenus
    foreach ($config['post_types'] as $post_type) {
        register_post_type_submenus($post_type);
    }

    // Taxonomy submenus
    foreach ($config['taxonomies'] as $taxonomy) {
        register_taxonomy_submenus($taxonomy);
    }

    // User submenus
    if (current_user_can('list_users')) {
        register_user_submenus();
    }
}

/**
 * Registers submenu items for a post type.
 */
function register_post_type_submenus($post_type) {
    $posts_data = get_submenu_posts($post_type);
    if (empty($posts_data['items'])) return;

    $post_type_obj = get_post_type_object($post_type);
    $parent_slug = "edit.php?post_type={$post_type}";
    $capability = $post_type_obj->cap->edit_posts ?? 'edit_posts';

    foreach ($posts_data['items'] as $post) {
        add_submenu_page(
            $parent_slug,
            esc_html($post->post_title),
            '<span aria-hidden="true">&nbsp;&nbsp;-&nbsp;</span>' . esc_html($post->post_title),
            $capability,
            generate_submenu_url('post', $post)
        );
    }

    if ($posts_data['has_more']) {
        add_submenu_page(
            $parent_slug,
            __('See more →', get_admin_submenu_textdomain()),
            '<span class="see-more-link">' . esc_html__('See more →', get_admin_submenu_textdomain()) . '</span>',
            $capability,
            generate_submenu_url('see_more_posts', null, ['post_type' => $post_type])
        );
    }
}

/**
 * Registers submenu items for a taxonomy.
 */
function register_taxonomy_submenus($taxonomy) {
    $terms_data = get_submenu_terms($taxonomy);
    if (empty($terms_data['items'])) return;

    $taxonomy_obj = get_taxonomy($taxonomy);
    $post_type = $taxonomy_obj->object_type[0] ?? 'post';
    $parent_slug = "edit-tags.php?taxonomy={$taxonomy}";
    if ($post_type !== 'post') $parent_slug .= "&post_type={$post_type}";
    $capability = $taxonomy_obj->cap->edit_terms ?? 'manage_categories';

    foreach ($terms_data['items'] as $term) {
        add_submenu_page(
            $parent_slug,
            esc_html($term->name),
            '<span aria-hidden="true">&nbsp;&nbsp;-&nbsp;</span>' . esc_html($term->name),
            $capability,
            generate_submenu_url('term', $term, ['taxonomy' => $taxonomy, 'post_type' => $post_type])
        );
    }

    if ($terms_data['has_more']) {
        add_submenu_page(
            $parent_slug,
            __('See more →', get_admin_submenu_textdomain()),
            '<span class="see-more-link">' . esc_html__('See more →', get_admin_submenu_textdomain()) . '</span>',
            $capability,
            generate_submenu_url('see_more_terms', null, ['taxonomy' => $taxonomy, 'post_type' => $post_type])
        );
    }
}

/**
 * Registers user submenus grouped by roles.
 */
function register_user_submenus() {
    if (!current_user_can('list_users')) {
        return;
    }

    $parent_slug = 'users.php';
    $capability = 'list_users';
    $config = get_submenu_config();

    foreach ($config['user_roles'] as $role) {
        $users_data = get_submenu_users_by_role($role);
        if (empty($users_data['items'])) {
            continue;
        }

        $role_obj = get_role($role);
        $role_name = $role_obj ? translate_user_role($role_obj->name) : $role;

        // Add a non-clickable header for the role group.
        add_submenu_page(
            $parent_slug,
            "{$role_name} ({$users_data['total']})",
            '<span class="role-name">' . esc_html($role_name) . '</span><span class="dotted-line" aria-hidden="true"></span><span class="user-count">(' . intval($users_data['total']) . ')</span>',
            $capability,
            '#'
        );

        // Add individual user links for that role.
        foreach ($users_data['items'] as $user) {
            $display_name = $user->display_name ?: $user->user_login;
            add_submenu_page(
                $parent_slug,
                esc_html($display_name),
                '<span aria-hidden="true">&nbsp;&nbsp;-&nbsp;</span>' . esc_html($display_name),
                $capability,
                generate_submenu_url('user', $user)
            );
        }

        // Add a "See more" link if applicable.
        if ($users_data['has_more']) {
            add_submenu_page(
                $parent_slug,
                sprintf(__('See more %s', get_admin_submenu_textdomain()), $role_name),
                '<span class="see-more-link">' . __('See more →', get_admin_submenu_textdomain()) . '</span>',
                $capability,
                generate_submenu_url('user_role', null, ['role' => $role])
            );
        }
    }
}

// Hook into admin menu
add_action('admin_menu', 'register_all_submenus', 999);

/**
 * Injects custom CSS and a small JavaScript snippet into the admin head.
 * This is used to style the dynamic submenus for better readability and usability,
 * such as making them scrollable and adding visual separators for user roles.
 */
function admin_submenu_assets() {
    if (!current_user_can('edit_posts')) {
        return;
    }
  ?>
    <style>
      /* Make long submenus scrollable instead of extending off-screen */
      #adminmenu .wp-submenu {
          min-width: 160px !important;
          max-height: 60vh;
          overflow-y: auto;
      }
      #adminmenu .wp-submenu a {
          white-space: normal !important;
          word-wrap: break-word !important;
      }
      /* Indent individual item links for a cleaner hierarchy */
      #adminmenu .wp-submenu li a[href*="post.php?post="],
      #adminmenu .wp-submenu li a[href*="term.php?taxonomy="],
      #adminmenu .wp-submenu li a[href*="user-edit.php?user_id="] {
          text-indent: -18px !important;
          padding-left: 26px !important;
          padding-right: 20px !important;
      }
      /* Style the user role headers (e.g., "Editors ....... (5)") */
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
      /* Style the "See more ->" links */
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
add_action('admin_head', 'admin_submenu_assets');