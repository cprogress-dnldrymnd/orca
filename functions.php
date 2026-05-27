<?php

/*-----------------------------------------------------------------------------------*/
/* Define the version so we can easily replace it throughout the theme
/*-----------------------------------------------------------------------------------*/
define('orca_version', 1);
define('theme_dir', get_template_directory_uri() . '/');
define('assets_dir', theme_dir . 'assets/');
define('image_dir', assets_dir . 'images/');
define('vendor_dir', assets_dir . 'vendors/');
add_action('after_setup_theme', 'setup_woocommerce_support');

function setup_woocommerce_support()
{
	add_theme_support('woocommerce');
	add_theme_support('wc-product-gallery-zoom');
	add_theme_support('wc-product-gallery-lightbox');
	add_theme_support('wc-product-gallery-slider');
}

function action_wp_enqueue_scripts()
{
	wp_enqueue_style('style', theme_dir . 'style.css');
	wp_enqueue_script('bootstrap', vendor_dir . 'bootstrap/dist/js/bootstrap.min.js');
	wp_register_script('swiper', vendor_dir . 'swiper/js/swiper-bundle.min.js');
	if (is_post_type_archive('sfwd-courses') || is_tax('ld_course_category') || is_shop() || is_front_page()) {
		wp_enqueue_script('archive-course', assets_dir . 'javascripts/archive-course.js', array('jquery'));
		// in JavaScript, object properties are accessed as ajax_object.ajax_url
		wp_localize_script(
			'archive-course',
			'ajax_object',
			array(
				'ajax_url' => admin_url('admin-ajax.php')
			)
		);
	} else if (is_single() && get_post_type() == 'sfwd-courses') {
		wp_enqueue_script('single-course', assets_dir . 'javascripts/single-course.js', array('jquery', 'swiper'));
	}
}
add_action('wp_enqueue_scripts', 'action_wp_enqueue_scripts', 20);

/*-----------------------------------------------------------------------------------*/
/* Register Carbofields
/*-----------------------------------------------------------------------------------*/
add_action('carbon_fields_register_fields', 'tissue_paper_register_custom_fields');
function tissue_paper_register_custom_fields()
{
	require_once('includes/post-meta.php');
}
function get__post_meta($value)
{
	return get_post_meta(get_the_ID(), '_' . $value, true);
}

function get__term_meta($term_id, $value)
{
	return get_term_meta($term_id, '_' . $value, true);
}

function get__post_meta_by_id($id, $value)
{
	return get_post_meta($id, '_' . $value, true);
}
function get__theme_option($value)
{
	return get_option('_' . $value);
}

function arrayKeyStartsWith($array, $prefix) {
    $matchingKeys = [];
    foreach ($array as $key => $value) {
        if (strpos($key, $prefix) === 0) {
            $matchingKeys[$key] = $value;
        }
    }
    return $matchingKeys;
}
/* opt in to marketing fields at checkout */
add_action( 'woocommerce_init', function() {
    if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
        return;
    }
    woocommerce_register_additional_checkout_field(
        array(
            'id'       => 'orca-learn/training-opt-in',
            'label'    => __( "Yes, I'd like to receive emails on how to access this training course, information regarding our OceanWatchers app, and how to access the app.", 'orca-learn' ),
            'location' => 'contact',
            'type'     => 'checkbox',
            'required' => false,
        )
    );
    woocommerce_register_additional_checkout_field(
        array(
            'id'       => 'orca-learn/communications-opt-in',
            'label'    => __( "Yes, I'd like to receive additional communications about ORCA's work, updates, fundraising, and opportunities to get involved.", 'orca-learn' ),
            'location' => 'contact',
            'type'     => 'checkbox',
            'required' => false,
        )
    );
} );
add_action( 'woocommerce_store_api_checkout_order_processed', function( $order ) {
    $training_opt_in       = $order->get_meta( 'orca-learn/training-opt-in' );
    $communications_opt_in = $order->get_meta( 'orca-learn/communications-opt-in' );
    if ( $training_opt_in === '1' ) {
        $email = $order->get_billing_email();
        // e.g. subscribe to training/app email list
    }
    if ( $communications_opt_in === '1' ) {
        $email = $order->get_billing_email();
        // e.g. subscribe to ORCA communications list
    }
} );


