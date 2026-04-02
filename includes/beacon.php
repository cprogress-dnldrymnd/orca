<?php

/**
 * Plugin Name: Beacon CRM Integration for WooCommerce
 * Description: Handles synchronisation of Orders and Course Data to Beacon CRM. Settings managed via Settings > Beacon CRM. Product Fields managed via Product Data > Beacon CRM Tab or Variations.
 * Author: Digitally Disruptive - Donald Raymundo
 * Author URI: https://digitallydisruptive.co.uk/
 * Version: 1.1.0
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class Beacon_CRM_Integration
 * * Core class responsible for handling the Beacon CRM Integration within WooCommerce.
 * Utilises OOP principles to encapsulate API interactions, administrative interfaces,
 * and core business logic for order and course synchronisation.
 */
class Beacon_CRM_Integration
{

    /**
     * Settings Option Keys
     */
    const OPT_API_KEY    = 'beacon_crm_api_key';
    const OPT_ACCOUNT_ID = 'beacon_crm_account_id';
    const OPT_API_BASE   = 'beacon_crm_api_base';

    /**
     * Singleton instance.
     *
     * @var Beacon_CRM_Integration|null
     */
    private static $instance = null;

    /**
     * Retrieves the singleton instance of the class.
     *
     * @return Beacon_CRM_Integration
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor: Hooks into WordPress and WooCommerce actions.
     * Registers settings, custom user columns, metaboxes, and product data hooks.
     */
    private function __construct()
    {
        // Admin Settings Menu
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

        // AJAX Order Search
        add_action('wp_ajax_beacon_search_orders', [$this, 'ajax_search_orders']);
        add_action('wp_ajax_beacon_init_bulk_sync', [$this, 'ajax_init_bulk_sync']);
        add_action('wp_ajax_beacon_process_chunk', [$this, 'ajax_process_chunk']);

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

        // --- Product Meta Fields (Simple - MULTIPLE) ---
        add_action('woocommerce_product_options_general_product_data', [$this, 'render_simple_product_fields']);
        add_action('woocommerce_process_product_meta', [$this, 'save_simple_product_fields']);

        // --- Product Meta Fields (Variations - SINGLE) ---
        add_action('woocommerce_product_after_variable_attributes', [$this, 'render_variation_fields'], 10, 3);
        add_action('woocommerce_save_product_variation', [$this, 'save_variation_fields'], 10, 2);

        // Log Admin Columns & Filters
        add_filter('manage_beaconcrmlogs_posts_columns', [$this, 'add_log_columns']);
        add_action('manage_beaconcrmlogs_posts_custom_column', [$this, 'fill_log_columns'], 10, 2);
        add_action('restrict_manage_posts', [$this, 'add_log_filters']);
        add_action('pre_get_posts', [$this, 'filter_logs_by_meta']);
    }

    /* -------------------------------------------------------------------------- */
    /* PRODUCT ADMIN FIELDS                                                       */
    /* -------------------------------------------------------------------------- */

    /**
     * Registers a custom tab in the WooCommerce Product Data meta box
     * to logically partition distinct components.
     *
     * @param array $tabs Existing product data tabs.
     * @return array Modified product data tabs.
     */
    public function add_beacon_product_data_tab($tabs)
    {
        $tabs['beacon_crm'] = [
            'label'  => __('Beacon CRM', 'woocommerce'),
            'target' => 'beacon_crm_product_data',
            'class'  => ['show_if_simple', 'show_if_variable'],
        ];
        return $tabs;
    }

    /**
     * Render Repeater fields for Simple Products within the custom Beacon CRM tab.
     * Includes advanced repeater functionalities: duplication, reordering, collapsing, and deletion.
     */
    public function render_simple_product_fields()
    {
        global $post;

        // Retrieve existing data
        $courses = get_post_meta($post->ID, '_beacon_courses_data', true);

        // Handle legacy data migration or initialization
        if (! is_array($courses)) {
            $legacy_id = get_post_meta($post->ID, '_beacon_id', true);
            if ($legacy_id) {
                $courses = [
                    [
                        'id'   => $legacy_id,
                        'type' => get_post_meta($post->ID, '_beacon_course_type', true)
                    ]
                ];
            } else {
                $courses = [];
            }
        }

        // Render directly into the General tab
        echo '<div class="options_group" id="beacon_crm_fields" style="padding: 10px 20px;">';
        echo '<h3>Beacon CRM Integration (Courses)</h3>';
        echo '<p class="description">Add one or more Beacon courses linked to this product. These will trigger for Simple Products AND Variations (if merged).</p>';

        // Hidden flag is crucial to detect "empty" submissions
        echo '<input type="hidden" name="_beacon_crm_flag" value="1">';

        echo '<div id="beacon_courses_wrapper">';

        if (! empty($courses)) {
            foreach ($courses as $index => $course) {
                $this->render_course_row($index, $course['id'] ?? '', $course['type'] ?? '');
            }
        }

        echo '</div>'; // End wrapper

        echo '<p><button type="button" class="button button-primary" id="add_beacon_course_row">Add Another Course</button></p>';

        $this->render_repeater_scripts();

        echo '</div>';
    }

