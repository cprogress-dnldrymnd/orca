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
function course_created($new_status, $old_status, $post)
{
    if ($new_status == 'publish' && $old_status != 'publish' && $post->post_type == 'sfwd-courses') {
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

    $price = learndash_get_course_price($post->ID)['price'];

    $product->set_regular_price($price);

    $product->save();

    $product->get_id();

    update_post_meta($product->get_id(), '_related_course', array($post->ID));
    $product_price_update = get_option('product_price_update');

    $product_price_update[] = $product->get_id();

    update_option('product_price_update', $product_price_update);
}

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
*/

function action_admin_init()
{
    echo 'xxxxxxx';
    $product_price_update = get_option('product_price_update');
    if ($product_price_update) {
        foreach ($product_price_update as $product_id) {
            $course_id = get_post_meta($product_id, '_related_course', true);
            $price = learndash_get_course_price($course_id[0])['price'];
            $product = new WC_Product_Course($product_id);
            $product->set_regular_price($price);
            $product->save();
        }
    }
}
add_action('admin_head ', 'action_admin_init', 9999);
