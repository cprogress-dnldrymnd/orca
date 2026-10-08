<?php
/**
 * Activate Mailchimp for WooCommerce without running remote setup.
 * wp eval-file wp-content/themes/orca/bin/activate-mailchimp.php --skip-plugins=wordfence
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$plugin = 'mailchimp-for-woocommerce/mailchimp-woocommerce.php';
if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin ) ) {
	WP_CLI::error( 'Plugin files not found.' );
}

$active = get_option( 'active_plugins', array() );
if ( ! in_array( $plugin, $active, true ) ) {
	$active[] = $plugin;
	sort( $active );
	update_option( 'active_plugins', $active );
	WP_CLI::success( 'Mailchimp for WooCommerce added to active_plugins.' );
} else {
	WP_CLI::success( 'Mailchimp for WooCommerce already active.' );
}

WP_CLI::log( 'Next: open WP Admin → Mailchimp and complete OAuth / audience mapping (client credentials required).' );
WP_CLI::log( 'Beacon CRM remains the source for checkout training/communications opt-ins.' );