    /**
     * Renders a single row for the Beacon CRM Course repeater.
     *
     * @param string|int $index    The unique identifier/index for the row.
     * @param string     $id_val   The existing Beacon ID value.
     * @param string     $type_val The existing Beacon Course Type value.
     */
    private function render_course_row($index, $id_val, $type_val)
    {
?>
        <div class="beacon_course_row" style="border:1px solid #c3c4c7; padding:10px; margin-bottom:10px; background:#fff;">
            <div class="beacon-row-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; padding-bottom:10px; border-bottom:1px solid #eee;">
                <strong>Course Entry</strong>
                <div class="beacon-row-actions">
                    <button type="button" class="button toggle_beacon_row" title="Collapse/Expand">↕</button>
                    <button type="button" class="button move_up_beacon_row" title="Move Up">↑</button>
                    <button type="button" class="button move_down_beacon_row" title="Move Down">↓</button>
                    <button type="button" class="button duplicate_beacon_row" title="Duplicate">Copy</button>
                    <button type="button" class="button remove_beacon_row" style="color: #d63638; border-color:#d63638;" title="Delete">✕</button>
                </div>
            </div>

            <div class="beacon-row-content">
                <p class="form-field">
                    <label>Beacon ID</label>
                    <input type="text" class="short beacon-id-input" name="_beacon_courses_data[<?php echo esc_attr($index); ?>][id]" value="<?php echo esc_attr($id_val); ?>" placeholder="Course ID">
                </p>
                <p class="form-field">
                    <label>Course Type</label>
                    <select class="select short beacon-type-input" name="_beacon_courses_data[<?php echo esc_attr($index); ?>][type]">
                        <option value="">Select Type...</option>
                        <option value="MMS" <?php selected($type_val, 'MMS'); ?>>MMS</option>
                        <option value="OceanWatchers" <?php selected($type_val, 'OceanWatchers'); ?>>OceanWatchers</option>
                        <option value="Introduction" <?php selected($type_val, 'Introduction'); ?>>Introduction</option>
                        <option value="Deep Dive" <?php selected($type_val, 'Deep Dive'); ?>>Deep Dive</option>
                    </select>
                </p>
            </div>
        </div>
    <?php
    }

