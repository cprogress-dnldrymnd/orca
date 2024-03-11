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

function searchfilter($query)
{
    $meta_query = array(
        array(
            'key' => '_sku',
            'value' => 'XSDS323',
            'compare' => 'LIKE',
        ),
    );
    if ($query->is_search && !is_admin()) {
        $query->set('meta_query', $meta_query);
    }

    return $query;
}

add_filter('pre_get_posts', 'searchfilter', 9999999);