<?php
function filter_woocommerce_cart_redirect_after_error($redirect, $product_id)
{
    $_related_course = get_post_meta($product_id, '_related_course', true);

    if (count($_related_course) == 1) {
        $redirect = esc_url(get_the_permalink($_related_course[0]));
    } else {
        $redirect = esc_url(WC()->cart->get_cart_url());
    }

    return $redirect;
}
add_filter('woocommerce_cart_redirect_after_error', 'filter_woocommerce_cart_redirect_after_error', 10, 2);

function searchfilter($query)
{

    //if ($query->is_search && !is_admin()) {
    //   $query->set('post_type', array('post', 'sfwd-courses'));
    //}

    return $query;
}

add_filter('pre_get_posts', 'searchfilter', 9999999);


/**
 * @snippet       Add First & Last Name to My Account Register Form - WooCommerce
 * @how-to        Get CustomizeWoo.com FREE
 * @author        Rodolfo Melogli
 * @compatible    WooCommerce 8
 * @community     https://businessbloomer.com/club/
 */

///////////////////////////////
// 1. ADD FIELDS

add_action('woocommerce_register_form_start', 'bbloomer_add_name_woo_account_registration');

function bbloomer_add_name_woo_account_registration()
{
?>

    <p class="form-row form-row-first">
        <label for="reg_billing_first_name"><?php _e('First name', 'woocommerce'); ?> <span class="required">*</span></label>
        <input type="text" class="input-text" name="billing_first_name" id="reg_billing_first_name" value="<?php if (!empty($_POST['billing_first_name'])) esc_attr_e($_POST['billing_first_name']); ?>" />
    </p>

    <p class="form-row form-row-last">
        <label for="reg_billing_last_name"><?php _e('Last name', 'woocommerce'); ?> <span class="required">*</span></label>
        <input type="text" class="input-text" name="billing_last_name" id="reg_billing_last_name" value="<?php if (!empty($_POST['billing_last_name'])) esc_attr_e($_POST['billing_last_name']); ?>" />
    </p>

    <div class="clear"></div>

    <?php
}

///////////////////////////////
// 2. VALIDATE FIELDS

add_filter('woocommerce_registration_errors', 'bbloomer_validate_name_fields', 10, 3);

function bbloomer_validate_name_fields($errors, $username, $email)
{
    if (isset($_POST['billing_first_name']) && empty($_POST['billing_first_name'])) {
        $errors->add('billing_first_name_error', __('<strong>Error</strong>: First name is required!', 'woocommerce'));
    }
    if (isset($_POST['billing_last_name']) && empty($_POST['billing_last_name'])) {
        $errors->add('billing_last_name_error', __('<strong>Error</strong>: Last name is required!.', 'woocommerce'));
    }
    return $errors;
}

///////////////////////////////
// 3. SAVE FIELDS

add_action('woocommerce_created_customer', 'bbloomer_save_name_fields');

function bbloomer_save_name_fields($customer_id)
{
    if (isset($_POST['billing_first_name'])) {
        update_user_meta($customer_id, 'billing_first_name', sanitize_text_field($_POST['billing_first_name']));
        update_user_meta($customer_id, 'first_name', sanitize_text_field($_POST['billing_first_name']));
    }
    if (isset($_POST['billing_last_name'])) {
        update_user_meta($customer_id, 'billing_last_name', sanitize_text_field($_POST['billing_last_name']));
        update_user_meta($customer_id, 'last_name', sanitize_text_field($_POST['billing_last_name']));
    }
}

