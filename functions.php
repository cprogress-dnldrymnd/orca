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
	} else if (is_product()) {
		wp_enqueue_script('single-product', assets_dir . 'javascripts/single-product.js', array(), orca_version, true);
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

if ( ! defined( 'ORCA_TRAINING_OPT_IN_LABEL' ) ) {
	define( 'ORCA_TRAINING_OPT_IN_LABEL', "Yes, I'd like to receive emails on how to access this training course, information regarding our OceanWatchers app, and how to access the app." );
}

if ( ! defined( 'ORCA_COMMUNICATIONS_OPT_IN_LABEL' ) ) {
	define( 'ORCA_COMMUNICATIONS_OPT_IN_LABEL', "Yes, I'd like to receive additional communications about ORCA's work, updates, fundraising, and opportunities to get involved." );
}

add_action( 'woocommerce_init', function() {
	if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
		return;
	}

	woocommerce_register_additional_checkout_field(
		array(
			'id'       => 'orca-learn/training-opt-in',
			'label'    => __( ORCA_TRAINING_OPT_IN_LABEL, 'orca-learn' ),
			'location' => 'contact',
			'type'     => 'checkbox',
			'required' => false,
		)
	);

	woocommerce_register_additional_checkout_field(
		array(
			'id'       => 'orca-learn/communications-opt-in',
			'label'    => __( ORCA_COMMUNICATIONS_OPT_IN_LABEL, 'orca-learn' ),
			'location' => 'contact',
			'type'     => 'checkbox',
			'required' => false,
		)
	);
} );

/**
 * Normalise different stored checkbox values into Yes/No.
 */
function orca_normalise_opt_in_value( $value ) {
	if ( is_array( $value ) ) {
		$value = reset( $value );
	}

	if ( is_bool( $value ) ) {
		return $value ? 'Yes' : 'No';
	}

	$value = strtolower( trim( (string) $value ) );

	if ( in_array( $value, array( '1', 'yes', 'true', 'on', 'checked' ), true ) ) {
		return 'Yes';
	}

	return 'No';
}

/**
 * Get opt-in value from an order.
 *
 * WooCommerce Additional Checkout Fields can display the full field label
 * in the order admin, so we check both the field ID and the label/meta fallback.
 */
function orca_get_order_opt_in_value( $order, $short_key, $label ) {
	if ( ! $order instanceof WC_Order ) {
		return 'No';
	}

	$possible_keys = array(
		$short_key,
		'_' . $short_key,
		$label,
		$label . ':',
	);

	foreach ( $possible_keys as $key ) {
		$value = $order->get_meta( $key, true );

		if ( '' !== $value && null !== $value ) {
			return orca_normalise_opt_in_value( $value );
		}
	}

	/**
	 * Final fallback: search all order meta keys in case WooCommerce stores
	 * the additional checkout field under a generated/full label key.
	 */
	foreach ( $order->get_meta_data() as $meta ) {
		$key   = (string) $meta->key;
		$value = $meta->value;

		if (
			false !== stripos( $key, $short_key ) ||
			false !== stripos( $key, trim( $label ) ) ||
			false !== stripos( $key, 'training-opt-in' ) && false !== stripos( $short_key, 'training-opt-in' ) ||
			false !== stripos( $key, 'communications-opt-in' ) && false !== stripos( $short_key, 'communications-opt-in' )
		) {
			return orca_normalise_opt_in_value( $value );
		}
	}

	return 'No';
}

function orca_get_training_opt_in_value( $order ) {
	return orca_get_order_opt_in_value(
		$order,
		'orca-learn/training-opt-in',
		ORCA_TRAINING_OPT_IN_LABEL
	);
}

function orca_get_communications_opt_in_value( $order ) {
	return orca_get_order_opt_in_value(
		$order,
		'orca-learn/communications-opt-in',
		ORCA_COMMUNICATIONS_OPT_IN_LABEL
	);
}

add_action( 'woocommerce_store_api_checkout_order_processed', function( $order ) {
	if ( 'Yes' === orca_get_training_opt_in_value( $order ) ) {
		$email = $order->get_billing_email();
		// e.g. subscribe to training/app email list
	}

	if ( 'Yes' === orca_get_communications_opt_in_value( $order ) ) {
		$email = $order->get_billing_email();
		// e.g. subscribe to ORCA communications list
	}
} );

