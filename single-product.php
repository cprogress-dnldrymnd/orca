<?php
global $product;

$sku = $product->get_sku();

if ($sku) {
    $url = get_permalink($sku);
    wp_redirect($url);
    exit;
}
