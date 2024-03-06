<?php

/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * https://developers.elementor.com/docs/hello-elementor-theme/
 *
 * @package HelloElementorChild
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Load child theme scripts & styles.
 *
 * @return void
 */
function action_wp_enqueue_scripts()
{

	wp_enqueue_style(
		'style',
		get_stylesheet_directory_uri() . '/style.css',
	);
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


require_once('includes/menus.php');
require_once('includes/theme-widgets.php');
require_once('includes/post-types.php');
require_once('includes/learndash.php');
require_once('includes/shortcodes.php');
require_once('includes/hooks.php');
require_once('includes/woocommerce.php');
