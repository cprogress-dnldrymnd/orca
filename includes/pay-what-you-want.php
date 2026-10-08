<?php
/**
 * Pay as you want — custom WooCommerce product type + open-amount checkout.
 *
 * Product type slug: pay_as_you_want
 * Meta: _orca_pwyw_suggested, _orca_pwyw_min, _orca_pwyw_max
 * Legacy: _orca_pwyw = yes (still treated as open-amount until migrated)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load product class once WooCommerce product classes exist.
 */
function orca_load_pay_as_you_want_product_class() {
	if ( ! class_exists( 'WC_Product_Simple' ) ) {
		return;
	}
	require_once get_template_directory() . '/includes/class-wc-product-pay-as-you-want.php';}
add_action( 'woocommerce_init', 'orca_load_pay_as_you_want_product_class', 5 );

/**
 * Register product type in the admin dropdown.
 *
 * @param array $types Product types.
 * @return array
 */
function orca_register_pay_as_you_want_product_type( $types ) {
	$types['pay_as_you_want'] = __( 'Pay as you want', 'orca' );
	return $types;
}
add_filter( 'product_type_selector', 'orca_register_pay_as_you_want_product_type' );

/**
 * Map product type slug to PHP class.
 *
 * @param string $classname Class name.
 * @param string $product_type Product type.
 * @return string
 */
function orca_pay_as_you_want_product_class( $classname, $product_type ) {
	if ( 'pay_as_you_want' === $product_type && class_exists( 'WC_Product_Pay_As_You_Want' ) ) {
		return 'WC_Product_Pay_As_You_Want';
	}
	return $classname;
}
add_filter( 'woocommerce_product_class', 'orca_pay_as_you_want_product_class', 10, 2 );

/**
 * Show LearnDash course/group selector for this product type.
 *
 * @param string $class CSS classes.
 * @return string
 */
function orca_ld_course_selector_show_for_pwyw( $class ) {
	if ( false === strpos( $class, 'show_if_pay_as_you_want' ) ) {
		$class .= ' show_if_pay_as_you_want';
	}
	return $class;
}
add_filter( 'learndash_woocommerce_course_selector_class', 'orca_ld_course_selector_show_for_pwyw' );

/**
 * Enqueue admin JS for product data panels.
 *
 * @param string $hook Current admin page.
 */
function orca_pwyw_admin_scripts( $hook ) {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'product' !== $screen->id ) {
		return;
	}

	wp_enqueue_script(
		'orca-admin-pay-as-you-want',
		get_template_directory_uri() . '/assets/javascripts/admin-pay-as-you-want.js',
		array( 'jquery' ),
		defined( 'orca_version' ) ? orca_version : '1',
		true
	);
}
add_action( 'admin_enqueue_scripts', 'orca_pwyw_admin_scripts' );

/**
 * Admin fields: suggested / min / max.
 */
function orca_pwyw_product_options() {
	echo '<div class="options_group show_if_pay_as_you_want">';

	woocommerce_wp_text_input(
		array(
			'id'                => '_orca_pwyw_suggested',
			'label'             => __( 'Suggested price', 'orca' ) . ' (' . get_woocommerce_currency_symbol() . ')',
			'desc_tip'          => true,
			'description'       => __( 'Shown to the customer as a suggested amount. Also used as the default in the amount field.', 'orca' ),
			'type'              => 'number',
			'custom_attributes' => array(
				'step' => '0.01',
				'min'  => '0',
			),
		)
	);

	woocommerce_wp_text_input(
		array(
			'id'                => '_orca_pwyw_min',
			'label'             => __( 'Minimum price', 'orca' ) . ' (' . get_woocommerce_currency_symbol() . ')',
			'desc_tip'          => true,
			'description'       => __( 'Lowest amount a customer can enter. Use 0 to allow free.', 'orca' ),
			'type'              => 'number',
			'custom_attributes' => array(
				'step' => '0.01',
				'min'  => '0',
			),
		)
	);

	woocommerce_wp_text_input(
		array(
			'id'                => '_orca_pwyw_max',
			'label'             => __( 'Maximum price', 'orca' ) . ' (' . get_woocommerce_currency_symbol() . ')',
			'desc_tip'          => true,
			'description'       => __( 'Optional upper limit. Leave blank for no maximum.', 'orca' ),
			'type'              => 'number',
			'custom_attributes' => array(
				'step' => '0.01',
				'min'  => '0',
			),
		)
	);

	echo '</div>';
}
add_action( 'woocommerce_product_options_pricing', 'orca_pwyw_product_options' );

/**
 * Save PWYW fields for the custom product type.
 *
 * @param int $product_id Product ID.
 */
