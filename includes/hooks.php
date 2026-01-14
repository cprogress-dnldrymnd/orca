<?php
add_filter('get_the_archive_title', function ($title) {
    if (is_category()) {
        $title = single_cat_title('', false);
    } elseif (is_tag()) {
        $title = single_tag_title('', false);
    } elseif (is_author()) {
        $title = '<span class="vcard">' . get_the_author() . '</span>';
    } elseif (is_tax()) { //for custom post types
        $title = sprintf(__('%1$s'), single_term_title('', false));
    } elseif (is_post_type_archive()) {
        $title = post_type_archive_title('', false);
    }
    return $title;
});


add_action('admin_init', function () {
    // Redirect any user trying to access comments page
    global $pagenow;

    if ($pagenow === 'edit-comments.php') {
        wp_safe_redirect(admin_url());
        exit;
    }

    // Remove comments metabox from dashboard
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');

    // Disable support for comments and trackbacks in post types
    foreach (get_post_types() as $post_type) {
        if (post_type_supports($post_type, 'comments')) {
            remove_post_type_support($post_type, 'comments');
            remove_post_type_support($post_type, 'trackbacks');
        }
    }
});

// Close comments on the front-end
add_filter('comments_open', '__return_false', 20, 2);
add_filter('pings_open', '__return_false', 20, 2);

// Hide existing comments
add_filter('comments_array', '__return_empty_array', 10, 2);

// Remove comments page in menu
add_action('admin_menu', function () {
    remove_menu_page('edit-comments.php');
});

// Remove comments links from admin bar
add_action('init', function () {
    if (is_admin_bar_showing()) {
        remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
    }
});

/**
 * 1. Register the Meta Box
 */
function beacon_register_crm_log_metabox() {
    add_meta_box(
        'beacon_crm_log_details',      // Unique ID for the meta box
        'CRM Log Information',         // Title displayed to the user
        'beacon_render_crm_log_box',   // Callback function to render the HTML
        'beaconcrmlogs',               // Post type key
        'normal',                      // Context (normal, side, advanced)
        'high'                         // Priority (high, low, default)
    );
}
add_action('add_meta_boxes', 'beacon_register_crm_log_metabox');

/**
 * 2. Render the Meta Box Content
 */
function beacon_render_crm_log_box($post) {
    // Fetch the meta values
    $log_type    = get_post_meta($post->ID, 'type', true);
    $api_url     = get_post_meta($post->ID, 'api_url', true);
    $log_args    = get_post_meta($post->ID, 'args', true);
    $log_return  = get_post_meta($post->ID, 'return', true);

    // CSS for basic styling to make it look clean
    ?>
    <style>
        .beacon-log-row { margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        .beacon-log-label { font-weight: bold; display: block; margin-bottom: 5px; font-size: 13px; color: #2c3338; }
        .beacon-log-code { background: #f0f0f1; padding: 10px; border: 1px solid #ccc; max-height: 300px; overflow: auto; font-family: monospace; }
        .beacon-log-value { font-size: 14px; }
    </style>

    <div class="beacon-crm-log-container">
        <div class="beacon-log-row">
            <span class="beacon-log-label">Type:</span>
            <div class="beacon-log-value">
                <?php echo esc_html($log_type ? $log_type : 'N/A'); ?>
            </div>
        </div>

        <div class="beacon-log-row">
            <span class="beacon-log-label">API URL:</span>
            <div class="beacon-log-value">
                <?php if ($api_url): ?>
                    <a href="<?php echo esc_url($api_url); ?>" target="_blank">
                        <?php echo esc_html($api_url); ?>
                    </a>
                <?php else: ?>
                    N/A
                <?php endif; ?>
            </div>
        </div>

        <div class="beacon-log-row">
            <span class="beacon-log-label">Request Arguments (Args):</span>
            <div class="beacon-log-code">
                <pre><?php echo esc_html(print_r($log_args, true)); ?></pre>
            </div>
        </div>

        <div class="beacon-log-row" style="border-bottom: none;">
            <span class="beacon-log-label">API Response (Return):</span>
            <div class="beacon-log-code">
                <pre><?php echo esc_html(print_r($log_return, true)); ?></pre>
            </div>
        </div>
    </div>
    <?php
}