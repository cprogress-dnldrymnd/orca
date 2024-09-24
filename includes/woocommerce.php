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
            <?php if ($online_courses_included) { ?>
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
        if ($this_product_id === $product_id) {
            return $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();;
        }
    }
    return false;
}


add_action('woocommerce_thankyou', function ($order_id) {
    $product_ids = array(3255, 3241);
    $in_cart = '';
    foreach ($product_ids as $product_id) {
        $product_is_in_order = bbloomer_check_order_product_id($order_id, $product_id);
        if ($product_is_in_order) {
            $in_cart .= 'true';
            $id = $product_is_in_order;
            $parent = $product_id;
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
        $content = '<html lang="en-US"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><meta content="width=device-width,initial-scale=1" name="viewport"><title>ORCA</title><style type="text/css">@media screen and (max-width:600px){#header_wrapper{padding:27px 36px!important;font-size:24px}#body_content_inner{font-size:10px!important}}</style><style id="chromane_style">body.chromane_rec_nofollow_link_highlighting_enabled a[rel*=nofollow]{margin-right:8px;margin-left:8px;outline:2px dotted #000!important;outline-offset:2px!important}body.chromane_rec_sponsored_link_highlighting_enabled a[rel*=sponsored]{margin-right:8px;margin-left:8px;outline:2px dotted #000!important;outline-color:#000;outline-offset:2px!important}body.chromane_rec_ugc_link_highlighting_enabled a[rel*=ugc]{margin-right:8px;margin-left:8px;outline:2px dotted #000!important;outline-offset:2px!important}</style></head><body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="background-color:#f7f7f7;padding:0;text-align:center" bgcolor="#f7f7f7" class=""><table width="100%" id="outer_wrapper" style="background-color:#f7f7f7" bgcolor="#f7f7f7"><tbody><tr><td></td><td width="600"><div id="wrapper" dir="ltr" style="margin:0 auto;padding:70px 0;width:100%;max-width:600px;-webkit-text-size-adjust:none" width="100%"><table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%"><tbody><tr><td align="center" valign="top"><div id="template_header_image"><p style="margin-top:0"><img src="https://learn.orca.org.uk/staging/wp-content/uploads/2024/04/orca-logo.png" alt="ORCA" style="border:none;display:inline-block;font-size:14px;font-weight:700;height:auto;outline:0;text-decoration:none;text-transform:capitalize;vertical-align:middle;max-width:100%;margin-left:0;margin-right:0" border="0"></p></div><table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_container" style="background-color:#fff;border:1px solid #dedede;box-shadow:0 1px 4px rgba(0,0,0,.1);border-radius:3px" bgcolor="#fff"><tbody><tr><td align="center" valign="top"><table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_header" style="background-color:#2a6ebb;color:#fff;border-bottom:0;font-weight:700;line-height:100%;vertical-align:middle;font-family:&quot" bgcolor="#2a6ebb"><tbody><tr><td id="header_wrapper" style="padding:36px 48px;display:block"><h1 style="color:#fff;font-family:&quot;font-weight:300;line-height:150%;margin:0;text-align:left;text-shadow:0 1px 0 #558bc9;color:#fff;background-color:inherit" bgcolor="inherit"><span style="color:#fff">Thank you for your order</span></h1></td></tr></tbody></table></td></tr><tr><td align="center" valign="top"><table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_body"><tbody><tr><td valign="top" id="body_content" style="background-color:#fff" bgcolor="#fff"><table border="0" cellpadding="20" cellspacing="0" width="100%"><tbody><tr><td valign="top" style="padding:48px 48px 32px"><div id="body_content_inner" style="color:#636363;font-family:&quot;line-height:150%;text-align:left" align="left"><p>Good morning,</p><p>Thank you for your ORCA training course booking. We have received your course booking and we look forward to welcoming you on an ORCA course soon.</p><p>As a small charity, by just booking onto our courses, you are ensuring that we can continue to tackle the issues threatening whales, dolphins and porpoises through our vital scientific research and public engagement work.</p><p>Further information about the Marine Mammal Surveyor Course you have booked can be found below: Marine Mammal Surveyor Course - online on ' . $title . ':</p><p>About two weeks prior to the course, we will be sending you more information about how to attend the live online course (which will be conducted on Zoom).</p><p>If you have any other questions, please have a look at our <a href="https://cdn2.assets-servd.host/orca-web/production/education/MMS_FAQ_s_2024.pdf?dm=1708947623">MMS FAQs</a>, and if your question is not answered there, please do not hesitate to contact us. We look forward to ‘seeing’ you later in 2024.</p><p>Many thanks for your support.</p><p>Best wishes,</p><p>The ORCA Team</p></div></td></tr></tbody></table></td></tr></tbody></table></td></tr></tbody></table></td></tr><tr><td align="center" valign="top"><table border="0" cellpadding="10" cellspacing="0" width="100%" id="template_footer"><tbody><tr><td valign="top" style="padding:0;border-radius:6px"><table border="0" cellpadding="10" cellspacing="0" width="100%"><tbody><tr><td colspan="2" valign="middle" id="credit" style="border-radius:6px;border:0;color:#8a8a8a;font-family:&quot;line-height:150%;text-align:center;padding:24px 0" align="center"><p style="margin:0 0 16px">ORCA | Looking out for whales and dolphins | <a href="https://www.orca.org.uk/">www.orca.org.uk</a></p></td></tr></tbody></table></td></tr></tbody></table></td></tr></tbody></table></div></td><td></td></tr></tbody></table></body></html>';

        wp_mail($to_email, $subject, $content, $headers);
    }
});


function wpse27856_set_content_type()
{
    return "text/html";
}
add_filter('wp_mail_content_type', 'wpse27856_set_content_type');
