<?php
/**
 * Enable Pay as you want product type on the WooCommerce product for a LearnDash course.
 *
 * Default: course 953, suggested £15, minimum £0.
 *
 *   wp eval-file wp-content/themes/orca/bin/setup-pwyw-product.php
 */

if ( ! defined( 'ABSPATH' ) || ! function_exists( 'WC' ) ) {
	WP_CLI::error( 'WooCommerce required.' );
}

$course_id = 953;
$suggested = 15.0;
$minimum   = 0.0;

$course = get_post( $course_id );
if ( ! $course || 'sfwd-courses' !== $course->post_type ) {
	WP_CLI::error( "Course {$course_id} not found." );
}

$products = get_posts(
	array(
		'post_type'      => 'product',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_query'     => array(
			array(
				'key'     => '_related_course',
				'value'   => serialize( intval( $course_id ) ),
				'compare' => 'LIKE',
			),
		),
		'orderby'        => 'ID',
		'order'          => 'ASC',
	)
);

if ( empty( $products ) ) {
	WP_CLI::error( "No WooCommerce product linked to course {$course_id}." );
}

$product_id = 0;
foreach ( $products as $pid ) {
	$related = get_post_meta( $pid, '_related_course', true );
	if ( is_array( $related ) && 1 === count( $related ) && (int) $related[0] === $course_id ) {
		$product_id = (int) $pid;
		break;
	}
}
if ( ! $product_id ) {
	$product_id = (int) $products[0];
}

// Ensure taxonomy term exists and assign product type.
if ( ! term_exists( 'pay_as_you_want', 'product_type' ) ) {
	wp_insert_term( 'pay_as_you_want', 'product_type' );
}
wp_set_object_terms( $product_id, 'pay_as_you_want', 'product_type' );
clean_post_cache( $product_id );

$product = wc_get_product( $product_id );
if ( ! $product ) {
	WP_CLI::error( "Could not load product {$product_id}." );
}

$product->set_sold_individually( true );
$product->set_regular_price( (string) $suggested );
$product->set_price( (string) $suggested );
$product->update_meta_data( '_orca_pwyw_suggested', wc_format_decimal( $suggested ) );
$product->update_meta_data( '_orca_pwyw_min', wc_format_decimal( $minimum ) );
$product->delete_meta_data( '_orca_pwyw_max' );
// Clear legacy flag; type is authoritative.
$product->delete_meta_data( '_orca_pwyw' );
$product->update_meta_data( '_related_course', array( $course_id ) );
$product->save();

WP_CLI::success( "Product #{$product_id} type=pay_as_you_want → course #{$course_id}" );
WP_CLI::log( 'Type: ' . $product->get_type() );
WP_CLI::log( 'Suggested: £' . $suggested . ' | Min: £' . $minimum );
WP_CLI::log( 'Product URL: ' . get_permalink( $product_id ) );
WP_CLI::log( 'Admin: Products → edit product → Product data → “Pay as you want”.' );
