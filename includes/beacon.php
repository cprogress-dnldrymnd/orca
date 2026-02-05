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
        
        // Handle Test Sync Submission
        add_action('admin_post_beacon_test_sync', [$this, 'handle_test_sync_submission']);

        // Order Hooks
        add_action('woocommerce_payment_complete', [$this, 'handle_payment_complete']);
        add_action('woocommerce_thankyou', [$this, 'handle_training_logic'], 10, 1);

        // User Admin Columns
        add_filter('manage_users_columns', [$this, 'add_beacon_id_user_column']);
        add_filter('manage_users_custom_column', [$this, 'fill_beacon_id_user_column'], 10, 3);
        add_filter('manage_sortable_columns', [$this, 'make_beacon_id_column_sortable']);

        // Meta Boxes for Logs
        add_action('add_meta_boxes', [$this, 'register_log_metabox']);
    }

    /* -------------------------------------------------------------------------- */
    /* ADMIN SETTINGS PAGE                                                        */
    /* -------------------------------------------------------------------------- */

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

    public function register_settings()
    {
        register_setting('beacon_crm_options', self::OPT_API_KEY, ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('beacon_crm_options', self::OPT_ACCOUNT_ID, ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('beacon_crm_options', self::OPT_API_BASE, ['sanitize_callback' => 'esc_url_raw', 'default' => 'https://api.beaconcrm.org/v1/account/']);

        add_settings_section('beacon_crm_main_section', 'API Configuration', null, 'beacon-crm-settings');

        add_settings_field(self::OPT_API_KEY, 'API Key', [$this, 'render_field_api_key'], 'beacon-crm-settings', 'beacon_crm_main_section');
        add_settings_field(self::OPT_ACCOUNT_ID, 'Account ID', [$this, 'render_field_account_id'], 'beacon-crm-settings', 'beacon_crm_main_section');
        add_settings_field(self::OPT_API_BASE, 'API Base URL', [$this, 'render_field_api_base'], 'beacon-crm-settings', 'beacon_crm_main_section');
    }

    public function render_settings_page()
    {
        ?>
        <div class="wrap">
            <h1>Beacon CRM Integration Settings</h1>
            
            <?php // Display Admin Notices for Test Sync
            if (isset($_GET['beacon_test_status'])) {
                $status = sanitize_text_field($_GET['beacon_test_status']);
                $order_id = isset($_GET['tested_order']) ? intval($_GET['tested_order']) : 0;
                
                if ($status === 'success') {
                    echo '<div class="notice notice-success is-dismissible"><p><strong>Success!</strong> Sync triggered for Order #' . $order_id . '. Check the <a href="edit.php?post_type=beaconcrmlogs">Beacon CRM Logs</a> for API responses.</p></div>';
                } elseif ($status === 'invalid_order') {
                    echo '<div class="notice notice-error is-dismissible"><p><strong>Error:</strong> Order #' . $order_id . ' not found or invalid.</p></div>';
                } elseif ($status === 'missing_auth') {
                    echo '<div class="notice notice-error is-dismissible"><p><strong>Error:</strong> API Key or Account ID is missing.</p></div>';
                }
            }
            ?>

            <form action="options.php" method="post">
                <?php
                settings_fields('beacon_crm_options');
                do_settings_sections('beacon-crm-settings');
                submit_button();
                ?>
            </form>

            <hr style="margin-top: 40px;">

            <h2>Test Integration</h2>
            <p>Manually trigger the sync process for a specific order. This will create/update the Person, add the Payment, and add Training records.</p>
            
            <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                <input type="hidden" name="action" value="beacon_test_sync">
                <?php wp_nonce_field('beacon_test_sync_nonce', 'beacon_test_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="beacon_test_order_id">WooCommerce Order ID</label></th>
                        <td>
                            <input name="beacon_test_order_id" type="number" id="beacon_test_order_id" class="regular-text" required>
                            <p class="description">Enter the ID of an existing order to test (e.g., 12345).</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Test Sync Now', 'secondary'); ?>
            </form>
        </div>
        <?php
    }

    public function render_field_api_key()
    {
        $value = get_option(self::OPT_API_KEY);
        echo '<input type="password" name="' . esc_attr(self::OPT_API_KEY) . '" value="' . esc_attr($value) . '" class="regular-text">';
    }

    public function render_field_account_id()
    {
        $value = get_option(self::OPT_ACCOUNT_ID);
        echo '<input type="text" name="' . esc_attr(self::OPT_ACCOUNT_ID) . '" value="' . esc_attr($value) . '" class="regular-text">';
    }

    public function render_field_api_base()
    {
        $value = get_option(self::OPT_API_BASE, 'https://api.beaconcrm.org/v1/account/');
        echo '<input type="url" name="' . esc_attr(self::OPT_API_BASE) . '" value="' . esc_attr($value) . '" class="regular-text">';
    }

    /* -------------------------------------------------------------------------- */
    /* LOG & META BOXES                                                           */
    /* -------------------------------------------------------------------------- */

    public function register_log_metabox() {
        add_meta_box(
            'beacon_crm_log_details',      // Unique ID
            'CRM Log Information',         // Title
            [$this, 'render_log_metabox'], // Callback
            'beaconcrmlogs',               // Post type
            'normal',                      // Context
            'high'                         // Priority
        );
    }

    public function render_log_metabox($post) {
        $log_type   = get_post_meta($post->ID, 'type', true);
        $api_url    = get_post_meta($post->ID, 'api_url', true);
        $log_args   = get_post_meta($post->ID, 'args', true);
        $log_return = get_post_meta($post->ID, 'return', true);

        ?>
        <style>
            .beacon-log-row { margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
            .beacon-log-label { font-weight: bold; display: block; margin-bottom: 5px; font-size: 13px; color: #2c3338; }
            .beacon-log-code { background: #f0f0f1; padding: 10px; border: 1px solid #ccc; overflow: auto; font-family: monospace; max-height: 300px; }
            .beacon-log-value { font-size: 14px; }
        </style>

        <div class="beacon-crm-log-container">
            <div class="beacon-log-row">
                <span class="beacon-log-label">Type:</span>
                <div class="beacon-log-value">
                    <?php echo esc_html($log_type ? $log_type : 'N/A'); ?>
                </div>
            </div>

            <div class="beacon-log-row">
                <span class="beacon-log-label">API URL:</span>
                <div class="beacon-log-value">
                    <?php if ($api_url): ?>
                        <a href="<?php echo esc_url($api_url); ?>" target="_blank">
                            <?php echo esc_html($api_url); ?>
                        </a>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </div>
            </div>

            <div class="beacon-log-row">
                <span class="beacon-log-label">Request Arguments (Args):</span>
                <div class="beacon-log-code">
                    <pre><?php echo esc_html(print_r($log_args, true)); ?></pre>
                </div>
            </div>

            <div class="beacon-log-row" style="border-bottom: none;">
                <span class="beacon-log-label">API Response (Return):</span>
                <div class="beacon-log-code">
                    <pre><?php echo esc_html(print_r($log_return, true)); ?></pre>
                </div>
            </div>
        </div>
        <?php
    }

    /* -------------------------------------------------------------------------- */
    /* TEST SUBMISSION HANDLER                                                    */
    /* -------------------------------------------------------------------------- */

    public function handle_test_sync_submission()
    {
        if (!isset($_POST['beacon_test_nonce']) || !wp_verify_nonce($_POST['beacon_test_nonce'], 'beacon_test_sync_nonce')) {
            wp_die('Invalid security nonce.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized user.');
        }

        $order_id = isset($_POST['beacon_test_order_id']) ? intval($_POST['beacon_test_order_id']) : 0;
        $order = wc_get_order($order_id);

        if (!$order) {
            wp_redirect(add_query_arg(['page' => 'beacon-crm-settings', 'beacon_test_status' => 'invalid_order', 'tested_order' => $order_id], admin_url('options-general.php')));
            exit;
        }

        if (!$this->get_credentials()) {
            wp_redirect(add_query_arg(['page' => 'beacon-crm-settings', 'beacon_test_status' => 'missing_auth'], admin_url('options-general.php')));
            exit;
        }

        $this->handle_payment_complete($order_id); 
        $this->handle_training_logic($order_id);   

        wp_redirect(add_query_arg(['page' => 'beacon-crm-settings', 'beacon_test_status' => 'success', 'tested_order' => $order_id], admin_url('options-general.php')));
        exit;
    }

    /* -------------------------------------------------------------------------- */
    /* API UTILITIES                                                              */
    /* -------------------------------------------------------------------------- */

    private function get_credentials()
    {
        $api_key = get_option(self::OPT_API_KEY);
        $account_id = get_option(self::OPT_ACCOUNT_ID);
        $api_base = get_option(self::OPT_API_BASE, 'https://api.beaconcrm.org/v1/account/');

        if (empty($api_key) || empty($account_id)) {
            return false;
        }
        $api_base = trailingslashit($api_base);

        return [
            'api_key'    => $api_key,
            'account_id' => $account_id,
            'base_url'   => $api_base . $account_id . '/'
        ];
    }

    private function get_headers($api_key)
    {
        return [
            'Authorization'      => 'Bearer ' . $api_key,
            'Beacon-Application' => 'developer_api',
            'Content-Type'       => 'application/json',
        ];
    }

    private function send_request($resource, $body, $order_id = 0, $method = 'PUT')
    {
        $creds = $this->get_credentials();
        if (!$creds) return false;

        $full_url = $creds['base_url'] . $resource;

        $args = [
            'body'    => json_encode($body),
            'headers' => $this->get_headers($creds['api_key']),
            'method'  => $method,
            'timeout' => 45,
        ];

        $response = wp_remote_request($full_url, $args);

        if (is_wp_error($response)) {
            error_log("Beacon API Error (Order $order_id): " . $response->get_error_message());
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

    private function get_or_create_person($order)
    {
        $user_id = $order->get_user_id();

        $existing_id = get_user_meta($user_id, 'beacon_user_id', true);
        if (!empty($existing_id)) {
            return $existing_id;
        }

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

    public function handle_payment_complete($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) return;

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
                    if (isset($beacon_course['id'])) {
                        $c_course = get__post_meta_by_id($beacon_course['id'], 'beacon_id'); 
                        $beacon_courses_arr[] = intval($c_course);
                    }
                }
            }

            // Check if product category is 'bundles'
            $is_bundle = has_term('bundles', 'product_cat', $product_id);

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
                    // 'event' is intentionally omitted here and added conditionally below
                    'notes'          => 'Payment made via woocommerce checkout for course: ' . $c_name,
                ],
            ];

            // Only add 'event' if it is NOT a bundle
            if (!$is_bundle) {
                $payload['entity']['event'] = $beacon_courses_arr;
            }

            $response = $this->send_request($resource, $payload, $order_id, 'PUT');

            $this->log_to_db("[Payment] Order " . $order_id, [
                'type'    => 'payment',
                'api_url' => $resource,
                'args'    => $payload,
                'return'  => $response,
            ]);
        }
    }

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
                    
                    if ($c_course_id) {
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