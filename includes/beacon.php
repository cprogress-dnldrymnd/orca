<?php
/**
 * Beacon CRM Integration for WooCommerce
 * * Handles synchronisation of Orders (Payments) and Course Data (Training)
 * to Beacon CRM upon successful checkout.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Beacon_CRM_Integration
{
    /**
     * API Configuration Constants
     */
    const API_KEY = '865611a7dca14f72fea75c1c2dfcd51d44b8364f3e54e575f708d9c562d2397de1507738d5f3fe56';
    const ACCOUNT_ID = '26878';
    const API_BASE = 'https://api.beaconcrm.org/v1/account/';

    /**
     * Constructor: Hooks into WordPress and WooCommerce actions.
     */
    public function __construct()
    {
        // Order Hooks
        add_action('woocommerce_payment_complete', [$this, 'handle_payment_complete']);
        add_action('woocommerce_thankyou', [$this, 'handle_training_logic'], 10, 1);

        // User Admin Columns
        add_filter('manage_users_columns', [$this, 'add_beacon_id_user_column']);
        add_filter('manage_users_custom_column', [$this, 'fill_beacon_id_user_column'], 10, 3);
        add_filter('manage_sortable_columns', [$this, 'make_beacon_id_column_sortable']);
    }

    /**
     * Returns the standard headers for Beacon API requests.
     * * @return array
     */
    private function get_headers()
    {
        return [
            'Authorization'      => 'Bearer ' . self::API_KEY,
            'Beacon-Application' => 'developer_api',
            'Content-Type'       => 'application/json',
        ];
    }

    /**
     * Sends a request to the Beacon CRM API.
     * * @param string $endpoint The API endpoint (full URL).
     * @param array  $body     The payload body.
     * @param int    $order_id context for logging.
     * @param string $method   HTTP method (PUT, POST, GET).
     * @return array|false     Decoded JSON response or false on failure.
     */
    private function send_request($endpoint, $body, $order_id = 0, $method = 'PUT')
    {
        $args = [
            'body'    => json_encode($body),
            'headers' => $this->get_headers(),
            'method'  => $method,
            'timeout' => 45, // Increased timeout for external API calls
        ];

        // Using wp_remote_request is cleaner for PUT/POST than wp_remote_get
        $response = wp_remote_request($endpoint, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log("Beacon API Error (Order $order_id): $error_message");
            return false;
        }

        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            error_log("Beacon API JSON Decode Error: " . json_last_error_msg());
            return false;
        }

        return $data;
    }

    /**
     * CORE LOGIC: Ensures a Person exists in Beacon CRM.
     * * 1. Checks User Meta for existing Beacon ID.
     * 2. If missing, creates/upserts the Person in Beacon using Order Billing Data.
     * 3. Saves the returned Beacon ID to User Meta.
     * * @param WC_Order $order The WooCommerce order object.
     * @return int|false The Beacon Person ID or false on failure.
     */
    private function get_or_create_person($order)
    {
        $user_id = $order->get_user_id();

        // 1. Check local cache (User Meta)
        $existing_id = get_user_meta($user_id, 'beacon_user_id', true);
        if (!empty($existing_id)) {
            return $existing_id;
        }

        // 2. Prepare data for creation
        $first_name = $order->get_billing_first_name();
        $last_name  = $order->get_billing_last_name();
        $email      = $order->get_billing_email();
        $phone      = $order->get_billing_phone();
        
        // Address data
        $country_code = $order->get_billing_country();
        $country_name = isset(WC()->countries->countries[$country_code]) ? WC()->countries->countries[$country_code] : $country_code;

        $address = [
            "address_line_one" => $order->get_billing_address_1(),
            "address_line_two" => $order->get_billing_address_2(),
            "city"             => $order->get_billing_city(),
            "region"           => $order->get_billing_state(),
            "postal_code"      => $order->get_billing_postcode(),
            "country"          => $country_name,
        ];

        $payload = [
            "primary_field_key" => "emails",
            "entity" => [
                "emails" => [["email" => $email, "is_primary" => true]],
                "name" => [
                    "full"   => $first_name . ' ' . $last_name,
                    "last"   => $last_name,
                    "first"  => $first_name,
                    "middle" => null,
                    "prefix" => null,
                ],
                'type'    => ['Supporter'],
                "address" => [$address],
                "notes"   => 'Updated via woocommerce checkout'
            ],
        ];

        // Conditionally add phone if it exists
        if (!empty($phone)) {
            $payload['entity']['phone_numbers'] = [["number" => $phone, "is_primary" => true]];
        }

        $endpoint = self::API_BASE . self::ACCOUNT_ID . '/entity/person/upsert';
        $response = $this->send_request($endpoint, $payload, $order->get_id());

        // 3. Process Response
        if ($response && isset($response['entity']['id'])) {
            $beacon_id = $response['entity']['id'];
            update_user_meta($user_id, 'beacon_user_id', $beacon_id);

            $this->log_to_db("[Person Created] Order " . $order->get_id(), [
                'type'    => 'person',
                'api_url' => $endpoint,
                'args'    => $payload,
                'return'  => $response,
            ]);

            return $beacon_id;
        }

        return false;
    }

    /**
     * Hook: woocommerce_payment_complete
     * Handles the creation of the Payment entity in Beacon.
     * * @param int $order_id
     */
    public function handle_payment_complete($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) return;

        // Ensure user exists first to avoid "Validation Error"
        $beacon_person_id = $this->get_or_create_person($order);

        if (!$beacon_person_id) {
            error_log("Beacon Error: Could not resolve Person ID for Payment. Order #$order_id");
            return;
        }

        $items = $order->get_items();
        $date_paid_obj = $order->get_date_paid();
        $payment_date = $date_paid_obj ? $date_paid_obj->format('Y-m-d') : date('Y-m-d'); // Fallback to today if null

        $external_id = $order->get_transaction_id();
        if (empty($external_id)) {
            $external_id = 'MANUAL-' . $order_id;
        }

        $endpoint = self::API_BASE . self::ACCOUNT_ID . '/entity/payment/upsert';

        foreach ($items as $item) {
            $product_id = $item->get_product_id();
            $c_name = get_the_title($product_id) . " [Order ID: $order_id]";

            // Retrieve associated Beacon Course IDs
            $beacon_courses_arr = [];
            $beacon_courses = carbon_get_post_meta($product_id, 'beacon_courses');
            
            if (is_array($beacon_courses)) {
                foreach ($beacon_courses as $beacon_course) {
                    $c_course = get__post_meta_by_id($beacon_course['id'], 'beacon_id'); // Keeping user's custom function
                    $beacon_courses_arr[] = intval($c_course);
                }
            }

            $payload = [
                "primary_field_key" => "external_id",
                "entity" => [
                    'external_id'    => $external_id,
                    'amount'         => [
                        'value'    => $item->get_total(),
                        'currency' => 'GBP',
                    ],
                    'type'           => ['Course fees'],
                    'source'         => ['Training Course'],
                    'payment_method' => ['Card'],
                    'payment_date'   => [$payment_date],
                    'customer'       => [intval($beacon_person_id)],
                    'event'          => $beacon_courses_arr,
                    'notes'          => 'Payment made via woocommerce checkout for course: ' . $c_name,
                ],
            ];

            $response = $this->send_request($endpoint, $payload, $order_id, 'PUT');

            $this->log_to_db("[Payment] Order " . $order_id, [
                'type'    => 'payment',
                'api_url' => $endpoint,
                'args'    => $payload,
                'return'  => $response,
            ]);
        }
    }

    /**
     * Hook: woocommerce_thankyou
     * Handles the creation of Training records (Course registration).
     * * @param int $order_id
     */
    public function handle_training_logic($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) return;

        // Ensure user exists first
        $beacon_person_id = $this->get_or_create_person($order);

        if (!$beacon_person_id) {
            error_log("Beacon Error: Could not resolve Person ID for Training. Order #$order_id");
            return;
        }

        $items = $order->get_items();
        $endpoint = self::API_BASE . self::ACCOUNT_ID . '/entity/c_training/upsert';

        foreach ($items as $item) {
            $product_id = $item->get_product_id();
            $beacon_courses = carbon_get_post_meta($product_id, 'beacon_courses');

            if (is_array($beacon_courses)) {
                foreach ($beacon_courses as $beacon_course) {
                    // Extract metadata using user's specific helper function
                    $c_course_id = isset($beacon_course['id']) ? $beacon_course['id'] : 0;
                    $c_course = get__post_meta_by_id($c_course_id, 'beacon_id');
                    $c_course_type = get__post_meta_by_id($c_course_id, 'course_type');
                    $c_name = get_the_title($c_course_id) . " [Order ID: $order_id]";

                    if ($c_course && $c_course_type) {
                        $payload = [
                            "primary_field_key" => "c_previous_db_id",
                            "entity" => [
                                "c_person"         => [intval($beacon_person_id)],
                                "c_course"         => [intval($c_course)],
                                "c_course_type"    => [$c_course_type],
                                "c_previous_db_id" => $c_name
                            ]
                        ];

                        $response = $this->send_request($endpoint, $payload, $order_id);

                        $this->log_to_db("[Training] Order " . $order_id, [
                            'type'    => 'training',
                            'api_url' => $endpoint,
                            'args'    => $payload,
                            'return'  => $response,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Helper: Inserts a log entry into the 'beaconcrmlogs' custom post type.
     * * @param string $title
     * @param array  $meta_fields
     * @return int|false Post ID or false
     */
    private function log_to_db($title, $meta_fields = [])
    {
        $post_data = [
            'post_title'  => sanitize_text_field($title),
            'post_status' => 'publish',
            'post_type'   => 'beaconcrmlogs',
            'meta_input'  => $meta_fields,
        ];

        $result = wp_insert_post($post_data, true);

        if (is_wp_error($result)) {
            error_log('Beacon CRM Log Error: ' . $result->get_error_message());
            return false;
        }
        return $result;
    }

    /**
     * Admin Column: Add 'Beacon ID' to Users list.
     */
    public function add_beacon_id_user_column($columns)
    {
        $columns['beacon_id'] = 'Beacon ID';
        return $columns;
    }

    /**
     * Admin Column: Populate 'Beacon ID'.
     */
    public function fill_beacon_id_user_column($output, $column_name, $user_id)
    {
        if ($column_name === 'beacon_id') {
            $beacon_id = get_user_meta($user_id, 'beacon_user_id', true);
            return !empty($beacon_id) ? esc_html($beacon_id) : '—';
        }
        return $output;
    }

    /**
     * Admin Column: Make 'Beacon ID' sortable.
     */
    public function make_beacon_id_column_sortable($columns)
    {
        $columns['beacon_id'] = 'beacon_user_id';
        return $columns;
    }
}

// Initialize the integration
new Beacon_CRM_Integration();