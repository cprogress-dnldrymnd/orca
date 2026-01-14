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

function beacon_api_function($api_url, $body, $order_id = '', $method = 'PUT')
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
        add_beacon_crm_log("Failed Beacon API Response for order ID: $order_id", array(
            'type' => 'Beacon Failed API Response',
            'order_id' => $order_id
        ));
        return false;
    } else {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            error_log("Json decode error: " . json_last_error_msg());
            add_beacon_crm_log("Failed Beacon API Response for order ID: $order_id", array(
                'type' => 'Beacon Failed API Response',
                'order_id' => $order_id
            ));
            return false;
        }
        return $data;
    }
}

add_action('woocommerce_thankyou', 'action_woocommerce_thankyou', 10, 1);


function beacon_create_payment($order_id)
{
    ob_start();
    // $beacon_payment_created = get_post_meta($order_id, 'beacon_payment_created', true);
    $order = wc_get_order($order_id);
    $user_id = $order->get_user_id();
    $c_person = get_user_meta($user_id, 'beacon_user_id', true);

    $items = $order->get_items();
    //$method = $order->get_payment_method();
    $date_paid = $order->get_date_paid();
    // $date_paid = $order->get_date_created();
    if ($date_paid) {
        $payment_date = $date_paid->format('Y-m-d');
    } else {
        $payment_date = false;
    }

    $external_id = $order->get_transaction_id();

    if (empty($external_id)) {
        $external_id = 'MANUAL-' . $order_id;
    }
    $payment_method = 'Card';
    if ($payment_date) {
        foreach ($items as $key => $item) {

            $product_id = $item->get_product_id();
            $c_name = get_the_title($product_id) . " [Order ID: $order_id]";
            $c_course = get__post_meta_by_id($product_id, 'beacon_id');
            $price = $item->get_total();

            $body_create_payment = [
                "primary_field_key" => "external_id",
                "entity" => [
                    'external_id' => $external_id,
                    'amount' => [
                        'value' => $price,
                        'currency' => 'GBP',
                    ],
                    'type' => ['Course fees'],
                    'source' => ['Training Course'],
                    'payment_method' => [$payment_method],
                    'payment_date' => [$payment_date],
                    'customer' => [intval($c_person)],
                    'event' => [intval($c_course)],
                    'notes' => 'Payment made via woocommerce checkout for course: ' . $c_name,
                ],
            ];

            $beacon_api_function = beacon_api_function('https://api.beaconcrm.org/v1/account/26878/entity/payment/upsert', $body_create_payment, $order_id, 'PUT');

            if ($beacon_api_function['entity']) {
                add_beacon_crm_log("Created Beacon Payment for user ID: $user_id", array(
                    'type' => 'Beacon Training',
                    'user_id' => $user_id,
                    'beacon_person_id' => $c_person,
                    'order_id' => $order_id,
                    'product_id' => $product_id,
                ));
            }
        }
    }

    return ob_get_clean();
}
add_action('woocommerce_payment_complete', 'action_woocommerce_payment_complete');


function action_woocommerce_payment_complete($order_id)
{
    beacon_create_payment($order_id);
}

