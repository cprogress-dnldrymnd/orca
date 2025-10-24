<?php
define('BEACON_API_KEY', '865611a7dca14f72fea75c1c2dfcd51d44b8364f3e54e575f708d9c562d2397de1507738d5f3fe56');

function beacon_headers()
{
    return array(
        'Authorization' => 'Bearer ' . BEACON_API_KEY,
        'Beacon-Application' => 'developer_api',
        'Content-Type' => 'application/json',
    );
}

function beacon_api_function($api_url, $body, $method = 'PUT')
{

    $encoded_body = json_encode($body);

    $response = wp_remote_get($api_url, array(
        'body' => $encoded_body,
        'headers' =>    beacon_headers(),
        'method' => $method
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

add_action('woocommerce_thankyou', 'action_woocommerce_thankyou', 10, 1);

function action_woocommerce_thankyou($order_id)
{

    $order = wc_get_order($order_id);
    $user_id = $order->get_user_id();
    $beacon_user_id = get_user_meta($user_id, 'beacon_user_id', true);

    echo $beacon_user_id;

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
    $items = $order->get_items();

    if (!$beacon_user_id) {
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
                'type' => ['Supporter'],
                "address" => [$address],
                "notes" => 'Updated via woocommerce checkout'
            ],
        ];
        $c_person = beacon_api_function('https://api.beaconcrm.org/v1/account/26878/entity/person/upsert', $body_create_person)['entity']['id'];
        update_user_meta($user_id, 'beacon_user_id', $c_person);
    } else {
        $c_person = $beacon_user_id;
    }

    foreach ($items as $item) {
        $product_id = $item->get_product_id();
        $c_name = get_the_title($product_id) . " [Order ID: $order_id]";
        $c_course = get__post_meta_by_id($product_id, 'beacon_id');
        $c_course_type = get__post_meta_by_id($product_id, 'course_type');
        if ($c_course && $c_course_type) {
            $body_create_training = [
                "primary_field_key" => "c_name",
                "entity" => [
                    "c_name" => $c_name,
                    "c_person" => [intval($c_person)],
                    "c_course" => [intval($c_course)],
                    "c_course_type" => [$c_course_type]
                ]

            ];
           echo beacon_api_function('https://api.beaconcrm.org/v1/account/26878/entity/c_training/upsert', $body_create_training);
            echo '<pre>';
            var_dump($body_create_training);
            echo '</pre>';
        }
    }

    beacon_create_payment($order_id);
}

function beacon_create_payment($order_id)
{
    $beacon_payment_created = get_post_meta($order_id, 'beacon_payment_created', true);
    $order = wc_get_order($order_id);
    $user_id = $order->get_user_id();
    $c_person = get_user_meta($user_id, 'beacon_user_id', true);

    $items = $order->get_items();
    $method = $order->get_payment_method();
    $date_paid = $order->get_date_paid();
    //$date_paid = $order->get_date_created();
    $external_id = $order->get_transaction_id();
    if ($date_paid) {
        $payment_date = $date_paid->format('Y-m-d');
    } else {
        $payment_date = false;
    }
    $type = 'Course fees';
    if ($method == 'stripe_cc') {
        $payment_method = 'Card';
    } else {
        $payment_method = 'Cash';
    }


    if (!$beacon_payment_created) {
        if ($payment_date) {
            foreach ($items as $item) {
                $product_id = $item->get_product_id();
                $c_name = get_the_title($product_id) . " [Order ID: $order_id]";
                $price = $item->get_total();
                $body_create_payment = [
                    'amount' => [
                        'value' => $price,
                        'currency' => 'GBP',
                    ],
                    'type' => [$type],
                    'source' => 'Training Course',
                    'payment_method' => [$payment_method],
                    'payment_date' => [$payment_date],
                    'customer' => [intval($c_person)],
                    'notes' => 'Payment made via woocommerce checkout for course: ' . $c_name,
                    'external_id' => $external_id,
                ];
            }
            beacon_api_function('https://api.beaconcrm.org/v1/account/26878/entity/payment', $body_create_payment, 'POST');
            update_post_meta($order_id, 'beacon_payment_created', true);
        }
    }
}

add_action('woocommerce_pre_payment_complete', 'action_woocommerce_pre_payment_complete');


function action_woocommerce_pre_payment_complete($order_id)
{
    beacon_create_payment($order_id);
}