function orca_save_pay_as_you_want_product_meta( $product_id ) {
	$product = wc_get_product( $product_id );
	if ( ! $product || 'pay_as_you_want' !== $product->get_type() ) {
		// Still allow legacy simple + meta products to save if fields posted.
		$type = isset( $_POST['product-type'] ) ? sanitize_text_field( wp_unslash( $_POST['product-type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( 'pay_as_you_want' !== $type ) {
			return;
		}
	}

	$suggested = isset( $_POST['_orca_pwyw_suggested'] ) ? wc_format_decimal( wp_unslash( $_POST['_orca_pwyw_suggested'] ) ) : ''; // phpcs:ignore
	$min       = isset( $_POST['_orca_pwyw_min'] ) ? wc_format_decimal( wp_unslash( $_POST['_orca_pwyw_min'] ) ) : '0'; // phpcs:ignore
	$max       = isset( $_POST['_orca_pwyw_max'] ) ? wc_format_decimal( wp_unslash( $_POST['_orca_pwyw_max'] ) ) : ''; // phpcs:ignore

	update_post_meta( $product_id, '_orca_pwyw_suggested', $suggested );
	update_post_meta( $product_id, '_orca_pwyw_min', $min );
	if ( '' !== $max && null !== $max ) {
		update_post_meta( $product_id, '_orca_pwyw_max', $max );
	} else {
		delete_post_meta( $product_id, '_orca_pwyw_max' );
	}

	// Keep regular price in sync with suggested for catalogues / reports.
	if ( '' !== $suggested ) {
		update_post_meta( $product_id, '_regular_price', $suggested );
		update_post_meta( $product_id, '_price', $suggested );
	}

	update_post_meta( $product_id, '_sold_individually', 'yes' );
}
add_action( 'woocommerce_process_product_meta_pay_as_you_want', 'orca_save_pay_as_you_want_product_meta' );
add_action( 'woocommerce_process_product_meta', 'orca_save_pay_as_you_want_product_meta', 20 );

/**
 * True when commercial NYP plugin is available.
 */
function orca_nyp_plugin_active() {
	return class_exists( 'WC_Name_Your_Price' ) || function_exists( 'WC_Name_Your_Price_Helpers' );
}

/**
 * Whether this product should collect an open amount.
 *
 * @param int|WC_Product $product Product.
 * @return bool
 */
function orca_product_is_open_amount( $product ) {
	if ( is_numeric( $product ) ) {
		$product = wc_get_product( $product );
	}
	if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
		return false;
	}

	if ( 'pay_as_you_want' === $product->get_type() ) {
		return true;
	}

	// Legacy meta flag.
	if ( 'yes' === $product->get_meta( '_orca_pwyw' ) ) {
		return true;
	}

	if ( function_exists( 'WC_Name_Your_Price_Helpers' ) && method_exists( 'WC_Name_Your_Price_Helpers', 'is_nyp' ) ) {
		return (bool) WC_Name_Your_Price_Helpers::is_nyp( $product );
	}

	return 'yes' === $product->get_meta( '_nyp' );
}

/**
 * Open-amount settings for a product.
 *
 * @param WC_Product $product Product.
 * @return array{suggested:float,min:float,max:float}
 */
function orca_get_open_amount_settings( $product ) {
	return array(
		'suggested' => (float) $product->get_meta( '_orca_pwyw_suggested' ),
		'min'       => (float) $product->get_meta( '_orca_pwyw_min' ),
		'max'       => (float) $product->get_meta( '_orca_pwyw_max' ),
	);
}

/**
 * Add-to-cart form for pay_as_you_want (same template as simple).
 */
add_action( 'woocommerce_pay_as_you_want_add_to_cart', 'woocommerce_simple_add_to_cart', 30 );

add_action( 'woocommerce_before_add_to_cart_button', 'orca_render_open_amount_field', 9 );
function orca_render_open_amount_field() {
	if ( orca_nyp_plugin_active() ) {
		return;
	}

	global $product;
	if ( ! $product || ! orca_product_is_open_amount( $product ) ) {
		return;
	}

	$settings  = orca_get_open_amount_settings( $product );
	$suggested = $settings['suggested'] > 0 ? $settings['suggested'] : '';
	$min       = max( 0, $settings['min'] );
	$max       = $settings['max'] > 0 ? $settings['max'] : '';
	$currency  = get_woocommerce_currency_symbol();
	?>
	<div class="orca-pwyw" style="margin:1rem 0;">
		<label for="orca_pwyw_amount"><strong><?php esc_html_e( 'Name your price', 'orca' ); ?></strong></label>
		<div class="orca-pwyw__row" style="display:flex;align-items:center;gap:.5rem;margin-top:.35rem;">
			<span><?php echo esc_html( $currency ); ?></span>
			<input
				type="number"
				class="input-text nyp-input"
				name="orca_pwyw_amount"
				id="orca_pwyw_amount"
				step="0.01"
				min="<?php echo esc_attr( $min ); ?>"
				<?php echo $max !== '' ? 'max="' . esc_attr( $max ) . '"' : ''; ?>
				value="<?php echo esc_attr( $suggested ); ?>"
				required
			/>
		</div>
		<?php if ( $suggested !== '' ) : ?>
			<p class="orca-pwyw__hint" style="margin:.35rem 0 0;font-size:.9em;">
				<?php
				printf(
					/* translators: %s: suggested price */
					esc_html__( 'Suggested amount: %s', 'orca' ),
					wp_kses_post( wc_price( $suggested ) )
				);
				?>
			</p>
		<?php endif; ?>
	</div>
	<?php
}

add_filter( 'woocommerce_add_to_cart_validation', 'orca_validate_open_amount', 10, 3 );
function orca_validate_open_amount( $passed, $product_id, $quantity ) {
	if ( orca_nyp_plugin_active() ) {
		return $passed;
	}

	$product = wc_get_product( $product_id );
	if ( ! $product || ! orca_product_is_open_amount( $product ) ) {
		return $passed;
	}

	$raw = isset( $_REQUEST['orca_pwyw_amount'] ) ? wp_unslash( $_REQUEST['orca_pwyw_amount'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( '' === $raw && '0' !== (string) $raw ) {
		wc_add_notice( __( 'Please enter an amount for this course.', 'orca' ), 'error' );
		return false;
	}

	$amount   = (float) $raw;
	$settings = orca_get_open_amount_settings( $product );
	if ( $amount < $settings['min'] ) {
		wc_add_notice(
			sprintf(
				/* translators: %s: minimum price */
				__( 'Please enter at least %s.', 'orca' ),
				wp_strip_all_tags( wc_price( $settings['min'] ) )
			),
			'error'
		);
		return false;
	}
	if ( $settings['max'] > 0 && $amount > $settings['max'] ) {
		wc_add_notice(
			sprintf(
				/* translators: %s: maximum price */
				__( 'Please enter no more than %s.', 'orca' ),
				wp_strip_all_tags( wc_price( $settings['max'] ) )
			),
			'error'
		);
		return false;
	}

	return $passed;
}

add_filter( 'woocommerce_add_cart_item_data', 'orca_add_open_amount_cart_item_data', 10, 2 );
function orca_add_open_amount_cart_item_data( $cart_item_data, $product_id ) {
	if ( orca_nyp_plugin_active() ) {
		return $cart_item_data;
	}

	$product = wc_get_product( $product_id );
	if ( ! $product || ! orca_product_is_open_amount( $product ) ) {
		return $cart_item_data;
	}

	if ( isset( $_REQUEST['orca_pwyw_amount'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$cart_item_data['orca_pwyw_amount'] = (float) wp_unslash( $_REQUEST['orca_pwyw_amount'] ); // phpcs:ignore
		$cart_item_data['unique_key']       = md5( microtime() . wp_rand() );
	}

	return $cart_item_data;
}

add_action( 'woocommerce_before_calculate_totals', 'orca_apply_open_amount_to_cart', 20 );
function orca_apply_open_amount_to_cart( $cart ) {
	if ( orca_nyp_plugin_active() || ( is_admin() && ! defined( 'DOING_AJAX' ) ) ) {
		return;
	}
	if ( empty( $cart->cart_contents ) ) {
		return;
	}

	foreach ( $cart->get_cart() as $item ) {
		if ( isset( $item['orca_pwyw_amount'], $item['data'] ) && is_object( $item['data'] ) ) {
			$item['data']->set_price( (float) $item['orca_pwyw_amount'] );
		}
	}
}

add_filter( 'woocommerce_get_price_html', 'orca_open_amount_price_html', 20, 2 );
function orca_open_amount_price_html( $price, $product ) {
	if ( orca_nyp_plugin_active() ) {
		return $price;
	}
	if ( ! $product || ! orca_product_is_open_amount( $product ) ) {
		return $price;
	}

	$settings = orca_get_open_amount_settings( $product );
	if ( $settings['suggested'] > 0 ) {
		return sprintf(
			/* translators: %s: suggested price */
			esc_html__( 'Suggested %s — pay what you want', 'orca' ),
			wp_strip_all_tags( wc_price( $settings['suggested'] ) )
		);
	}
	if ( $settings['min'] > 0 ) {
		return sprintf(
			/* translators: %s: minimum price */
			esc_html__( 'From %s', 'orca' ),
			wp_strip_all_tags( wc_price( $settings['min'] ) )
		);
	}
	return esc_html__( 'Pay what you want', 'orca' );
}
