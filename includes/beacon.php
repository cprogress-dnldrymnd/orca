<?php
function beacon_api_function($api_url, $body, $method = 'PUT')
{

    $encoded_body = json_encode($body);
    $response = wp_remote_get($api_url, array(
        'headers' =>    beacon_headers(),
        'body' => $encoded_body,
        'method' => 'PUT'
    ));

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        error_log("Something went wrong: $error_message");
        return false;
    } else {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            error_log("Json decode error: " . json_last_error_msg());
            return false;
        }
        return $data;
    }
}


function get_product_ids_from_order($order_id)
{
    // Check if WooCommerce is active.
    if (! function_exists('wc_get_order')) {
        return array(); // Return empty array if WooCommerce is not active.
    }

    // Get the order object.
    $order = wc_get_order($order_id);

    // Check if the order object is valid.
    if (! $order) {
        return array(); // Return empty array if order is not found or invalid.
    }

    $product_ids = array(); // Initialize an empty array to store product IDs.

    // Get the order items.  This gets each line item in the order.
    $items = $order->get_items();

    // Iterate through each item in the order.
    foreach ($items as $item) {
        // Get the product ID from the item.
        $product_id = $item->get_product_id();

        // Add the product ID to the array.
        $product_ids[] = $product_id;
    }

    return $product_ids; // Return the array of product IDs.
}

add_action('woocommerce_new_order', 'action_woocommerce_new_order', 10, 1);

function action_woocommerce_new_order($order_id)
{
    $order = wc_get_order($order_id);
    $first_name = $order->get_billing_first_name();
    $last_name  = $order->get_billing_last_name();
    $email  = $order->get_billing_email();
    $phone  = $order->get_billing_phone();
    $address_1  = $order->get_billing_address_1();
    $address_2  = $order->get_billing_address_2();
    $city  = $order->get_billing_city();
    $state  = $order->get_billing_state();
    $postcode  = $order->get_billing_postcode();
    $country  = $order->get_billing_country();
    $product_ids = get_product_ids_from_order($order_id);
    $address = [
        "address_line_one" => $address_1,
        "address_line_two" => $address_2,
        "city" => $city,
        "region" => $state,
        "postal_code" => $postcode,
        "country" => WC()->countries->countries[$country],
    ];

    $body_create_person = [
        "primary_field_key" => "emails",
        "entity" => [
            "emails" => [["email" => $email, "is_primary" => true]],
            "phone_numbers" => [["number" => $phone, "is_primary" => true]],
            "name" => [
                "full" => $first_name . ' ' . $last_name,
                "last" => $last_name,
                "first" => $first_name,
                "middle" => null,
                "prefix" => null,
            ],
            'type' => ['Member'],
            "address" => [$address]
        ],
    ];
    $c_person = beacon_api_function('https://api.beaconcrm.org/v1/account/26878/entity/person/upsert', $body_create_person)['entity']['id'];


    foreach ($product_ids as $product) {
        $c_name = get_the_title($product) . " [Order ID $order_id]";
        $c_course = get__post_meta_by_id($product, 'beacon_id');
        $c_course_type = get__post_meta_by_id($product, 'course_type');
        if ($c_course && $c_course_type) {

            $body_create_training = [
                "primary_field_key" => "c_name",
                "entity" => [
                    "c_name" => $c_name,
                    "c_person" => array(intval($c_person)),
                    "c_course" => array(intval($c_course)),
                    "c_course_type" => [$c_course_type]
                ]

            ];

            beacon_api_function('https://api.beaconcrm.org/v1/account/26878/entity/c_training/upsert', $body_create_training);
        }
    }
}
