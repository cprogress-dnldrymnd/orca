<?php
/**
 * Configure (or create) a Name Your Price simple product linked to a LearnDash course.
 *
 * Usage:
 *   wp eval-file wp-content/themes/orca/bin/setup-nyp-product.php \
 *     -- course_id=953 suggested=20 minimum=0 maximum=0 product_id=0
 *
 * Requires WooCommerce Name Your Price to be installed and active.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( ! function_exists( 'WC' ) || ! class_exists( 'WC_Product_Simple' ) ) {
	WP_CLI::error( 'WooCommerce is required.' );
}

if ( ! class_exists( 'WC_Name_Your_Price' ) && ! function_exists( 'WC_Name_Your_Price_Helpers' ) ) {
	WP_CLI::error( 'WooCommerce Name Your Price is not installed/active. Purchase and install it first.' );
}

$assoc = array();
foreach ( array_slice( $GLOBALS['argv'] ?? array(), 1 ) as $arg ) {
	if ( false !== strpos( $arg, '=' ) ) {
		list( $k, $v ) = explode( '=', $arg, 2 );
		$assoc[ $k ]   = $v;
	}
}

// WP-CLI eval-file passes extras after -- via $args in some versions; also accept env.
$course_id  = isset( $assoc['course_id'] ) ? absint( $assoc['course_id'] ) : absint( getenv( 'ORCA_NYP_COURSE_ID' ) );
$suggested  = isset( $assoc['suggested'] ) ? (float) $assoc['suggested'] : (float) ( getenv( 'ORCA_NYP_SUGGESTED' ) ?: 20 );
$minimum    = isset( $assoc['minimum'] ) ? (float) $assoc['minimum'] : (float) ( getenv( 'ORCA_NYP_MINIMUM' ) ?: 0 );
$maximum    = isset( $assoc['maximum'] ) ? (float) $assoc['maximum'] : (float) ( getenv( 'ORCA_NYP_MAXIMUM' ) ?: 0 );
$product_id = isset( $assoc['product_id'] ) ? absint( $assoc['product_id'] ) : absint( getenv( 'ORCA_NYP_PRODUCT_ID' ) );

if ( ! $course_id ) {
	WP_CLI::error( 'Provide course_id=ID (LearnDash course to sell as pay-as-you-want).' );
}

$course = get_post( $course_id );
if ( ! $course || 'sfwd-courses' !== $course->post_type ) {
	WP_CLI::error( "Invalid course_id {$course_id}." );
}

if ( $product_id ) {
	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		WP_CLI::error( "Invalid product_id {$product_id}." );
	}
} else {
	$product = new WC_Product_Simple();
	$product->set_name( $course->post_title . ' (Pay as you want)' );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_sold_individually( true );
}

$product->set_regular_price( $suggested > 0 ? (string) $suggested : '' );
$product->set_price( $suggested > 0 ? (string) $suggested : '' );
$product->update_meta_data( '_nyp', 'yes' );
$product->update_meta_data( '_suggested_price', $suggested > 0 ? wc_format_decimal( $suggested ) : '' );
$product->update_meta_data( '_min_price', wc_format_decimal( $minimum ) );
if ( $maximum > 0 ) {
	$product->update_meta_data( '_maximum_price', wc_format_decimal( $maximum ) );
} else {
	$product->delete_meta_data( '_maximum_price' );
}
$product->update_meta_data( '_related_course', array( $course_id ) );
$product_id = $product->save();

WP_CLI::success( "Name Your Price product #{$product_id} linked to course #{$course_id}." );
WP_CLI::log( "Suggested: {$suggested} | Min: {$minimum} | Max: " . ( $maximum > 0 ? $maximum : 'none' ) );
WP_CLI::log( 'Edit URL: ' . admin_url( 'post.php?post=' . $product_id . '&action=edit' ) );
WP_CLI::log( 'Product URL: ' . get_permalink( $product_id ) );
