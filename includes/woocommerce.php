<?php
function filter_woocommerce_cart_redirect_after_error($redirect, $product_id)
{
    $_related_course = get_post_meta($product_id, '_related_course', true);

    if (count($_related_course) == 1) {
        $redirect = esc_url(get_the_permalink($_related_course[0]));
    } else {
        $redirect = esc_url(WC()->cart->get_cart_url());
    }

    return $redirect;
}
add_filter('woocommerce_cart_redirect_after_error', 'filter_woocommerce_cart_redirect_after_error', 10, 2);

/**
 * @snippet       Also Search by SKU @ Shop
 * @how-to        Get CustomizeWoo.com FREE
 * @author        Rodolfo Melogli
 * @compatible    WooCommerce 7
 * @community     https://businessbloomer.com/club/
 */
 
 add_filter( 'posts_search', 'bbloomer_product_search_by_sku', 9999, 2 );
  
 function bbloomer_product_search_by_sku( $search, $wp_query ) {
    global $wpdb;
    if ( is_admin() || ! is_search() || ! isset( $wp_query->query_vars['s'] ) || ( ! is_array( $wp_query->query_vars['post_type'] ) && $wp_query->query_vars['post_type'] !== "product" ) || ( is_array( $wp_query->query_vars['post_type'] ) && ! in_array( "product", $wp_query->query_vars['post_type'] ) ) ) return $search; 
    $product_id = wc_get_product_id_by_sku( $wp_query->query_vars['s'] );
    if ( ! $product_id ) return $search;
    $product = wc_get_product( $product_id );
    if ( $product->is_type( 'variation' ) ) {
       $product_id = $product->get_parent_id();
    }
    $search = str_replace( 'AND (((', "AND (({$wpdb->posts}.ID IN (" . $product_id . ")) OR ((", $search );  
    return $search;   
 }