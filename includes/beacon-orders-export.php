<?php

/**
 * ORCA Beacon Orders Export
 * Adds export button to WooCommerce > Orders.
 */
add_action( 'admin_notices', function() {
	$screen = get_current_screen();

	if ( ! $screen || 'woocommerce_page_wc-orders' !== $screen->id ) {
		return;
	}

	$export_url = wp_nonce_url(
		admin_url( 'admin-post.php?action=orca_beacon_orders_export' ),
		'orca_beacon_orders_export'
	);

	echo '<div class="notice notice-info" style="padding:12px;">';
	echo '<strong>Beacon Export:</strong> ';
	echo '<a href="' . esc_url( $export_url ) . '" class="button button-primary">Download Beacon Orders CSV</a>';
	echo '</div>';
} );

/**
 * Resolve an order-attribution value for a given UTM/click-id key.
 *
 * Reads the theme's custom `_utm_*` meta first (saved from the classic checkout
 * in functions.php), and falls back to WooCommerce's native Order Attribution
 * meta (`_wc_order_attribution_*`), which is what's captured on the block-based
 * checkout. Either mechanism can be the one populated depending on the checkout
 * in use, so we check both.
 */
function orca_get_attribution_value( $order, $key ) {
	if ( ! $order instanceof WC_Order ) {
		return '';
	}

	// Theme custom meta: `_utm_source`, `_gclid`, etc.
	$value = $order->get_meta( '_' . $key );

	if ( '' !== $value && null !== $value ) {
		return $value;
	}

	// WooCommerce native Order Attribution: `_wc_order_attribution_utm_source`, etc.
	return (string) $order->get_meta( '_wc_order_attribution_' . $key );
}

add_action( 'admin_post_orca_beacon_orders_export', function() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Permission denied.' );
	}

	check_admin_referer( 'orca_beacon_orders_export' );

	$orders = wc_get_orders(
		array(
			'limit'  => -1,
			'status' => array_keys( wc_get_order_statuses() ),
			'return' => 'objects',
		)
	);

	if ( ob_get_length() ) {
		ob_end_clean();
	}

	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=orca-beacon-orders-' . date( 'Y-m-d' ) . '.csv' );
	header( 'Pragma: no-cache' );
	header( 'Expires: 0' );

	$output = fopen( 'php://output', 'w' );

	fputcsv(
		$output,
		array(
			'Order ID',
			'Order Date',
			'Status',
			'Customer Name',
			'Customer Email',
			'Product(s)',
			'Training / OceanWatchers Opt-In',
			'ORCA Communications Opt-In',
			'UTM Source',
			'UTM Medium',
			'UTM Campaign',
			'UTM Term',
			'UTM Content',
			'GCLID',
			'FBCLID',
			'MSCLKID',
		)
	);

	foreach ( $orders as $order ) {
		$products = array();

		foreach ( $order->get_items() as $item ) {
			$products[] = $item->get_name() . ' x ' . $item->get_quantity();
		}

		fputcsv(
			$output,
			array(
				$order->get_id(),
				$order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : '',
				$order->get_status(),
				trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
				$order->get_billing_email(),
				implode( ' | ', $products ),
				orca_get_training_opt_in_value( $order ),
				orca_get_communications_opt_in_value( $order ),
				orca_get_attribution_value( $order, 'utm_source' ),
				orca_get_attribution_value( $order, 'utm_medium' ),
				orca_get_attribution_value( $order, 'utm_campaign' ),
				orca_get_attribution_value( $order, 'utm_term' ),
				orca_get_attribution_value( $order, 'utm_content' ),
				orca_get_attribution_value( $order, 'gclid' ),
				orca_get_attribution_value( $order, 'fbclid' ),
				orca_get_attribution_value( $order, 'msclkid' ),
			)
		);
	}

	fclose( $output );
	exit;
} );

/**
 * Add ORCA opt-in columns to WooCommerce > Orders table.
 */
add_filter( 'manage_woocommerce_page_wc-orders_columns', function( $columns ) {
	$new_columns = array();

	foreach ( $columns as $key => $label ) {
		$new_columns[ $key ] = $label;

		if ( 'billing_address' === $key || 'order_total' === $key ) {
			$new_columns['orca_training_opt_in']       = 'Training Opt-In';
			$new_columns['orca_communications_opt_in'] = 'Comms Opt-In';
		}
	}

	return $new_columns;
}, 20 );

add_action( 'manage_woocommerce_page_wc-orders_custom_column', function( $column, $order ) {
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	if ( 'orca_training_opt_in' === $column ) {
		echo esc_html( orca_get_training_opt_in_value( $order ) );
	}

	if ( 'orca_communications_opt_in' === $column ) {
		echo esc_html( orca_get_communications_opt_in_value( $order ) );
	}
}, 20, 2 );