require_once('includes/bootstrap-navwalker.php');
require_once('includes/menus.php');
require_once('includes/theme-widgets.php');
require_once('includes/post-types.php');
require_once('includes/learndash.php');
require_once('includes/shortcodes.php');
require_once('includes/hooks.php');
require_once('includes/woocommerce.php');
require_once('includes/ajax.php');
require_once('includes/wc-redirect-manager.php');


/**
 * Temporary Utility: Export Legacy Beacon CRM Product Mappings
 * Adds a page under Tools > Beacon Data Export to view all mapped products.
 */

add_action('admin_menu', function() {
    add_management_page(
        'Beacon CRM Data Export',
        'Beacon Data Export',
        'manage_options',
        'beacon-data-export',
        'render_beacon_data_export_page'
    );
});

function render_beacon_data_export_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    echo '<div class="wrap"><h1>Beacon CRM Product Mappings (Legacy)</h1>';
    echo '<p>Use this reference table to map your existing Beacon CRM data to your LearnDash courses after updating the plugin.</p>';
    echo '<table class="wp-list-table widefat fixed striped" style="margin-top: 15px;">';
    echo '<thead><tr>
            <th style="width: 15%;">WC Product ID</th>
            <th style="width: 35%;">Product Name</th>
            <th style="width: 20%;">Linked LearnDash Course ID</th>
            <th style="width: 30%;">Beacon Data</th>
          </tr></thead>';
    echo '<tbody>';

    $args = [
        'post_type'      => ['product', 'product_variation'],
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'meta_query'     => [
            'relation' => 'OR',
            [
                'key'     => '_beacon_courses_data',
                'compare' => 'EXISTS'
            ],
            [
                'key'     => '_beacon_id',
                'compare' => 'EXISTS'
            ]
        ]
    ];

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id    = get_the_ID();
            $post_title = get_the_title();
            
            // Format Variation Titles
            if (get_post_type() === 'product_variation') {
                $parent_id  = wp_get_post_parent_id($post_id);
                $post_title = get_the_title($parent_id) . ' <strong>(Variation #' . $post_id . ')</strong>';
            }

            // Retrieve linked LearnDash Course ID(s) to make manual migration easier
            $ld_courses = get_post_meta($post_id, '_related_course', true);
            if (is_array($ld_courses)) {
                $ld_courses_display = implode(', ', array_map('intval', $ld_courses));
            } else {
                $ld_courses_display = $ld_courses ? intval($ld_courses) : '<em>None Linked</em>';
            }

            $beacon_data_display = [];

            // 1. Extract Parent/Simple Repeater Data
            $repeater_data = get_post_meta($post_id, '_beacon_courses_data', true);
            if (!empty($repeater_data) && is_array($repeater_data)) {
                foreach ($repeater_data as $row) {
                    if (!empty($row['id']) && !empty($row['type'])) {
                        $beacon_data_display[] = 'ID: <strong>' . esc_html($row['id']) . '</strong> | Type: <strong>' . esc_html($row['type']) . '</strong>';
                    }
                }
            }

            // 2. Extract Variation Data
            $var_id   = get_post_meta($post_id, '_beacon_id', true);
            $var_type = get_post_meta($post_id, '_beacon_course_type', true);
            if ($var_id && $var_type) {
                $beacon_data_display[] = 'ID: <strong>' . esc_html($var_id) . '</strong> | Type: <strong>' . esc_html($var_type) . '</strong>';
            }

            // Skip rendering if meta keys exist but the arrays/strings are actually empty
            if (empty($beacon_data_display)) {
                continue; 
            }

            echo '<tr>';
            echo '<td>' . esc_html($post_id) . '</td>';
            echo '<td>' . wp_kses_post($post_title) . '</td>';
            echo '<td>' . wp_kses_post($ld_courses_display) . '</td>';
            echo '<td>' . implode('<br>', $beacon_data_display) . '</td>';
            echo '</tr>';
        }
        wp_reset_postdata();
    } else {
        echo '<tr><td colspan="4">No Beacon CRM mapping data found.</td></tr>';
    }

    echo '</tbody></table></div>';
}
