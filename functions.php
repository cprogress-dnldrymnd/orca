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

require_once('includes/bootstrap-navwalker.php');
require_once('includes/menus.php');
require_once('includes/theme-widgets.php');
require_once('includes/post-types.php');
require_once('includes/learndash.php');
require_once('includes/shortcodes.php');
require_once('includes/hooks.php');
require_once('includes/woocommerce.php');
require_once('includes/ajax.php');
require_once('includes/beacon.php');