/*
function course_created($new_status, $old_status, $post)
{
    if (($new_status == 'publish') && $old_status != 'publish' && $post->post_type == 'sfwd-courses') {
        create_course_product($post);
    }
}
add_action('transition_post_status', 'course_created', 10, 3);


function create_course_product($post)
{
    $product = new WC_Product_Course(false);

    $product->set_name($post->post_title); // product title

    $product->set_slug($post->post_name);

    $product->set_sku($post->ID);

    $product->save();

    $product->get_id();

    update_post_meta($product->get_id(), '_related_course', array($post->ID));
    $product_price_update = get_option('product_price_update');

    $product_price_update[] = $product->get_id();

    update_option('product_price_update', $product_price_update);
}
*/
/*
add_action('save_post', 'product_save');

function product_save($post_id)
{
    if (get_post_type($post_id) == 'product') {
        $course_id = get_post_meta($post_id, '_related_course', true);
        $price = learndash_get_course_price($course_id[0])['price'];
        $product = new WC_Product_Course($post_id);
        $product->set_regular_price($price);
        $product->save();
    }
}


function update_product_prices()
{
    $product_price_update = get_option('product_price_update');
    if ($product_price_update) {
        foreach ($product_price_update as $product_id) {
            unset($product_price_update[$product_id]);
            $course_id = get_post_meta($product_id, '_related_course', true);
            $price = learndash_get_course_price($course_id[0])['price'];
            $product = new WC_Product_Course($product_id);
            $product->set_regular_price($price);
            $product->save();
        }
        update_option('product_price_update', array());
    }
}

function action_post_updated($post_ID, $post_after, $post_before)
{
    if (get_post_type($post_ID) == 'sfwd-courses') {
        $image = get_post_thumbnail_id($post_ID);
        $args = array(
            'post_type'  => 'product',
            'meta_query' => array(
                array(
                    'key'   => '_sku',
                    'value' => $post_ID,
                )
            )
        );
        $post_ids = array();
        $postslist = get_posts($args);

        foreach ($postslist as $post) {
            $post_ids[] = $post->ID;

            if ($image) {
                set_post_thumbnail($post->ID, $image);
            } else {
                delete_post_thumbnail($post->ID, $image);
            }
        }

        update_option('product_price_update', $post_ids);
    }
}

add_action('post_updated', 'action_post_updated', 10, 3); //don't forget the last argument to allow all three arguments of the function
*/

/**
 * @snippet       WooCommerce Add New Tab @ My Account
 * @how-to        Get CustomizeWoo.com FREE
 * @author        Rodolfo Melogli
 * @compatible    WooCommerce 5.0
 * @community     https://businessbloomer.com/club/
 */

// ------------------
// 1. Register new endpoint (URL) for My Account page
// Note: Re-save Permalinks or it will give 404 error

function bbloomer_add_premium_support_endpoint()
{
    add_rewrite_endpoint('courses', EP_ROOT | EP_PAGES);
    add_rewrite_endpoint('certificates', EP_ROOT | EP_PAGES);
}

add_action('init', 'bbloomer_add_premium_support_endpoint');

// ------------------
// 2. Add new query var

function bbloomer_premium_support_query_vars($vars)
{
    $vars[] = 'courses';
    $items['certificates'] = 'Certificates';
    return $vars;
}

add_filter('query_vars', 'bbloomer_premium_support_query_vars', 0);

// ------------------
// 3. Insert the new endpoint into the My Account menu

function bbloomer_add_premium_support_link_my_account($items)
{
    $items['courses'] = 'Courses';
    $items['certificates'] = 'Certificates';
    return $items;
}

add_filter('woocommerce_account_menu_items', 'bbloomer_add_premium_support_link_my_account');

// ------------------
// 4. Add content to the new tab

function action_courses_tab()
{
    echo '<h3>Courses</h3></p>';
    echo '<hr>';
    echo do_shortcode('[ld_profile]');
}

add_action('woocommerce_account_courses_endpoint', 'action_courses_tab');

function action_certificates_tab()
{
    echo '<h3>Certificates</h3></p>';
    echo '<hr>';

    $args = array(
        'numberposts' => 10,
        'post_type'   => 'sfwd-courses'
    );

    $courses = get_posts($args);
    echo '<div class="certificates-list mb-5 row g-4">';
    foreach ($courses as $course) {
        $ld_certificate =  learndash_get_course_certificate_link($course->ID);
        if ($ld_certificate) {
            echo '<div class="col-12">';
            echo do_shortcode('[_ld_certificate featured_image="' . true . '" id="' . $course->ID . '" label="' . $course->post_title . '"]');
            echo '</div>';
        }
    }
    echo '</div>';
}

add_action('woocommerce_account_certificates_endpoint', 'action_certificates_tab');

/**
 * @snippet       Reorder tabs @ My Account
 * @how-to        Get CustomizeWoo.com FREE
 * @author        Rodolfo Melogli
 * @compatible    WooCommerce 6
 * @community     https://businessbloomer.com/club/
 */

add_filter('woocommerce_account_menu_items', 'bbloomer_add_link_my_account');

function bbloomer_add_link_my_account($items)
{
    $newitems = array(
        'dashboard'       => __('Dashboard', 'woocommerce'),
        'edit-address'    => _n('Addresses', 'Address', (int) wc_shipping_enabled(), 'woocommerce'),
        'edit-account'    => __('Account details', 'woocommerce'),
        'courses'          => __('Courses', 'woocommerce'),
        'certificates'         => __('Certificates', 'woocommerce'),
        'orders'          => __('Orders', 'woocommerce'),
        'downloads'       => __('Downloads', 'woocommerce'),
        'payment-methods' => __('Payment methods', 'woocommerce'),
        'customer-logout' => __('Logout', 'woocommerce'),
    );
    return $newitems;
}