/*
function view_order_details($order_id)
{
    echo '<pre>';
    echo action_woocommerce_thankyou_test($order_id);
    echo '</pre>';
}
add_action('woocommerce_view_order', 'view_order_details');

*/
function action_woocommerce_thankyou($order_id)
{


    $order = wc_get_order($order_id);
    $user_id = $order->get_user_id();
    $beacon_user_id = get_user_meta($user_id, 'beacon_user_id', true);


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

        // Define the array WITHOUT phone_numbers initially
        $body_create_person = [
            "primary_field_key" => "emails",
            "entity" => [
                "emails" => [["email" => $email, "is_primary" => true]],
                // Phone number removed from here
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

        // Only add phone_numbers if $phone is not empty
        if (!empty($phone)) {
            $body_create_person['entity']['phone_numbers'] = [["number" => $phone, "is_primary" => true]];
        }

        $beacon_api_function = beacon_api_function('https://api.beaconcrm.org/v1/account/26878/entity/person/upsert', $body_create_person, $order_id);
        $c_person = $beacon_api_function['entity']['id'];
        update_user_meta($user_id, 'beacon_user_id', $c_person);
        if ($c_person) {
            add_beacon_crm_log("Created Beacon Person for user ID: $user_id", array(
                'type' => 'Beacon Person',
                'user_id' => $user_id,
                'beacon_person_id' => $c_person,
                'order_id' => $order_id
            ));
        } else {
            add_beacon_crm_log("Failed to Create Beacon Person for user ID: $user_id", array(
                'type' => 'Beacon Person',
                'user_id' => $user_id,
                'beacon_person_id' => $c_person,
                'order_id' => $order_id
            ));
        }
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
                "primary_field_key" => "c_previous_db_id",
                "entity" => [
                    "c_person" => [intval($c_person)],
                    "c_course" => [intval($c_course)],
                    "c_course_type" => [$c_course_type],
                    "c_previous_db_id" => $c_name
                ]
            ];
            $beacon_api_function = beacon_api_function('https://api.beaconcrm.org/v1/account/26878/entity/c_training/upsert', $body_create_training, $order_id);

            if ($beacon_api_function['entity']) {
                add_beacon_crm_log("Created Beacon Training for user ID: $user_id", array(
                    'type' => 'Beacon Training',
                    'user_id' => $user_id,
                    'beacon_person_id' => $c_person,
                    'order_id' => $order_id,
                    'product_id' => $product_id,
                ));
            }
        }
    }
    beacon_create_payment($order_id);
}


/**
 * Inserts a log entry into the 'beaconcrmlogs' post type and handles errors internally.
 *
 * @param string $title       The title of the log entry.
 * @param array  $meta_fields An associative array of meta key => value pairs.
 * @param string $content     Optional. The main content/body of the log.
 * @return int|bool           Returns the Post ID on success, or false on failure.
 */
function add_beacon_crm_log($title, $meta_fields = array())
{

    $post_data = array(
        'post_title'    => sanitize_text_field($title),
        'post_status'   => 'publish',
        'post_type'     => 'beaconcrmlogs',
        'meta_input'    => $meta_fields,
    );

    // We pass 'true' here to ensure we get a WP_Error object if it fails
    $result = wp_insert_post($post_data, true);

    if (is_wp_error($result)) {
        // Write the specific error message to the PHP error log
        error_log('Beacon CRM Log Error: ' . $result->get_error_message());

        // Return false to indicate failure to the caller
        return false;
    }

    return $result;
}


function action__wp_head()
{
    if (current_user_can('administrator')) {
        $order = wc_get_order(9579);
        $items = $order->get_items();
        foreach ($items as $key => $item) {
            $product_id = $item->get_product_id();
            $c_course = get__post_meta_by_id($product_id, 'beacon_id');

            $beacon_courses_arr = [];
            $beacon_courses = carbon_get_post_meta($product_id, 'beacon_courses');
            foreach ($beacon_courses as $beacon_course) {
                $beacon_courses_arr[] = $beacon_course['id'];
            }
            echo '<pre>';
            var_dump($beacon_courses_arr);
            echo '</pre>';
            /*
            $price = $item->get_total();

            $body_create_payment = [
                "primary_field_key" => "external_id",
                "entity" => [
                    'external_id' => $external_id,
                    'amount' => [
                        'value' => $price,
                        'currency' => 'GBP',
                    ],
                    'type' => ['Course fees'],
                    'source' => ['Training Course'],
                    'payment_method' => [$payment_method],
                    'payment_date' => [$payment_date],
                    'customer' => [intval($c_person)],
                    'event' => [intval($c_course)],
                    'notes' => 'Payment made via woocommerce checkout for course: ' . $c_name,
                ],
            ];

          
            $beacon_api_function = beacon_api_function('https://api.beaconcrm.org/v1/account/26878/entity/payment/upsert', $body_create_payment, $order_id, 'PUT');

            if ($beacon_api_function['entity']) {
                add_beacon_crm_log("Created Beacon Payment for user ID: $user_id", array(
                    'type' => 'Beacon Training',
                    'user_id' => $user_id,
                    'beacon_person_id' => $c_person,
                    'order_id' => $order_id,
                    'product_id' => $product_id,
                ));
            }*/
        }
    }
}

add_action('wp_head', 'action__wp_head');
