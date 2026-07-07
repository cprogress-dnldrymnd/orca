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

/**
 * The UTM / click-id keys tracked by the theme (see functions.php).
 * Keyed by the theme custom meta suffix => human label.
 */
function orca_get_attribution_keys() {
	return array(
		'utm_source'   => 'UTM Source',
		'utm_medium'   => 'UTM Medium',
		'utm_campaign' => 'UTM Campaign',
		'utm_term'     => 'UTM Term',
		'utm_content'  => 'UTM Content',
		'gclid'        => 'GCLID',
		'fbclid'       => 'FBCLID',
		'msclkid'      => 'MSCLKID',
	);
}

/**
 * Register the "Order Attribution" admin page under the WooCommerce menu.
 */
add_action( 'admin_menu', function() {
	add_submenu_page(
		'woocommerce',
		'Order Attribution (UTM)',
		'Order Attribution',
		'manage_woocommerce',
		'orca-order-attribution',
		'orca_render_order_attribution_page'
	);
}, 20 );

/**
 * Render a paginated table of orders with their theme custom `_utm_*` attribution.
 */
function orca_render_order_attribution_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Permission denied.' );
	}

	$attribution_keys = orca_get_attribution_keys();

	$per_page = 25;
	$paged    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
	$utm_only = isset( $_GET['utm_only'] ) && '1' === $_GET['utm_only'];

	$status = array_keys( wc_get_order_statuses() );

	// Match orders that have any non-empty theme custom `_utm_*` / click-id meta.
	$meta_query = array( 'relation' => 'OR' );

	foreach ( array_keys( $attribution_keys ) as $key ) {
		$meta_query[] = array(
			'key'     => '_' . $key,
			'value'   => '',
			'compare' => '!=',
		);
	}

	// IDs of attributed orders, newest first.
	$with_ids = wc_get_orders(
		array(
			'limit'      => -1,
			'status'     => $status,
			'orderby'    => 'date',
			'order'      => 'DESC',
			'return'     => 'ids',
			'meta_query' => $meta_query,
		)
	);

	if ( $utm_only ) {
		$ordered_ids = $with_ids;
	} else {
		// All orders newest first, then list attributed ones first while keeping
		// each group in date order (array_diff preserves the first array's order).
		$all_ids     = wc_get_orders(
			array(
				'limit'   => -1,
				'status'  => $status,
				'orderby' => 'date',
				'order'   => 'DESC',
				'return'  => 'ids',
			)
		);
		$without_ids = array_values( array_diff( $all_ids, $with_ids ) );
		$ordered_ids = array_merge( $with_ids, $without_ids );
	}

	$total     = count( $ordered_ids );
	$max_pages = (int) ceil( $total / $per_page );

	$page_ids = array_slice( $ordered_ids, ( $paged - 1 ) * $per_page, $per_page );
	$orders   = array();

	foreach ( $page_ids as $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $order ) {
			$orders[] = $order;
		}
	}

	$export_url = wp_nonce_url(
		admin_url( 'admin-post.php?action=orca_beacon_orders_export' ),
		'orca_beacon_orders_export'
	);

	$base_url = admin_url( 'admin.php?page=orca-order-attribution' );

	echo '<div class="wrap">';
	echo '<h1 class="wp-heading-inline">Order Attribution (UTM)</h1>';
	echo ' <a href="' . esc_url( $export_url ) . '" class="page-title-action">Download CSV</a>';
	echo '<hr class="wp-header-end">';

	// Filter links.
	echo '<ul class="subsubsub">';
	printf(
		'<li><a href="%s"%s>All</a> | </li>',
		esc_url( $base_url ),
		$utm_only ? '' : ' class="current"'
	);
	printf(
		'<li><a href="%s"%s>With UTM data</a></li>',
		esc_url( add_query_arg( 'utm_only', '1', $base_url ) ),
		$utm_only ? ' class="current"' : ''
	);
	echo '</ul>';

	echo '<p style="clear:both;">Showing ' . esc_html( number_format_i18n( $total ) ) . ' order(s).</p>';

	echo '<div style="overflow-x:auto;">';
	echo '<table class="wp-list-table widefat fixed striped" style="min-width:1100px;">';

	// Header.
	echo '<thead><tr>';
	echo '<th style="width:70px;">Order</th>';
	echo '<th style="width:150px;">Date</th>';
	echo '<th>Customer</th>';
	foreach ( $attribution_keys as $label ) {
		echo '<th>' . esc_html( $label ) . '</th>';
	}
	echo '</tr></thead>';

	echo '<tbody>';

	if ( empty( $orders ) ) {
		echo '<tr><td colspan="' . esc_attr( 3 + count( $attribution_keys ) ) . '">No orders found.</td></tr>';
	}

	foreach ( $orders as $order ) {
		$customer = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );

		echo '<tr>';
		printf(
			'<td><a href="%s">#%s</a></td>',
			esc_url( $order->get_edit_order_url() ),
			esc_html( $order->get_id() )
		);
		echo '<td>' . esc_html( $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i' ) : '' ) . '</td>';
		echo '<td>' . esc_html( '' !== $customer ? $customer : $order->get_billing_email() ) . '</td>';

		foreach ( array_keys( $attribution_keys ) as $key ) {
			$value = $order->get_meta( '_' . $key );
			echo '<td>' . ( '' !== $value ? esc_html( $value ) : '&mdash;' ) . '</td>';
		}

		echo '</tr>';
	}

	echo '</tbody></table>';
	echo '</div>';

	// Pagination.
	if ( $max_pages > 1 ) {
		$page_links = paginate_links(
			array(
				'base'      => add_query_arg( 'paged', '%#%', $utm_only ? add_query_arg( 'utm_only', '1', $base_url ) : $base_url ),
				'format'    => '',
				'prev_text' => '&laquo;',
				'next_text' => '&raquo;',
				'total'     => $max_pages,
				'current'   => $paged,
			)
		);

		if ( $page_links ) {
			echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post( $page_links ) . '</div></div>';
		}
	}

	echo '</div>';
}
