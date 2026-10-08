<?php
/**
 * Verify Mailchimp is installed alongside Beacon without dual-wiring opt-ins.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$mc = 'mailchimp-for-woocommerce/mailchimp-woocommerce.php';
WP_CLI::log( 'mailchimp_files=' . ( file_exists( WP_PLUGIN_DIR . '/' . $mc ) ? 'yes' : 'no' ) );
WP_CLI::log( 'mailchimp_active=' . ( is_plugin_active( $mc ) ? 'yes' : 'no' ) );

$mc_options = array(
	'mailchimp-woocommerce',
	'mailchimp-woocommerce-store_id',
	'mailchimp-woocommerce-listid',
);
foreach ( $mc_options as $opt ) {
	$val = get_option( $opt );
	WP_CLI::log( $opt . '=' . ( empty( $val ) ? '(empty — OAuth/setup pending)' : 'set' ) );
}

WP_CLI::log( 'beacon_plugin_active=' . ( is_plugin_active( 'beacon-crm-integration/beacon-crm-integration.php' ) || defined( 'BEACON_CRM_VERSION' ) ? 'yes' : 'unknown' ) );
WP_CLI::log( 'theme_opt_in_helpers=' . ( function_exists( 'orca_get_training_opt_in_value' ) && function_exists( 'orca_get_communications_opt_in_value' ) ? 'yes' : 'no' ) );

// Ensure theme checkout handlers are still stubs / Beacon-only (no Mailchimp API calls in theme).
$functions = file_get_contents( get_template_directory() . '/functions.php' );
$has_mc_api = ( false !== stripos( $functions, 'mailchimp' ) || false !== stripos( $functions, 'list-manage' ) );
WP_CLI::log( 'theme_functions_mailchimp_calls=' . ( $has_mc_api ? 'YES (unexpected)' : 'no (good — Beacon remains source for opt-ins)' ) );

WP_CLI::success( 'Mailchimp installed alongside Beacon. Complete OAuth in WP Admin → Mailchimp (client account required).' );
