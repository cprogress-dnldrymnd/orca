<?php
function filter_woocommerce_cart_redirect_after_error($redirect, $product_id)
{
    $redirect = get_permalink(get_the_ID());
    return $redirect;
}

add_filter('woocommerce_cart_redirect_after_error', 'filter_woocommerce_cart_redirect_after_error', 10, 2);
