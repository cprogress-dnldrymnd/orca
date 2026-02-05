<?php
/**
 * Beacon CRM Integration for WooCommerce
 * Handles synchronisation of Orders and Course Data to Beacon CRM.
 * Settings managed via Settings > Beacon CRM.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Beacon_CRM_Integration
{
    /**
     * Option Names
     */
    const OPT_API_KEY    = 'beacon_crm_api_key';
    const OPT_ACCOUNT_ID = 'beacon_crm_account_id';
    const OPT_API_BASE   = 'beacon_crm_api_base';

    /**
     * Constructor: Hooks into WordPress and WooCommerce actions.
     */
    public function __construct()
    {
        // Admin Settings Menu
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);

        // Order Hooks
        add_action('woocommerce_payment_complete', [$this, 'handle_payment_complete']);
        add_action('woocommerce_thankyou', [$this, 'handle_training_logic'], 10, 1);

        // User Admin Columns
        add_filter('manage_users_columns', [$this, 'add_beacon_id_user_column']);
        add_filter('manage_users_custom_column', [$this, 'fill_beacon_id_user_column'], 10, 3);
        add_filter('manage_sortable_columns', [$this, 'make_beacon_id_column_sortable']);
    }

    /* -------------------------------------------------------------------------- */
    /* ADMIN SETTINGS PAGE                                                        */
    /* -------------------------------------------------------------------------- */

    /**
     * Registers the settings page under "Settings".
     */
    public function add_admin_menu()
    {
        add_options_page(
            'Beacon CRM Settings',
            'Beacon CRM',
            'manage_options',
            'beacon-crm-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Registers settings, sections, and fields.
     */
    public function register_settings()
    {
        // Register API Key
        register_setting('beacon_crm_options', self::OPT_API_KEY, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]);

        // Register Account ID
        register_setting('beacon_crm_options', self::OPT_ACCOUNT_ID, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]);

        // Register API Base URL
        register_setting('beacon_crm_options', self::OPT_API_BASE, [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => 'https://api.beaconcrm.org/v1/account/'
        ]);

        // Add Section
        add_settings_section(
            'beacon_crm_main_section',
            'API Configuration',
            null,
            'beacon-crm-settings'
        );

        // Add Fields
        add_settings_field(
            self::OPT_API_KEY,
            'API Key',
            [$this, 'render_field_api_key'],
            'beacon-crm-settings',
            'beacon_crm_main_section'
        );

        add_settings_field(
            self::OPT_ACCOUNT_ID,
            'Account ID',
            [$this, 'render_field_account_id'],
            'beacon-crm-settings',
            'beacon_crm_main_section'
        );

        add_settings_field(
            self::OPT_API_BASE,
            'API Base URL',
            [$this, 'render_field_api_base'],
            'beacon-crm-settings',
            'beacon_crm_main_section'
        );
    }

    /**
     * Render the Settings Page HTML.
     */
    public function render_settings_page()
    {
        ?>
        <div class="wrap">
            <h1>Beacon CRM Integration Settings</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('beacon_crm_options');
                do_settings_sections('beacon-crm-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render API Key Field
     */
    public function render_field_api_key()
    {
        $value = get_option(self::OPT_API_KEY);
        echo '<input type="password" name="' . esc_attr(self::OPT_API_KEY) . '" value="' . esc_attr($value) . '" class="regular-text">';
        echo '<p class="description">Your private API key (Developer API).</p>';
    }

    /**
     * Render Account ID Field
     */
    public function render_field_account_id()
    {
        $value = get_option(self::OPT_ACCOUNT_ID);
        echo '<input type="text" name="' . esc_attr(self::OPT_ACCOUNT_ID) . '" value="' . esc_attr($value) . '" class="regular-text">';
    }

    /**
     * Render API Base URL Field
     */
    public function render_field_api_base()
    {
        $value = get_option(self::OPT_API_BASE, 'https://api.beaconcrm.org/v1/account/');
        echo '<input type="url" name="' . esc_attr(self::OPT_API_BASE) . '" value="' . esc_attr($value) . '" class="regular-text">';
        echo '<p class="description">Default: <code>https://api.beaconcrm.org/v1/account/</code></p>';
    }

    /* -------------------------------------------------------------------------- */
    /* API UTILITIES                                                              */
    /* -------------------------------------------------------------------------- */

    /**
     * Helper to retrieve API credentials safely.
     * @return array|false Credentials array or false if missing.
     */
    private function get_credentials()
    {
        $api_key = get_option(self::OPT_API_KEY);
        $account_id = get_option(self::OPT_ACCOUNT_ID);
        $api_base = get_option(self::OPT_API_BASE, 'https://api.beaconcrm.org/v1/account/');

        if (empty($api_key) || empty($account_id)) {
            return false;
        }

        // Ensure API Base has trailing slash
        $api_base = trailingslashit($api_base);

        return [
            'api_key'    => $api_key,
            'account_id' => $account_id,
            'base_url'   => $api_base . $account_id . '/' // Append Account ID to base
        ];
    }

    /**
     * Returns the standard headers for Beacon API requests.
     * @param string $api_key
     * @return array
     */
    private function get_headers($api_key)
    {
        return [
            'Authorization'      => 'Bearer ' . $api_key,
            'Beacon-Application' => 'developer_api',
            'Content-Type'       => 'application/json',
        ];
    }

    /**
     * Sends a request to the Beacon CRM API.
     * @param string $resource The resource path (e.g. 'entity/person/upsert').
     * @param array  $body     The payload body.
     * @param int    $order_id context for logging.
     * @param string $method   HTTP method (PUT, POST, GET).
     * @return array|false     Decoded JSON response or false on failure.
     */
    private function send_request($resource, $body, $order_id = 0, $method = 'PUT')
    {
        $creds = $this->get_credentials();

        if (!$creds) {
            error_log("Beacon CRM Error: Missing API Key or Account ID in settings.");
            return false;
        }

        // Construct full URL dynamically
        $full_url = $creds['base_url'] . $resource;

        $args = [
            'body'    => json_encode($body),
            'headers' => $this->get_headers($creds['api_key']),
            'method'  => $method,
            'timeout' => 45,
        ];

        $response = wp_remote_request($full_url, $args);

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

    /* -------------------------------------------------------------------------- */
    /* CORE BUSINESS LOGIC                                                        */
    /* -------------------------------------------------------------------------- */

    /**
     * CORE LOGIC: Ensures a Person exists in Beacon CRM.
     * @param WC_Order $order The WooCommerce order object.
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

        if (!empty($phone)) {
            $payload['entity']['phone_numbers'] = [["number" => $phone, "is_primary" => true]];
        }

        $resource = 'entity/person/upsert';
        $response = $this->send_request($resource, $payload, $order->get_id());

        // 3. Process Response
        if ($response && isset($response['entity']['id'])) {
            $beacon_id = $response['entity']['id'];
            update_user_meta($user_id, 'beacon_user_id', $beacon_id);

            $this->log_to_db("[Person Created] Order " . $order->get_id(), [
                'type'    => 'person',
                'api_url' => $resource,
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
     * @param int $order_id
     */
    public function handle_payment_complete($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) return;

        // Ensure user exists first
        $beacon_person_id = $this->get_or_create_person($order);

        if (!$beacon_person_id) {
            error_log("Beacon Error: Could not resolve Person ID for Payment. Order #$order_id");
            return;
        }

        $items = $order->get_items();
        $date_paid_obj = $order->get_date_paid();
        $payment_date = $date_paid_obj ? $date_paid_obj->format('Y-m-d') : date('Y-m-d'); 

        $external_id = $order->get_transaction_id();
        if (empty($external_id)) {
            $external_id = 'MANUAL-' . $order_id;
        }

        $resource = 'entity/payment/upsert';

        foreach ($items as $item) {
            $product_id = $item->get_product_id();
            $c_name = get_the_title($product_id) . " [Order ID: $order_id]";

            $beacon_courses_arr = [];
            $beacon_courses = carbon_get_post_meta($product_id, 'beacon_courses');
            
            if (is_array($beacon_courses)) {
                foreach ($beacon_courses as $beacon_course) {
                    $c_course = get__post_meta_by_id($beacon_course['id'], 'beacon_id'); 
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

            $response = $this->send_request($resource, $payload, $order_id, 'PUT');

            $this->log_to_db("[Payment] Order " . $order_id, [
                'type'    => 'payment',
                'api_url' => $resource,
                'args'    => $payload,
                'return'  => $response,
            ]);
        }
    }

    /**
     * Hook: woocommerce_thankyou
     * Handles the creation of Training records.
     * @param int $order_id
     */
    public function handle_training_logic($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) return;

        $beacon_person_id = $this->get_or_create_person($order);

        if (!$beacon_person_id) {
            error_log("Beacon Error: Could not resolve Person ID for Training. Order #$order_id");
            return;
        }

        $items = $order->get_items();
        $resource = 'entity/c_training/upsert';

        foreach ($items as $item) {
            $product_id = $item->get_product_id();
            $beacon_courses = carbon_get_post_meta($product_id, 'beacon_courses');

            if (is_array($beacon_courses)) {
                foreach ($beacon_courses as $beacon_course) {
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

                        $response = $this->send_request($resource, $payload, $order_id);

                        $this->log_to_db("[Training] Order " . $order_id, [
                            'type'    => 'training',
                            'api_url' => $resource,
                            'args'    => $payload,
                            'return'  => $response,
                        ]);
                    }
                }
            }
        }
    }

    /* -------------------------------------------------------------------------- */
    /* LOGGING & UTILS                                                            */
    /* -------------------------------------------------------------------------- */

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

    public function add_beacon_id_user_column($columns)
    {
        $columns['beacon_id'] = 'Beacon ID';
        return $columns;
    }

    public function fill_beacon_id_user_column($output, $column_name, $user_id)
    {
        if ($column_name === 'beacon_id') {
            $beacon_id = get_user_meta($user_id, 'beacon_user_id', true);
            return !empty($beacon_id) ? esc_html($beacon_id) : '—';
        }
        return $output;
    }

    public function make_beacon_id_column_sortable($columns)
    {
        $columns['beacon_id'] = 'beacon_user_id';
        return $columns;
    }
}

new Beacon_CRM_Integration();