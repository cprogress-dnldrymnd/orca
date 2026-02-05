<?php

/**
 * Beacon CRM Integration for WooCommerce
 * Handles synchronisation of Orders and Course Data to Beacon CRM.
 * Settings managed via Settings > Beacon CRM.
 * Product Fields managed via Product Data > General or Variations.
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

        // --- NEW: Product Meta Fields (Simple) ---
        add_action('woocommerce_product_options_general_product_data', [$this, 'render_simple_product_fields']);
        add_action('woocommerce_process_product_meta', [$this, 'save_simple_product_fields']);

        // --- NEW: Product Meta Fields (Variations) ---
        add_action('woocommerce_product_after_variable_attributes', [$this, 'render_variation_fields'], 10, 3);
        add_action('woocommerce_save_product_variation', [$this, 'save_variation_fields'], 10, 2);
    }

    /* -------------------------------------------------------------------------- */
    /* PRODUCT ADMIN FIELDS (NEW)                                                 */
    /* -------------------------------------------------------------------------- */

    /**
     * Render fields for Simple Products (General Tab)
     */
    public function render_simple_product_fields()
    {
        echo '<div class="options_group">';

        echo '<h3>Beacon CRM Integration</h3>';

        woocommerce_wp_text_input([
            'id'          => '_beacon_id',
            'label'       => 'Beacon ID',
            'desc_tip'    => 'true',
            'description' => 'Enter the Beacon Course ID associated with this product.',
            'type'        => 'text',
        ]);

        woocommerce_wp_select([
            'id'          => '_beacon_course_type',
            'label'       => 'Course Type',
            'options'     => [
                ''              => 'Select Type...',
                'MMS'           => 'MMS',
                'OceanWatchers' => 'OceanWatchers',
                'Introduction'  => 'Introduction',
                'Deep Dive'     => 'Deep Dive',
            ],
            'desc_tip'    => 'true',
            'description' => 'Select the type of course.',
        ]);

        echo '</div>';
    }

    /**
     * Save fields for Simple Products
     */
    public function save_simple_product_fields($post_id)
    {
        if (isset($_POST['_beacon_id'])) {
            update_post_meta($post_id, '_beacon_id', sanitize_text_field($_POST['_beacon_id']));
        }
        if (isset($_POST['_beacon_course_type'])) {
            update_post_meta($post_id, '_beacon_course_type', sanitize_text_field($_POST['_beacon_course_type']));
        }
    }

    /**
     * Render fields for Variable Products (Inside each Variation)
     */
    public function render_variation_fields($loop, $variation_data, $variation)
    {
        $beacon_id = get_post_meta($variation->ID, '_beacon_id', true);
        $course_type = get_post_meta($variation->ID, '_beacon_course_type', true);

        echo '<div class="beacon_variation_fields form-row form-row-full options_group">';
        echo '<h4>Beacon CRM Integration</h4>';

        // Beacon ID Field
        woocommerce_wp_text_input([
            'id'            => '_beacon_id[' . $loop . ']',
            'name'          => '_beacon_id[' . $loop . ']', // required for variations
            'value'         => $beacon_id,
            'label'         => 'Beacon ID',
            'wrapper_class' => 'form-row form-row-full',
            'desc_tip'      => 'true',
            'description'   => 'Enter the Beacon Course ID associated with this variation.',
        ]);

        // Course Type Field
        woocommerce_wp_select([
            'id'            => '_beacon_course_type[' . $loop . ']',
            'name'          => '_beacon_course_type[' . $loop . ']', // required for variations
            'value'         => $course_type,
            'label'         => 'Course Type',
            'wrapper_class' => 'form-row form-row-full',
            'options'       => [
                ''              => 'Select Type...',
                'MMS'           => 'MMS',
                'OceanWatchers' => 'OceanWatchers',
                'Introduction'  => 'Introduction',
                'Deep Dive'     => 'Deep Dive',
            ]
        ]);

        echo '</div>';
    }

    /**
     * Save fields for Variations
     */
    public function save_variation_fields($variation_id, $i)
    {
        if (isset($_POST['_beacon_id'][$i])) {
            update_post_meta($variation_id, '_beacon_id', sanitize_text_field($_POST['_beacon_id'][$i]));
        }
        if (isset($_POST['_beacon_course_type'][$i])) {
            update_post_meta($variation_id, '_beacon_course_type', sanitize_text_field($_POST['_beacon_course_type'][$i]));
        }
    }

    /* -------------------------------------------------------------------------- */
    /* ADMIN SETTINGS PAGE                                                        */
    /* -------------------------------------------------------------------------- */

    public function add_admin_menu()
    {
        add_options_page('Beacon CRM Settings', 'Beacon CRM', 'manage_options', 'beacon-crm-settings', [$this, 'render_settings_page']);
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
            <?php
            if (isset($_GET['beacon_test_status'])) {
                $status = sanitize_text_field($_GET['beacon_test_status']);
                $order_id = isset($_GET['tested_order']) ? intval($_GET['tested_order']) : 0;
                if ($status === 'success') echo '<div class="notice notice-success is-dismissible"><p><strong>Success!</strong> Sync triggered for Order #' . $order_id . '. Check Logs.</p></div>';
                elseif ($status === 'invalid_order') echo '<div class="notice notice-error is-dismissible"><p><strong>Error:</strong> Order #' . $order_id . ' not found.</p></div>';
                elseif ($status === 'missing_auth') echo '<div class="notice notice-error is-dismissible"><p><strong>Error:</strong> API Key or Account ID missing.</p></div>';
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
            <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                <input type="hidden" name="action" value="beacon_test_sync">
                <?php wp_nonce_field('beacon_test_sync_nonce', 'beacon_test_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="beacon_test_order_id">WooCommerce Order ID</label></th>
                        <td><input name="beacon_test_order_id" type="number" id="beacon_test_order_id" class="regular-text" required></td>
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

    public function register_log_metabox()
    {
        add_meta_box('beacon_crm_log_details', 'CRM Log Information', [$this, 'render_log_metabox'], 'beaconcrmlogs', 'normal', 'high');
    }

    public function render_log_metabox($post)
    {
        $log_type = get_post_meta($post->ID, 'type', true);
        $api_url = get_post_meta($post->ID, 'api_url', true);
        $log_args = get_post_meta($post->ID, 'args', true);
        $log_return = get_post_meta($post->ID, 'return', true);
    ?>
        <style>
            .beacon-log-row {
                margin-bottom: 15px;
                border-bottom: 1px solid #eee;
                padding-bottom: 15px;
            }

            .beacon-log-label {
                font-weight: bold;
                display: block;
                margin-bottom: 5px;
                font-size: 13px;
                color: #2c3338;
            }

            .beacon-log-code {
                background: #f0f0f1;
                padding: 10px;
                border: 1px solid #ccc;
                overflow: auto;
                font-family: monospace;
                max-height: 300px;
            }

            .beacon-log-value {
                font-size: 14px;
            }
        </style>
        <div class="beacon-crm-log-container">
            <div class="beacon-log-row"><span class="beacon-log-label">Type:</span>
                <div class="beacon-log-value"><?php echo esc_html($log_type ?: 'N/A'); ?></div>
            </div>
            <div class="beacon-log-row"><span class="beacon-log-label">API URL:</span>
                <div class="beacon-log-value"><?php echo $api_url ? esc_html($api_url) : 'N/A'; ?></div>
            </div>
            <div class="beacon-log-row"><span class="beacon-log-label">Request Args:</span>
                <div class="beacon-log-code">
                    <pre><?php echo esc_html(print_r($log_args, true)); ?></pre>
                </div>
            </div>
            <div class="beacon-log-row" style="border-bottom:none;"><span class="beacon-log-label">API Return:</span>
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
        if (!isset($_POST['beacon_test_nonce']) || !wp_verify_nonce($_POST['beacon_test_nonce'], 'beacon_test_sync_nonce')) wp_die('Invalid security nonce.');
        if (!current_user_can('manage_options')) wp_die('Unauthorized user.');

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
        if (empty($api_key) || empty($account_id)) return false;
        return ['api_key' => $api_key, 'account_id' => $account_id, 'base_url' => trailingslashit($api_base) . $account_id . '/'];
    }

    private function get_headers($api_key)
    {
        return ['Authorization' => 'Bearer ' . $api_key, 'Beacon-Application' => 'developer_api', 'Content-Type' => 'application/json'];
    }

    private function send_request($resource, $body, $order_id = 0, $method = 'PUT')
    {
        $creds = $this->get_credentials();
        if (!$creds) return false;

        $response = wp_remote_request($creds['base_url'] . $resource, [
            'body'    => json_encode($body),
            'headers' => $this->get_headers($creds['api_key']),
            'method'  => $method,
            'timeout' => 45,
        ]);

        if (is_wp_error($response)) {
            error_log("Beacon API Error (Order $order_id): " . $response->get_error_message());
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
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
        if (!empty($existing_id)) return $existing_id;

        $first_name = $order->get_billing_first_name();
        $last_name  = $order->get_billing_last_name();
        $email      = $order->get_billing_email();
        $phone      = $order->get_billing_phone();
        $country    = $order->get_billing_country();
        $country_name = isset(WC()->countries->countries[$country]) ? WC()->countries->countries[$country] : $country;

        $payload = [
            "primary_field_key" => "emails",
            "entity" => [
                "emails" => [["email" => $email, "is_primary" => true]],
                "name" => ["full" => "$first_name $last_name", "last" => $last_name, "first" => $first_name],
                'type' => ['Supporter'],
                "address" => [[
                    "address_line_one" => $order->get_billing_address_1(),
                    "address_line_two" => $order->get_billing_address_2(),
                    "city" => $order->get_billing_city(),
                    "region" => $order->get_billing_state(),
                    "postal_code" => $order->get_billing_postcode(),
                    "country" => $country_name,
                ]],
                "notes" => 'Updated via woocommerce checkout'
            ],
        ];
        if (!empty($phone)) $payload['entity']['phone_numbers'] = [["number" => $phone, "is_primary" => true]];

        $resource = 'entity/person/upsert';
        $response = $this->send_request($resource, $payload, $order->get_id());

        if ($response && isset($response['entity']['id'])) {
            update_user_meta($user_id, 'beacon_user_id', $response['entity']['id']);
            $this->log_to_db("[Person Created] Order " . $order->get_id(), ['type' => 'person', 'api_url' => $resource, 'args' => $payload, 'return' => $response]);
            return $response['entity']['id'];
        }
        return false;
    }

    public function handle_payment_complete($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) return;

        $beacon_person_id = $this->get_or_create_person($order);
        if (!$beacon_person_id) {
            error_log("Beacon Error: No Person ID for Order #$order_id");
            return;
        }

        $date_paid = $order->get_date_paid() ? $order->get_date_paid()->format('Y-m-d') : date('Y-m-d');
        $external_id = $order->get_transaction_id() ?: 'MANUAL-' . $order_id;
        $resource = 'entity/payment/upsert';

        foreach ($order->get_items() as $item) {
            // Determine Product ID (Variation takes precedence)
            $product_id = $item->get_product_id(); // Parent ID
            $variation_id = $item->get_variation_id();

            // Check Variation first, then Parent
            $item_id_to_check = $variation_id ? $variation_id : $product_id;

            // Fetch Native Meta
            $c_course = get_post_meta($item_id_to_check, '_beacon_id', true);

            // If Variation is empty, fallback to Parent (Optional, remove if strict separation needed)
            if (empty($c_course) && $variation_id) {
                $c_course = get_post_meta($product_id, '_beacon_id', true);
            }

            $c_name = $item->get_name() . " [Order ID: $order_id]";
            $is_bundle = has_term('bundles', 'product_cat', $product_id); // Categories are on Parent

            $payload = [
                "primary_field_key" => "external_id",
                "entity" => [
                    'external_id'    => $external_id,
                    'amount'         => ['value' => $item->get_total(), 'currency' => 'GBP'],
                    'type'           => ['Course fees'],
                    'source'         => ['Training Course'],
                    'payment_method' => ['Card'],
                    'payment_date'   => [$date_paid],
                    'customer'       => [intval($beacon_person_id)],
                    'notes'          => 'Payment via WC: ' . $c_name,
                ],
            ];

            // Logic: Only add 'event' if not bundle AND a course ID exists
            if (!$is_bundle && !empty($c_course)) {
                $payload['entity']['event'] = [intval($c_course)];
            }

            $response = $this->send_request($resource, $payload, $order_id, 'PUT');
            $this->log_to_db("[Payment] Order " . $order_id, ['type' => 'payment', 'api_url' => $resource, 'args' => $payload, 'return' => $response]);
        }
    }

    public function handle_training_logic($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) return;

        $beacon_person_id = $this->get_or_create_person($order);
        if (!$beacon_person_id) return;

        $resource = 'entity/c_training/upsert';

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $variation_id = $item->get_variation_id();
            $item_id_to_check = $variation_id ? $variation_id : $product_id;

            // Fetch Native Meta
            $c_course = get_post_meta($item_id_to_check, '_beacon_id', true);
            $c_course_type = get_post_meta($item_id_to_check, '_beacon_course_type', true);

            // Fallback to parent if empty on variation
            if (empty($c_course) && $variation_id) {
                $c_course = get_post_meta($product_id, '_beacon_id', true);
                $c_course_type = get_post_meta($product_id, '_beacon_course_type', true);
            }

            $c_name = $item->get_name() . " [Order ID: $order_id]";

            if (!empty($c_course) && !empty($c_course_type)) {
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
                $this->log_to_db("[Training] Order " . $order_id, ['type' => 'training', 'api_url' => $resource, 'args' => $payload, 'return' => $response]);
            }
        }
    }

    private function log_to_db($title, $meta_fields = [])
    {
        $result = wp_insert_post(['post_title' => sanitize_text_field($title), 'post_status' => 'publish', 'post_type' => 'beaconcrmlogs', 'meta_input' => $meta_fields], true);
        if (is_wp_error($result)) error_log('Beacon Log Error: ' . $result->get_error_message());
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
            $id = get_user_meta($user_id, 'beacon_user_id', true);
            return $id ? esc_html($id) : '—';
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