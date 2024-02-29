<?php
function filter_woocommerce_cart_redirect_after_error($redirect, $product_id)
{
    $_related_course = get_post_meta($product_id, '_related_course');

    if (count($_related_course) == 1) {
        $redirect = get_the_permalink($_related_course[0]);
    } else {
        $redirect = esc_url(WC()->cart->get_cart_url());
    }
    $redirect = esc_url(WC()->cart->get_cart_url());

    return $redirect;
}

add_filter('woocommerce_cart_redirect_after_error', 'filter_woocommerce_cart_redirect_after_error', 10, 2);
