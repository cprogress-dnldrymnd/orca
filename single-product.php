<?php
$product = wc_get_product(get_the_ID());

$sku = $product->get_sku();

if ($sku) {
    $url = get_permalink($sku);
    wp_redirect($url);
    exit;
}