/**
 * Change the placeholder image
 */
add_filter('woocommerce_placeholder_img_src', 'custom_woocommerce_placeholder_img_src');

function custom_woocommerce_placeholder_img_src($src)
{
    $upload_dir = wp_upload_dir();
    $uploads = untrailingslashit($upload_dir['baseurl']);
    // replace with path to your image
    $src = image_dir . 'placeholder.jpg';

    return $src;
}

/*

add_action('after_delete_post', 'action_after_delete_post', 10, 2);
function action_after_delete_post($post_id, $post)
{
    if ('sfwd-courses' == $post->post_type) {
        $product_id = get_product_by_sku($post_id);
        wp_delete_post($product_id);
    }
}


add_action('wp_trash_post', 'action_wp_trash_post', 10);
function action_wp_trash_post($post_id)
{

    if ('sfwd-courses' == get_post_type($post_id)) {
        $product_id = get_product_by_sku($post_id);
        wp_trash_post($product_id);
    }
}
*/
/**
 * @snippet       Override & Force Sold Individually @ WooCommerce Products
 * @how-to        Get CustomizeWoo.com FREE
 * @author        Rodolfo Melogli
 * @compatible    WooCommerce 5
 * @community     https://businessbloomer.com/club/
 */

add_filter('woocommerce_is_sold_individually', '__return_true');

remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10);
add_action('woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10);

/**
 * WooCommerce Loop Product Thumbs
 **/
if (!function_exists('woocommerce_template_loop_product_thumbnail')) {
    function woocommerce_template_loop_product_thumbnail()
    {
        echo "<div class='image-box'>";
        echo woocommerce_get_product_thumbnail();
        echo "</div>";
    }
}
function product_related_courses()
{

    $product = wc_get_product(get_the_ID());

    if ($product->get_type() == 'variable') {
        $_related_course_var = [];
        foreach ($product->get_children() as $var) {
            $_related_course_v = get_post_meta($var, '_related_course', true);
            if ($_related_course_v) {
                $_related_course_var = array_merge($_related_course_var, $_related_course_v);
            }
        }
        $_related_course = array_unique($_related_course_var);
    } else {
        $_related_course = get_post_meta(get_the_ID(), '_related_course', true);
    }

    $online_courses_included = carbon_get_the_post_meta('online_courses_included');



    if ($_related_course || $online_courses_included) {

    ?>
        <div class="related-courses my-4">
            <h3> Course Included</h3>
            <?php
            if ($_related_course) {
                $_related_course = array_reverse($_related_course);
            ?>
                <?php foreach ($_related_course as $course) { ?>
                    <div class="course-item">
                        <div class="row g-3 align-items-center">
                            <div class="col-sm-3">
                                <?= do_shortcode('[_learndash_image learndash_status_bubble="true" id="' . $course . '" image_id="' . get_post_thumbnail_id($course) . '" size="medium"]') ?>
                            </div>
                            <div class="col-sm-9">
                                <?= do_shortcode('[_heading tag="h4" heading="' . get_the_title($course) . '"]') ?>
                                <?= do_shortcode('[_description description="' . get_the_excerpt($course) . '"]'); ?>
                                <div class="mt-3">
                                    <?= do_shortcode('[_learndash_course_button id="' . $course . '"]'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            <?php } ?>
            <?php if ($online_courses_included && has_term(array('bundles'), 'product_cat', get_the_ID())) { ?>
                <?php foreach ($online_courses_included as $online_course) { ?>
                    <?php
                    $course = $online_course['id'];
                    ?>
                    <div class="course-item">
                        <div class="row g-3 align-items-center">
                            <div class="col-sm-3">
                                <?= do_shortcode('[_learndash_image learndash_status_bubble="true" id="' . $course . '" image_id="' . get_post_thumbnail_id($course) . '" size="medium"]') ?>
                            </div>
                            <div class="col-sm-9">
                                <?= do_shortcode('[_heading tag="h4" heading="' . get_the_title($course) . '"]') ?>
                                <?= do_shortcode('[_description description="' . get_the_excerpt($course) . '"]'); ?>
                                <div class="mt-3">
                                    <?= do_shortcode('[_learndash_course_button id="' . $course . '"]'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            <?php } ?>
        </div>
        <div class="important-note mt-4 mb-4">
            <p>
                <strong>Important Note </strong>
            </p>
            <p>
                Please refrain from purchasing bundles which includes courses you are already enrolled too.
            </p>
        </div>
<?php
    }
}

add_action('woocommerce_before_add_to_cart_form', 'product_related_courses');


/**
 * Snippet Name:	WooCommerce Show Coupon Code Used In Emails
 * Snippet Author:	ecommercehints.com
 */

add_action('woocommerce_email_after_order_table', 'ecommercehints_show_coupons_used_in_emails', 10, 4);
function ecommercehints_show_coupons_used_in_emails($order, $sent_to_admin, $plain_text, $email)
{
    if (count($order->get_coupons()) > 0) {
        $html = '<div class="used-coupons">
         <h2>Used coupons<h2>
         <table class="td" cellspacing="0" cellpadding="6" border="1"><tr>
         <th>Coupon Code</th>
         <th>Coupon Amount</th>
         </tr>';

        foreach ($order->get_coupons() as $item) {
            $coupon_code   = $item->get_code();
            $coupon = new WC_Coupon($coupon_code);
            $discount_type = $coupon->get_discount_type();
            $coupon_amount = $coupon->get_amount();

            if ($discount_type == 'percent') {
                $output = $coupon_amount . "%";
            } else {
                $output = wc_price($coupon_amount);
            }

            $html .= '<tr>
                 <td>' . strtoupper($coupon_code) . '</td>
                 <td>' . $output . '</td>
             </tr>';
        }
        $html .= '</table><br></div>';

        $css = '<style>
             .used-coupons table {
                 width: 100%;
                 font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif;
                 color: #737373;
                 border: 1px solid #e4e4e4;
                 margin-bottom:8px;
             }
             .used-coupons table th, table.tracking-info td {
             text-align: left;
             border-top-width: 4px;
             color: #737373;
             border: 1px solid #e4e4e4;
             padding: 12px;
             }
             .used-coupons table td {
             text-align: left;
             border-top-width: 4px;
             color: #737373;
             border: 1px solid #e4e4e4;
             padding: 12px;
             }
         </style>';

        echo $css . $html;
    }
}

/**
 * @snippet       WooCommerce: Check if Product ID is in the Order
 * @how-to        Get CustomizeWoo.com FREE
 * @author        Rodolfo Melogli
 * @compatible    WooCommerce 9
 * @community     https://businessbloomer.com/club/
 */

function bbloomer_check_order_product_id($order_id, $product_id)
{
    $order = wc_get_order($order_id);
    if (! $order) return;
    $items = $order->get_items();
    foreach ($items as $item_id => $item) {
        $this_product_id = $item->get_product_id();
        if ($this_product_id == $product_id) {
            return $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();;
        }
    }
    return false;
}


add_action('woocommerce_thankyou', function ($order_id) {

    $email_sent = get_post_meta($order_id, 'email_sent', true);
    if (!$email_sent) {
        $coursecustomemails = get_posts(array(
            'post_type' => 'coursecustomemails',
            'numberposts' => -1,
        ));

        foreach ($coursecustomemails as $coursecustomemail) {
            $product_ids = carbon_get_post_meta($coursecustomemail->ID, 'products');

            $in_cart = '';
            foreach ($product_ids as $product_id) {
                $product_is_in_order = bbloomer_check_order_product_id($order_id, $product_id['id']);
                if ($product_is_in_order) {
                    $in_cart .= 'true';
                    $id = $product_is_in_order;
                    $parent = $product_id['id'];
                } else {
                    $in_cart .= 'false';
                }
            }
            if (str_contains($in_cart, 'true')) {
                $order = wc_get_order($order_id);
                $to_email = $order->get_billing_email();
                $title = str_replace(get_the_title($parent), '', get_the_title($id));
                $subject = 'ORCA training course booking';

                $headers = 'From: ORCA <website@orca.org.uk>' . "\r\n";
                $content = $coursecustomemail->post_content;
                $content = str_replace('[title]', $title, $content);

                wp_mail($to_email, $subject, $content, $headers);

                update_post_meta($order_id, 'email_sent', true);
            }
        }
    }
});


function wpse27856_set_content_type()
{
    return "text/html";
}
add_filter('wp_mail_content_type', 'wpse27856_set_content_type');





add_action('init', 'add_custom_taxonomy_to_post_type',9999);

function add_custom_taxonomy_to_post_type()
{
    register_taxonomy_for_object_type('ld_course_category', 'product');
}
