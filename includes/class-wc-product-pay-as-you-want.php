<?php
/**
 * WooCommerce product type: Pay as you want.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Product_Simple' ) ) {
	return;
}

/**
 * Simple-like product that collects a customer-entered amount.
 */
class WC_Product_Pay_As_You_Want extends WC_Product_Simple {

	/**
	 * Product type slug.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'pay_as_you_want';
	}

	/**
	 * Always sold individually (one course enrolment per purchase).
	 *
	 * @param string $context View context.
	 * @return bool
	 */
	public function get_sold_individually( $context = 'view' ) {
		return true;
	}

	/**
	 * Purchasable even when regular price is empty (amount is collected at add-to-cart).
	 *
	 * @return bool
	 */
	public function is_purchasable() {
		return apply_filters( 'woocommerce_is_purchasable', $this->exists() && ( 'publish' === $this->get_status() || current_user_can( 'edit_post', $this->get_id() ) ), $this );
	}
}
