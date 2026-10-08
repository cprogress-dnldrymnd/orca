<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$p = wc_get_product( 1412 );
WP_CLI::log( 'type=' . ( $p ? $p->get_type() : 'none' ) );
WP_CLI::log( 'class=' . ( $p ? get_class( $p ) : 'none' ) );
WP_CLI::log( 'open=' . ( $p && orca_product_is_open_amount( $p ) ? 'yes' : 'no' ) );
WP_CLI::log( 'suggested=' . ( $p ? $p->get_meta( '_orca_pwyw_suggested' ) : '' ) );

$types = apply_filters( 'product_type_selector', array( 'simple' => 'Simple' ) );
WP_CLI::log( 'has_type_in_selector=' . ( isset( $types['pay_as_you_want'] ) ? 'yes' : 'no' ) );
WP_CLI::log( 'class_exists=' . ( class_exists( 'WC_Product_Pay_As_You_Want' ) ? 'yes' : 'no' ) );

$class = apply_filters( 'learndash_woocommerce_course_selector_class', 'options_group show_if_course show_if_simple', null );
WP_CLI::log( 'ld_selector_class=' . $class );

WP_CLI::success( 'PWYW product type OK' );