/**
 * Retain UTM parameters across the website.
 */
function disruptive_retain_utm_parameters() {
	if ( is_admin() ) {
		return;
	}
	?>
	<script>
	(function () {
		var trackedParams = [
			'utm_source',
			'utm_medium',
			'utm_campaign',
			'utm_term',
			'utm_content',
			'gclid',
			'fbclid',
			'msclkid'
		];

		function getStoredParams() {
			return new URLSearchParams(sessionStorage.getItem('disruptive_tracking_params') || '');
		}

		function saveCurrentParams() {
			var currentParams = new URLSearchParams(window.location.search);
			var storedParams = getStoredParams();
			var found = false;

			trackedParams.forEach(function (param) {
				if (currentParams.has(param)) {
					storedParams.set(param, currentParams.get(param));
					found = true;
				}
			});

			if (found) {
				sessionStorage.setItem('disruptive_tracking_params', storedParams.toString());
			}
		}

		function addParamsToUrl(url) {
			var storedParams = getStoredParams();

			if (!storedParams.toString()) {
				return url;
			}

			var newUrl;

			try {
				newUrl = new URL(url, window.location.origin);
			} catch (e) {
				return url;
			}

			if (newUrl.hostname !== window.location.hostname) {
				return url;
			}

			storedParams.forEach(function (value, key) {
				if (!newUrl.searchParams.has(key)) {
					newUrl.searchParams.set(key, value);
				}
			});

			return newUrl.toString();
		}

		saveCurrentParams();

		document.addEventListener('click', function (event) {
			var link = event.target.closest('a');

			if (!link || !link.href) {
				return;
			}

			var href = link.getAttribute('href');

			if (
				!href ||
				href.indexOf('#') === 0 ||
				href.indexOf('mailto:') === 0 ||
				href.indexOf('tel:') === 0 ||
				href.indexOf('javascript:') === 0
			) {
				return;
			}

			link.href = addParamsToUrl(link.href);
		});
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'disruptive_retain_utm_parameters', 99 );

/**
 * Store UTMs in cookies when someone lands on the site.
 */
add_action( 'init', function() {
	$utm_keys = array(
		'utm_source',
		'utm_medium',
		'utm_campaign',
		'utm_term',
		'utm_content',
		'gclid',
		'fbclid',
		'msclkid',
	);

	foreach ( $utm_keys as $key ) {
		if ( isset( $_GET[ $key ] ) && '' !== $_GET[ $key ] ) {
			setcookie(
				$key,
				sanitize_text_field( wp_unslash( $_GET[ $key ] ) ),
				time() + ( 30 * DAY_IN_SECONDS ),
				COOKIEPATH,
				COOKIE_DOMAIN
			);
		}
	}
} );

/**
 * Add hidden UTM fields to classic WooCommerce checkout.
 */
add_action( 'woocommerce_after_order_notes', function( $checkout ) {
	$utm_keys = array(
		'utm_source',
		'utm_medium',
		'utm_campaign',
		'utm_term',
		'utm_content',
		'gclid',
		'fbclid',
		'msclkid',
	);

	foreach ( $utm_keys as $key ) {
		$value = isset( $_COOKIE[ $key ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $key ] ) ) : '';

		woocommerce_form_field(
			$key,
			array(
				'type'  => 'hidden',
				'class' => array( 'form-row-wide' ),
			),
			$value
		);
	}
} );

/**
 * Save UTMs to the WooCommerce order.
 */
add_action( 'woocommerce_checkout_create_order', function( $order, $data ) {
	$utm_keys = array(
		'utm_source',
		'utm_medium',
		'utm_campaign',
		'utm_term',
		'utm_content',
		'gclid',
		'fbclid',
		'msclkid',
	);

	foreach ( $utm_keys as $key ) {
		if ( isset( $_POST[ $key ] ) && '' !== $_POST[ $key ] ) {
			$order->update_meta_data(
				'_' . $key,
				sanitize_text_field( wp_unslash( $_POST[ $key ] ) )
			);
		}
	}
}, 10, 2 );


require_once('includes/beacon-orders-export.php');
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