    /**
     * Outputs the JavaScript required to handle advanced repeater functionality.
     * Supports addition, deletion, duplication, reordering, and collapsing.
     */
    private function render_repeater_scripts()
    {
    ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                const wrapper = $('#beacon_courses_wrapper');

                // Get Row Template
                const getTemplate = (unique_index) => `
                    <div class="beacon_course_row" style="border:1px solid #c3c4c7; padding:10px; margin-bottom:10px; background:#fff;">
                        <div class="beacon-row-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; padding-bottom:10px; border-bottom:1px solid #eee;">
                            <strong>Course Entry</strong>
                            <div class="beacon-row-actions">
                                <button type="button" class="button toggle_beacon_row" title="Collapse/Expand">↕</button>
                                <button type="button" class="button move_up_beacon_row" title="Move Up">↑</button>
                                <button type="button" class="button move_down_beacon_row" title="Move Down">↓</button>
                                <button type="button" class="button duplicate_beacon_row" title="Duplicate">Copy</button>
                                <button type="button" class="button remove_beacon_row" style="color: #d63638; border-color:#d63638;" title="Delete">✕</button>
                            </div>
                        </div>
                        <div class="beacon-row-content">
                            <p class="form-field">
                                <label>Beacon ID</label>
                                <input type="text" class="short beacon-id-input" name="_beacon_courses_data[${unique_index}][id]" value="" placeholder="Course ID">
                            </p>
                            <p class="form-field">
                                <label>Course Type</label>
                                <select class="select short beacon-type-input" name="_beacon_courses_data[${unique_index}][type]">
                                    <option value="">Select Type...</option>
                                    <option value="MMS">MMS</option>
                                    <option value="OceanWatchers">OceanWatchers</option>
                                    <option value="Introduction">Introduction</option>
                                    <option value="Deep Dive">Deep Dive</option>
                                </select>
                            </p>
                        </div>
                    </div>
                `;

                // Add Row
                $('#add_beacon_course_row').on('click', function() {
                    const unique_index = Date.now() + Math.floor(Math.random() * 1000);
                    wrapper.append(getTemplate(unique_index));
                });

                // Remove Row
                wrapper.on('click', '.remove_beacon_row', function() {
                    if (confirm('Are you sure you want to remove this course?')) {
                        $(this).closest('.beacon_course_row').slideUp(300, function() {
                            $(this).remove();
                        });
                    }
                });

                // Collapse/Expand Row
                wrapper.on('click', '.toggle_beacon_row', function() {
                    $(this).closest('.beacon_course_row').find('.beacon-row-content').slideToggle(300);
                });

                // Duplicate Row
                wrapper.on('click', '.duplicate_beacon_row', function() {
                    const currentRow = $(this).closest('.beacon_course_row');
                    const unique_index = Date.now() + Math.floor(Math.random() * 1000);
                    const newRow = $(getTemplate(unique_index));

                    // Copy values
                    newRow.find('.beacon-id-input').val(currentRow.find('.beacon-id-input').val());
                    newRow.find('.beacon-type-input').val(currentRow.find('.beacon-type-input').val());

                    currentRow.after(newRow);
                });

                // Move Up
                wrapper.on('click', '.move_up_beacon_row', function() {
                    const row = $(this).closest('.beacon_course_row');
                    if (row.prev('.beacon_course_row').length) {
                        row.insertBefore(row.prev('.beacon_course_row'));
                    }
                });

                // Move Down
                wrapper.on('click', '.move_down_beacon_row', function() {
                    const row = $(this).closest('.beacon_course_row');
                    if (row.next('.beacon_course_row').length) {
                        row.insertAfter(row.next('.beacon_course_row'));
                    }
                });
            });
        </script>
    <?php
    }

    /**
     * Saves the repeater fields data when a Simple Product is saved.
     * Sanitises input to prevent injection vectors.
     *
     * @param int $post_id The ID of the post/product being saved.
     */
    public function save_simple_product_fields($post_id)
    {
        if (! isset($_POST['_beacon_crm_flag'])) {
            return;
        }

        $sanitized_data = [];

        if (isset($_POST['_beacon_courses_data']) && is_array($_POST['_beacon_courses_data'])) {
            foreach (wp_unslash($_POST['_beacon_courses_data']) as $item) {
                if (! empty($item['id'])) {
                    $sanitized_data[] = [
                        'id'   => sanitize_text_field($item['id']),
                        'type' => isset($item['type']) ? sanitize_text_field($item['type']) : ''
                    ];
                }
            }
        }

        update_post_meta($post_id, '_beacon_courses_data', $sanitized_data);

        // Clean up legacy keys post-migration
        delete_post_meta($post_id, '_beacon_id');
        delete_post_meta($post_id, '_beacon_course_type');
    }

    /**
     * Renders Beacon fields specifically for Variable Products, located inside each variation panel.
     *
     * @param int     $loop           Position in the loop.
     * @param array   $variation_data The variation data.
     * @param WP_Post $variation      The variation post object.
     */
    public function render_variation_fields($loop, $variation_data, $variation)
    {
        $beacon_id   = get_post_meta($variation->ID, '_beacon_id', true);
        $course_type = get_post_meta($variation->ID, '_beacon_course_type', true);

        echo '<div class="beacon_variation_fields form-row form-row-full options_group" style="padding: 10px;">';
        echo '<h4>Beacon CRM Integration</h4>';

        woocommerce_wp_text_input([
            'id'            => '_beacon_id[' . $loop . ']',
            'name'          => '_beacon_id[' . $loop . ']',
            'value'         => $beacon_id,
            'label'         => 'Beacon ID',
            'wrapper_class' => 'form-row form-row-full',
            'desc_tip'      => 'true',
            'description'   => 'Enter the Beacon Course ID associated with this variation.',
        ]);

        woocommerce_wp_select([
            'id'            => '_beacon_course_type[' . $loop . ']',
            'name'          => '_beacon_course_type[' . $loop . ']',
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
     * Saves variation-specific Beacon CRM fields upon variation save.
     *
     * @param int $variation_id The ID of the variation being saved.
     * @param int $i            The index of the variation in the save request.
     */
    public function save_variation_fields($variation_id, $i)
    {
        $id_val = isset($_POST['_beacon_id'][$i]) ? sanitize_text_field(wp_unslash($_POST['_beacon_id'][$i])) : '';
        update_post_meta($variation_id, '_beacon_id', $id_val);

        $type_val = isset($_POST['_beacon_course_type'][$i]) ? sanitize_text_field(wp_unslash($_POST['_beacon_course_type'][$i])) : '';
        update_post_meta($variation_id, '_beacon_course_type', $type_val);
    }

    /* -------------------------------------------------------------------------- */
    /* ADMIN SETTINGS PAGE & UI                                                   */
    /* -------------------------------------------------------------------------- */

    /**
     * Enqueues WooCommerce Select2 (SelectWoo) for the order search dropdown.
     */
    public function enqueue_admin_scripts($hook)
    {
        if (strpos($hook, 'beacon-crm-settings') !== false) {
            wp_enqueue_script('selectWoo');
            wp_enqueue_style('select2');
        }
    }

    public function add_admin_menu()
    {
        add_options_page('Beacon CRM Settings', 'Beacon CRM', 'manage_options', 'beacon-crm-settings', [$this, 'render_settings_page']);
    }

    /**
     * Initialises options, settings sections, and fields via the WP Settings API.
     */
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


    /**
     * Renders the administrative settings page with a tabbed interface.
     */
    public function render_settings_page()
    {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'api';
    ?>
        <div class="wrap">
            <h1>Beacon CRM Integration</h1>

            <h2 class="nav-tab-wrapper">
                <a href="?page=beacon-crm-settings&tab=api" class="nav-tab <?php echo $active_tab === 'api' ? 'nav-tab-active' : ''; ?>">API Configuration</a>
                <a href="?page=beacon-crm-settings&tab=test" class="nav-tab <?php echo $active_tab === 'test' ? 'nav-tab-active' : ''; ?>">Test Integration</a>
                <a href="?page=beacon-crm-settings&tab=bulk" class="nav-tab <?php echo $active_tab === 'bulk' ? 'nav-tab-active' : ''; ?>">Bulk Date Sync</a>
            </h2>

            <?php $this->render_admin_notices(); ?>

            <div class="beacon-tab-content" style="margin-top: 20px; background: #fff; padding: 20px; border: 1px solid #c3c4c7;">
                <?php if ($active_tab === 'api') : ?>
                    <form action="options.php" method="post">
                        <?php
                        settings_fields('beacon_crm_options');
                        do_settings_sections('beacon-crm-settings');
                        submit_button();
                        ?>
                    </form>

                <?php elseif ($active_tab === 'test') : ?>
                    <h2>Test Single Order Sync</h2>
                    <p class="description">Search for a specific order to manually trigger the Beacon CRM sync workflow.</p>

                    <style>
                        /* Target SelectWoo specifically for the Beacon CRM search field to ensure WP Admin UI consistency */
                        .beacon-order-search-container .select2-container {
                            width: 100% !important;
                            max-width: 500px;
                        }

                        .beacon-order-search-container .select2-selection--single {
                            min-height: 32px;
                            border: 1px solid #8c8f94;
                            border-radius: 3px;
                            box-shadow: 0 0 0 transparent;
                        }

                        .beacon-order-search-container .select2-selection__rendered {
                            line-height: 30px;
                            padding-left: 12px;
                            color: #2c3338;
                        }

                        .beacon-order-search-container .select2-selection__arrow {
                            height: 30px;
                        }

                        .beacon-order-search-container .select2-container--focus .select2-selection--single {
                            border-color: #2271b1;
                            box-shadow: 0 0 0 1px #2271b1;
                        }
                    </style>

                    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                        <input type="hidden" name="action" value="beacon_test_sync">
                        <?php wp_nonce_field('beacon_test_sync_nonce', 'beacon_test_nonce'); ?>
                        <table class="form-table beacon-order-search-container">
                            <tr>
                                <th scope="row"><label for="beacon_test_order_id">Select WooCommerce Order</label></th>
                                <td>
                                    <select name="beacon_test_order_id" id="beacon_test_order_id" class="wc-order-search" data-placeholder="Search for an order by ID, Name, or Email..." required></select>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button('Sync Order Now', 'primary'); ?>
                    </form>
                    <script>
                        /**
                         * Initializes the SelectWoo instance for asynchronous WooCommerce Order retrieval.
                         * Maps search queries to the custom 'beacon_search_orders' AJAX endpoint.
                         */
                        jQuery(document).ready(function($) {
                            $('#beacon_test_order_id').selectWoo({
                                ajax: {
                                    url: ajaxurl,
                                    dataType: 'json',
                                    delay: 250,
                                    data: function(params) {
                                        return {
                                            q: params.term,
                                            action: 'beacon_search_orders',
                                            security: '<?php echo wp_create_nonce("beacon_search_orders"); ?>'
                                        };
                                    },
                                    processResults: function(data) {
                                        return {
                                            results: data
                                        };
                                    },
                                    cache: true
                                },
                                minimumInputLength: 1,
                                allowClear: true
                            });
                        });
                    </script>

                <?php elseif ($active_tab === 'bulk') : ?>
                    <h2>Bulk Sync by Date Range</h2>
                    <p class="description">Select a date range to find and push all orders created within that timeframe to Beacon CRM. Processing happens in batches to prevent server timeouts.</p>

                    <div id="beacon-bulk-form-container">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="beacon_date_from">Date From</label></th>
                                <td><input type="date" id="beacon_date_from" required></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="beacon_date_to">Date To</label></th>
                                <td><input type="date" id="beacon_date_to" required></td>
                            </tr>
                        </table>
                        <p>
                            <button type="button" id="beacon-run-bulk" class="button button-primary">Run Bulk Sync</button>
                        </p>
                    </div>

                    <div id="beacon-progress-container" style="display: none; margin-top: 20px; max-width: 600px;">
                        <div style="background: #e0e0e0; width: 100%; height: 24px; border-radius: 3px; overflow: hidden; position: relative;">
                            <div id="beacon-progress-bar" style="background: #2271b1; width: 0%; height: 100%; transition: width 0.3s ease;"></div>
                            <span id="beacon-progress-percentage" style="position: absolute; top: 0; left: 0; width: 100%; line-height: 24px; text-align: center; color: #fff; font-weight: bold; mix-blend-mode: difference;">0%</span>
                        </div>
                        <p id="beacon-progress-status" style="font-weight: 600; margin-top: 8px;">Initializing...</p>
                    </div>

                    <script>
                        jQuery(document).ready(function($) {
                            $('#beacon-run-bulk').on('click', function() {
                                const dateFrom = $('#beacon_date_from').val();
                                const dateTo = $('#beacon_date_to').val();

                                if (!dateFrom || !dateTo) {
                                    alert('Please select both a From and To date.');
                                    return;
                                }

                                $('#beacon-bulk-form-container').slideUp();
                                $('#beacon-progress-container').slideDown();
                                $('#beacon-progress-status').text('Locating orders...');

                                // 1. Initialize Sync and gather IDs
                                $.post(ajaxurl, {
                                    action: 'beacon_init_bulk_sync',
                                    security: '<?php echo wp_create_nonce("beacon_bulk_sync"); ?>',
                                    date_from: dateFrom,
                                    date_to: dateTo
                                }, function(response) {
                                    if (!response.success) {
                                        $('#beacon-progress-status').html('<span style="color:#d63638;">Error: ' + response.data + '</span>');
                                        return;
                                    }

                                    const orderIds = response.data.order_ids;
                                    const totalOrders = response.data.total;
                                    let processedCount = 0;
                                    const chunkSize = 5; // Process 5 orders per request

                                    $('#beacon-progress-status').text('Processing 0 of ' + totalOrders + ' orders...');

                                    // 2. Recursive function to process chunks
                                    function processNextChunk() {
                                        if (orderIds.length === 0) {
                                            $('#beacon-progress-bar').css('width', '100%');
                                            $('#beacon-progress-percentage').text('100%');
                                            $('#beacon-progress-status').html('<span style="color:#00a32a;">Sync Complete! Successfully processed ' + totalOrders + ' orders.</span>');
                                            return;
                                        }

                                        const chunk = orderIds.splice(0, chunkSize);

                                        $.post(ajaxurl, {
                                            action: 'beacon_process_chunk',
                                            security: '<?php echo wp_create_nonce("beacon_bulk_sync"); ?>',
                                            order_ids: chunk
                                        }, function(chunkResponse) {
                                            if (chunkResponse.success) {
                                                processedCount += chunk.length;
                                                const percentage = Math.round((processedCount / totalOrders) * 100);

                                                $('#beacon-progress-bar').css('width', percentage + '%');
                                                $('#beacon-progress-percentage').text(percentage + '%');
                                                $('#beacon-progress-status').text('Processing ' + processedCount + ' of ' + totalOrders + ' orders...');

                                                // Trigger next batch
                                                processNextChunk();
                                            } else {
                                                $('#beacon-progress-status').html('<span style="color:#d63638;">Sync failed during chunk processing. Check console.</span>');
                                            }
                                        }).fail(function() {
                                            $('#beacon-progress-status').html('<span style="color:#d63638;">Server error occurred during processing.</span>');
                                        });
                                    }

                                    // Start processing the first chunk
                                    processNextChunk();
                                });
                            });
                        });
                    </script>
                <?php endif; ?>
            </div>
        </div>
    <?php
    }

    /**
     * Renders success/error notices passed via URL parameters.
     */
    private function render_admin_notices()
    {
        if (! isset($_GET['beacon_test_status'])) return;

        $status = sanitize_text_field(wp_unslash($_GET['beacon_test_status']));

        if ($status === 'success') {
            $order_id = isset($_GET['tested_order']) ? intval($_GET['tested_order']) : 0;
            echo '<div class="notice notice-success is-dismissible"><p><strong>Success!</strong> Sync triggered for Order #' . esc_html($order_id) . '. Check Logs.</p></div>';
        } elseif ($status === 'bulk_success') {
            $count = isset($_GET['processed_count']) ? intval($_GET['processed_count']) : 0;
            echo '<div class="notice notice-success is-dismissible"><p><strong>Bulk Sync Complete!</strong> Successfully processed ' . esc_html($count) . ' orders.</p></div>';
        } elseif ($status === 'invalid_order') {
            echo '<div class="notice notice-error is-dismissible"><p><strong>Error:</strong> Valid order(s) not found.</p></div>';
        } elseif ($status === 'missing_auth') {
            echo '<div class="notice notice-error is-dismissible"><p><strong>Error:</strong> API Key or Account ID missing.</p></div>';
        }
    }

    /**
     * AJAX Endpoint: Searches WooCommerce orders by ID, Name, or Email for the SelectWoo dropdown.
     */
    public function ajax_search_orders()
    {
        check_ajax_referer('beacon_search_orders', 'security');

        if (! current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $term = isset($_GET['q']) ? wc_clean(wp_unslash($_GET['q'])) : '';
        if (empty($term)) {
            wp_send_json([]);
        }

        $orders = wc_get_orders([
            's'      => $term,
            'limit'  => 20,
            'return' => 'objects',
        ]);

        $results = [];
        foreach ($orders as $order) {
            $results[] = [
                'id'   => $order->get_id(),
                'text' => '#' . $order->get_id() . ' - ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . ' (' . $order->get_billing_email() . ')'
            ];
        }

        wp_send_json($results);
    }


    /**
     * AJAX Endpoint: Initializes the bulk sync by retrieving all relevant Order IDs.
     */
    public function ajax_init_bulk_sync()
    {
        check_ajax_referer('beacon_bulk_sync', 'security');
        if (! current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $date_from = isset($_POST['date_from']) ? sanitize_text_field(wp_unslash($_POST['date_from'])) : '';
        $date_to   = isset($_POST['date_to']) ? sanitize_text_field(wp_unslash($_POST['date_to'])) : '';

        if (empty($date_from) || empty($date_to)) {
            wp_send_json_error('Invalid date range.');
        }

        if (! $this->get_credentials()) {
            wp_send_json_error('API credentials missing. Please configure them in the API tab.');
        }

        $orders = wc_get_orders([
            'limit'        => -1,
            'date_created' => $date_from . '...' . $date_to,
            'return'       => 'ids',
        ]);

        if (empty($orders)) {
            wp_send_json_error('No orders found in this date range.');
        }

        wp_send_json_success(['order_ids' => $orders, 'total' => count($orders)]);
    }

    /**
     * AJAX Endpoint: Processes a specific chunk of Order IDs to prevent timeouts.
     */
    public function ajax_process_chunk()
    {
        check_ajax_referer('beacon_bulk_sync', 'security');
        if (! current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $order_ids = isset($_POST['order_ids']) ? array_map('intval', (array) $_POST['order_ids']) : [];

        if (empty($order_ids)) {
            wp_send_json_success();
        }

        foreach ($order_ids as $order_id) {
            $this->handle_payment_complete($order_id);
            $this->handle_training_logic($order_id);
        }

        // Enforce a strict 500ms delay per chunk to safeguard against API rate-limiting
        usleep(500000);

        wp_send_json_success();
    }

    /**
     * Renders the API Key input field.
     */
    public function render_field_api_key()
    {
        $value = get_option(self::OPT_API_KEY);
        echo '<input type="password" name="' . esc_attr(self::OPT_API_KEY) . '" value="' . esc_attr($value) . '" class="regular-text">';
    }

    /**
     * Renders the Account ID input field.
     */
    public function render_field_account_id()
    {
        $value = get_option(self::OPT_ACCOUNT_ID);
        echo '<input type="text" name="' . esc_attr(self::OPT_ACCOUNT_ID) . '" value="' . esc_attr($value) . '" class="regular-text">';
    }

    /**
     * Renders the API Base URL input field.
     */
    public function render_field_api_base()
    {
        $value = get_option(self::OPT_API_BASE, 'https://api.beaconcrm.org/v1/account/');
        echo '<input type="url" name="' . esc_attr(self::OPT_API_BASE) . '" value="' . esc_attr($value) . '" class="regular-text">';
    }

    /* -------------------------------------------------------------------------- */
    /* LOG & META BOXES                                                           */
    /* -------------------------------------------------------------------------- */

    /**
     * Registers the meta box for the custom 'beaconcrmlogs' post type.
     */
    public function register_log_metabox()
    {
        add_meta_box('beacon_crm_log_details', 'CRM Log Information', [$this, 'render_log_metabox'], 'beaconcrmlogs', 'normal', 'high');
    }

    /**
     * Renders log execution details (Request Arguments and API Response) dynamically.
     *
     * @param WP_Post $post The post object being rendered.
     */
    public function render_log_metabox($post)
    {
        $log_type   = get_post_meta($post->ID, 'type', true);
        $api_url    = get_post_meta($post->ID, 'api_url', true);
        $log_args   = get_post_meta($post->ID, 'args', true);
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

    /**
     * Manages form submission for the Manual Test Sync panel.
     * Validates nonces, permissions, and directs flow into standard handlers.
     */
    public function handle_test_sync_submission()
    {
        if (! isset($_POST['beacon_test_nonce']) || ! wp_verify_nonce(sanitize_key($_POST['beacon_test_nonce']), 'beacon_test_sync_nonce')) {
            wp_die('Invalid security nonce.');
        }

        if (! current_user_can('manage_options')) {
            wp_die('Unauthorized user.');
        }

        $order_id = isset($_POST['beacon_test_order_id']) ? intval($_POST['beacon_test_order_id']) : 0;
        $order    = wc_get_order($order_id);

        if (! $order) {
            wp_redirect(add_query_arg(['page' => 'beacon-crm-settings', 'beacon_test_status' => 'invalid_order', 'tested_order' => $order_id], admin_url('options-general.php')));
            exit;
        }

        if (! $this->get_credentials()) {
            wp_redirect(add_query_arg(['page' => 'beacon-crm-settings', 'beacon_test_status' => 'missing_auth'], admin_url('options-general.php')));
            exit;
        }

        // Force sequence execution
        $this->handle_payment_complete($order_id);
        $this->handle_training_logic($order_id);

        wp_redirect(add_query_arg(['page' => 'beacon-crm-settings', 'beacon_test_status' => 'success', 'tested_order' => $order_id], admin_url('options-general.php')));
        exit;
    }

    /* -------------------------------------------------------------------------- */
    /* API UTILITIES                                                              */
    /* -------------------------------------------------------------------------- */

    /**
     * Fetches stored API configuration arrays necessary for authentication.
     *
     * @return array|bool Array of credentials on success, false if missing keys.
     */
    private function get_credentials()
    {
        $api_key    = get_option(self::OPT_API_KEY);
        $account_id = get_option(self::OPT_ACCOUNT_ID);
        $api_base   = get_option(self::OPT_API_BASE, 'https://api.beaconcrm.org/v1/account/');

        if (empty($api_key) || empty($account_id)) {
            return false;
        }

        return [
            'api_key'    => $api_key,
            'account_id' => $account_id,
            'base_url'   => trailingslashit($api_base) . $account_id . '/'
        ];
    }

    /**
     * Formats standardized HTTP Headers for communicating with Beacon API.
     *
     * @param string $api_key The valid bearer token.
     * @return array Standard headers.
     */
    private function get_headers($api_key)
    {
        return [
            'Authorization'      => 'Bearer ' . $api_key,
            'Beacon-Application' => 'developer_api',
            'Content-Type'       => 'application/json'
        ];
    }

    /**
     * Executes the API request via wp_remote_request and parses JSON return.
     *
     * @param string $resource Target resource endpoint relative to base_url.
     * @param array  $body     Payload associative array to be JSON-encoded.
     * @param int    $order_id WC Order ID for diagnostic context.
     * @param string $method   HTTP Verb (e.g., PUT, POST, GET).
     * @return array|bool Parsed JSON as an array or false upon error.
     */
    private function send_request($resource, $body, $order_id = 0, $method = 'PUT')
    {
        $creds = $this->get_credentials();
        if (! $creds) {
            return false;
        }

        $response = wp_remote_request($creds['base_url'] . $resource, [
            'body'    => wp_json_encode($body),
            'headers' => $this->get_headers($creds['api_key']),
            'method'  => $method,
            'timeout' => 45,
        ]);

        if (is_wp_error($response)) {
            error_log("Beacon API Error (Order {$order_id}): " . $response->get_error_message());
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

    /**
     * Retrieves an existing Person ID via User Meta mapping or generates a new entity via API upsert.
     *
     * @param WC_Order $order The initialized WooCommerce order instance.
     * @return string|bool Beacon user ID on success, false on failure.
     */
    private function get_or_create_person($order)
    {
        $user_id     = $order->get_user_id();
        $existing_id = get_user_meta($user_id, 'beacon_user_id', true);

        if (! empty($existing_id)) {
            return $existing_id;
        }

        $first_name   = $order->get_billing_first_name();
        $last_name    = $order->get_billing_last_name();
        $email        = $order->get_billing_email();
        $phone        = $order->get_billing_phone();
        $country      = $order->get_billing_country();
        $country_name = isset(WC()->countries->countries[$country]) ? WC()->countries->countries[$country] : $country;

        $payload = [
            "primary_field_key" => "emails",
            "entity"            => [
                "emails"  => [["email" => $email, "is_primary" => true]],
                "name"    => ["full" => "$first_name $last_name", "last" => $last_name, "first" => $first_name],
                'type'    => ['Supporter'],
                "address" => [[
                    "address_line_one" => $order->get_billing_address_1(),
                    "address_line_two" => $order->get_billing_address_2(),
                    "city"             => $order->get_billing_city(),
                    "region"           => $order->get_billing_state(),
                    "postal_code"      => $order->get_billing_postcode(),
                    "country"          => $country_name,
                ]],
                "notes"   => 'Updated via woocommerce checkout'
            ],
        ];

        if (! empty($phone)) {
            $payload['entity']['phone_numbers'] = [["number" => $phone, "is_primary" => true]];
        }

        $resource = 'entity/person/upsert';
        $response = $this->send_request($resource, $payload, $order->get_id());

        // Check if the response is valid and contains an entity ID
        if ($response && isset($response['entity']['id'])) {
            update_user_meta($user_id, 'beacon_user_id', $response['entity']['id']);
            $this->log_to_db("[Person Created] Order " . $order->get_id(), ['type' => 'person', 'api_url' => $resource, 'args' => $payload, 'return' => $response]);
            return $response['entity']['id'];
        }

        // ADDITION: Explicitly log the API failure to the CPT so it is visible in the UI
        $this->log_to_db("[Person Sync Failed] Order " . $order->get_id(), [
            'type'    => 'person',
            'api_url' => $resource,
            'args'    => $payload,
            'return'  => $response // This will capture the API error array or boolean false
        ]);

        return false;
    }

    /**
     * Parses a finalized transaction to sync financial payment objects up to Beacon CRM endpoints.
     * Triggered on WooCommerce payment completion hooks. Consolidates multi-item orders into 
     * a single payment payload and aggregates product names for CRM notation.
     *
     * @param int $order_id Standard WC Order ID numeric value.
     */
    public function handle_payment_complete($order_id)
    {
        $order = wc_get_order($order_id);
        if (! $order) {
            return;
        }

        $beacon_person_id = $this->get_or_create_person($order);
        if (! $beacon_person_id) {
            // Existing logic pushes to PHP error log
            error_log("Beacon Error: No Person ID for Order #{$order_id}");

            // ADDITION: Log the aborted payment sequence to the CPT for diagnostic visibility
            $this->log_to_db("[Payment Aborted] Order " . $order_id, [
                'type'    => 'payment',
                'api_url' => 'N/A',
                'args'    => ['error' => 'Sequence aborted: Missing or failed Person ID generation. Check Person logs for details.'],
                'return'  => false
            ]);
            return;
        }

        $date_paid   = $order->get_date_paid() ? $order->get_date_paid()->format('Y-m-d') : date('Y-m-d');
        $external_id = $order->get_transaction_id() ?: 'MANUAL-' . $order_id;
        $resource    = 'entity/payment/upsert';

        $product_names = [];
        $has_bundle    = false;
        $type = 'Course fees';


        // Iterate through items solely to aggregate names and check for bundle categories
        foreach ($order->get_items() as $item) {
            $name = $item->get_name();

            if (! $has_bundle && has_term('bundles', 'product_cat', $item->get_product_id())) {
                $name .= ' (Bundle Payment)';
                $has_bundle = true;
            }

            $product_names[] = $item->get_name();
        }

        // Construct aggregated CRM note
        $aggregated_names = implode(', ', $product_names);
        $note_text        = 'Payment via WC: ' . $aggregated_names . " [Order ID: {$order_id}]";

        // Construct a single, unified payment payload using the full order total
        $payload = [
            "primary_field_key" => "external_id",
            "entity"            => [
                'external_id'    => $external_id,
                'amount'         => ['value' => $order->get_total(), 'currency' => 'GBP'],
                'type'           => [$type],
                'source'         => ['Training Course'],
                'payment_method' => ['Card'],
                'payment_date'   => [$date_paid],
                'customer'       => [intval($beacon_person_id)],
                'notes'          => $note_text,
            ],
        ];

        // Execute the singular API request for the transaction
        $response = $this->send_request($resource, $payload, $order_id, 'PUT');
        $this->log_to_db("[Payment] Order " . $order_id, ['type' => 'payment', 'api_url' => $resource, 'args' => $payload, 'return' => $response]);
    }

    /**
     * Aggregates relevant Beacon Course IDs for a specific cart item by querying standard
     * post meta arrays and variation-specific definitions.
     *
     * @param int $product_id   The parent WooCommerce Product ID.
     * @param int $variation_id The specific variation ID (if applicable).
     * @return array Numeric array containing gathered distinct course IDs.
     */
    private function get_item_course_ids($product_id, $variation_id)
    {
        $collected_ids = [];

        // 1. ALWAYS Get Parent/General Data (Simple or Variable Parent)
        $courses_data = get_post_meta($product_id, '_beacon_courses_data', true);
        if (is_array($courses_data)) {
            foreach ($courses_data as $c) {
                if (! empty($c['id'])) {
                    $collected_ids[] = intval($c['id']);
                }
            }
        }

        // 2. MERGE Variation Data (if exists)
        if ($variation_id) {
            $v_course_id = get_post_meta($variation_id, '_beacon_id', true);
            if ($v_course_id) {
                $collected_ids[] = intval($v_course_id);
            }
        }

        // Return deduplicated values
        return array_unique($collected_ids);
    }

    /**
     * Parses a finalized transaction to sync financial payment objects up to Beacon CRM endpoints.
     * Triggered on WooCommerce payment completion hooks. Consolidates multi-item orders into 
     * a single payment payload and aggregates product names for CRM notation.
     *
     * @param int $order_id Standard WC Order ID numeric value.
     */
    public function handle_payment_complete($order_id)
    {
        $order = wc_get_order($order_id);
        if (! $order) {
            return;
        }

        $beacon_person_id = $this->get_or_create_person($order);
        if (! $beacon_person_id) {
            error_log("Beacon Error: No Person ID for Order #{$order_id}");
            return;
        }

        $date_paid   = $order->get_date_paid() ? $order->get_date_paid()->format('Y-m-d') : date('Y-m-d');
        $external_id = $order->get_transaction_id() ?: 'MANUAL-' . $order_id;
        $resource    = 'entity/payment/upsert';

        $product_names = [];
        $has_bundle    = false;
        $type = 'Course fees';


        // Iterate through items solely to aggregate names and check for bundle categories
        foreach ($order->get_items() as $item) {
            $name = $item->get_name();

            if (! $has_bundle && has_term('bundles', 'product_cat', $item->get_product_id())) {
                $name .= ' (Bundle Payment)';
                $has_bundle = true;
            }

            $product_names[] = $item->get_name();
        }

        // Construct aggregated CRM note
        $aggregated_names = implode(', ', $product_names);
        $note_text        = 'Payment via WC: ' . $aggregated_names . " [Order ID: {$order_id}]";

        // Construct a single, unified payment payload using the full order total
        $payload = [
            "primary_field_key" => "external_id",
            "entity"            => [
                'external_id'    => $external_id,
                'amount'         => ['value' => $order->get_total(), 'currency' => 'GBP'],
                'type'           => [$type],
                'source'         => ['Training Course'],
                'payment_method' => ['Card'],
                'payment_date'   => [$date_paid],
                'customer'       => [intval($beacon_person_id)],
                'notes'          => $note_text,
            ],
        ];

        // Execute the singular API request for the transaction
        $response = $this->send_request($resource, $payload, $order_id, 'PUT');
        $this->log_to_db("[Payment] Order " . $order_id, ['type' => 'payment', 'api_url' => $resource, 'args' => $payload, 'return' => $response]);
    }

    /**
     * Connects WC products with Beacon internal training records iteratively.
     * Hooks onto WooCommerce 'thankyou' action to parse purchased courses.
     *
     * @param int $order_id Standard WC Order ID numeric value.
     */
    public function handle_training_logic($order_id)
    {
        $order = wc_get_order($order_id);
        if (! $order) {
            return;
        }

        $beacon_person_id = $this->get_or_create_person($order);
        if (! $beacon_person_id) {
            return;
        }

        $resource = 'entity/c_training/upsert';

        foreach ($order->get_items() as $item) {
            $product_id         = $item->get_product_id();
            $variation_id       = $item->get_variation_id();
            $courses_to_process = [];

            // 1. ALWAYS Get Parent/General Data
            $data = get_post_meta($product_id, '_beacon_courses_data', true);
            if (is_array($data)) {
                $courses_to_process = $data;
            }

            // 2. MERGE Variation Data (if exists)
            if ($variation_id) {
                $id   = get_post_meta($variation_id, '_beacon_id', true);
                $type = get_post_meta($variation_id, '_beacon_course_type', true);
                if ($id && $type) {
                    $courses_to_process[] = ['id' => $id, 'type' => $type];
                }
            }

            foreach ($courses_to_process as $course) {
                if (! empty($course['id']) && ! empty($course['type'])) {

                    // Modified naming convention as per user request
                    $c_name = $course['type'] . " [Order ID: {$order_id}]";

                    $payload = [
                        "primary_field_key" => "c_previous_db_id",
                        "entity"            => [
                            "c_person"         => [intval($beacon_person_id)],
                            "c_course"         => [intval($course['id'])],
                            "c_course_type"    => [$course['type']],
                            "c_previous_db_id" => $c_name
                        ]
                    ];

                    $response = $this->send_request($resource, $payload, $order_id);
                    $this->log_to_db("[Training] Order " . $order_id, ['type' => 'training', 'api_url' => $resource, 'args' => $payload, 'return' => $response]);
                }
            }
        }
    }

    /**
     * Commits transaction logs contextively into the WP database utilizing the 'beaconcrmlogs' CPT.
     * Evaluates the API response to assign a success/error status for list filtering.
     *
     * @param string $title       Primary log identifier.
     * @param array  $meta_fields Mapped log data to insert into post meta.
     * @return int|WP_Error Post ID on success, Error on logic failure.
     */
    private function log_to_db($title, $meta_fields = [])
    {
        // Automatically determine success/error status based on API return
        $status = 'success';
        if (empty($meta_fields['return'])) {
            $status = 'error'; // send_request returns false on failure
        } elseif (is_array($meta_fields['return']) && isset($meta_fields['return']['errors'])) {
            $status = 'error'; // Catch internal Beacon API error wrappers if present
        }

        $meta_fields['status'] = $status;

        $result = wp_insert_post([
            'post_title'  => sanitize_text_field($title),
            'post_status' => 'publish',
            'post_type'   => 'beaconcrmlogs',
            'meta_input'  => $meta_fields
        ], true);

        if (is_wp_error($result)) {
            error_log('Beacon Log Error: ' . $result->get_error_message());
        }

        return $result;
    }

    /* -------------------------------------------------------------------------- */
    /* LOG FILTERING & ADMIN COLUMNS                                              */
    /* -------------------------------------------------------------------------- */

    /**
     * Injects custom columns (Payload Type and Status) into the logs list table.
     *
     * @param array $columns Existing column headers.
     * @return array Modified column headers.
     */
    public function add_log_columns($columns)
    {
        $new_columns = [];
        foreach ($columns as $key => $title) {
            if ($key === 'date') {
                $new_columns['log_type']   = 'Payload Type';
                $new_columns['log_status'] = 'Status';
            }
            $new_columns[$key] = $title;
        }
        return $new_columns;
    }

    /**
     * Populates the custom columns with respective meta values for each log entry.
     *
     * @param string $column  The column identifier currently being rendered.
     * @param int    $post_id The ID of the log post.
     */
    public function fill_log_columns($column, $post_id)
    {
        if ('log_type' === $column) {
            $type = get_post_meta($post_id, 'type', true);
            echo $type ? esc_html(ucfirst($type)) : '&mdash;';
        }
        if ('log_status' === $column) {
            $status = get_post_meta($post_id, 'status', true);
            if ($status === 'success') {
                echo '<span style="color: #00a32a; font-weight: 600;">Success</span>';
            } elseif ($status === 'error') {
                echo '<span style="color: #d63638; font-weight: 600;">Error</span>';
            } else {
                echo '&mdash;';
            }
        }
    }

    /**
     * Renders dropdown filters above the log list table for Type and Status.
     *
     * @param string $post_type The post type currently being viewed.
     */
    public function add_log_filters($post_type)
    {
        if ('beaconcrmlogs' !== $post_type) {
            return;
        }

        $current_type   = isset($_GET['beacon_log_type']) ? sanitize_text_field(wp_unslash($_GET['beacon_log_type'])) : '';
        $current_status = isset($_GET['beacon_log_status']) ? sanitize_text_field(wp_unslash($_GET['beacon_log_status'])) : '';

    ?>
        <select name="beacon_log_type">
            <option value="">All Payload Types</option>
            <option value="person" <?php selected($current_type, 'person'); ?>>Person</option>
            <option value="payment" <?php selected($current_type, 'payment'); ?>>Payment</option>
            <option value="training" <?php selected($current_type, 'training'); ?>>Training</option>
        </select>

        <select name="beacon_log_status">
            <option value="">All Statuses</option>
            <option value="success" <?php selected($current_status, 'success'); ?>>Success</option>
            <option value="error" <?php selected($current_status, 'error'); ?>>Error</option>
        </select>
<?php
    }

    /**
     * intercepts the main query to apply meta_query filters based on admin selections.
     *
     * @param WP_Query $query The active WordPress query object.
     */
    public function filter_logs_by_meta($query)
    {
        global $pagenow;

        // Ensure we are modifying the correct admin query
        if ('edit.php' !== $pagenow || ! $query->is_main_query() || 'beaconcrmlogs' !== $query->get('post_type')) {
            return;
        }

        $meta_query = $query->get('meta_query') ?: [];

        // Apply Type Filter
        if (! empty($_GET['beacon_log_type'])) {
            $meta_query[] = [
                'key'     => 'type',
                'value'   => sanitize_text_field(wp_unslash($_GET['beacon_log_type'])),
                'compare' => '='
            ];
        }

        // Apply Status Filter
        if (! empty($_GET['beacon_log_status'])) {
            $meta_query[] = [
                'key'     => 'status',
                'value'   => sanitize_text_field(wp_unslash($_GET['beacon_log_status'])),
                'compare' => '='
            ];
        }

        if (! empty($meta_query)) {
            $query->set('meta_query', $meta_query);
        }
    }

    /**
     * Appends the custom Beacon ID column to WP admin User List arrays.
     *
     * @param array $columns Existing associative array mapped headers.
     * @return array Modified array headers.
     */
    public function add_beacon_id_user_column($columns)
    {
        $columns['beacon_id'] = 'Beacon ID';
        return $columns;
    }

    /**
     * Resolves the rendered output string for individual entries in the User List columns.
     *
     * @param string $output      Standard output render variable.
     * @param string $column_name Contextual check.
     * @param int    $user_id     Pointer identity index.
     * @return string Parsed ID string or fallback placeholder.
     */
    public function fill_beacon_id_user_column($output, $column_name, $user_id)
    {
        if ($column_name === 'beacon_id') {
            $id = get_user_meta($user_id, 'beacon_user_id', true);
            return $id ? esc_html($id) : '&mdash;';
        }
        return $output;
    }

    /**
     * Registers the Beacon ID column as natively sortable in admin lists.
     *
     * @param array $columns Standard array.
     * @return array Modified array allowing query parameter sorting.
     */
    public function make_beacon_id_column_sortable($columns)
    {
        $columns['beacon_id'] = 'beacon_user_id';
        return $columns;
    }
}

// Initialise the singleton instance.
Beacon_CRM_Integration::get_instance();
