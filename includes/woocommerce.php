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
}

add_action('init', 'bbloomer_add_premium_support_endpoint');

// ------------------
// 2. Add new query var

function bbloomer_premium_support_query_vars($vars)
{
    $vars[] = 'courses';
    return $vars;
}

add_filter('query_vars', 'bbloomer_premium_support_query_vars', 0);

// ------------------
// 3. Insert the new endpoint into the My Account menu

function bbloomer_add_premium_support_link_my_account($items)
{
    $items['courses'] = 'Courses';
    return $items;
}

add_filter('woocommerce_account_menu_items', 'bbloomer_add_premium_support_link_my_account');

// ------------------
// 4. Add content to the new tab

function bbloomer_premium_support_content()
{
    echo '<h3>Courses</h3></p>';
    echo do_shortcode('[ld_profile]');
}

add_action('woocommerce_account_courses_endpoint', 'bbloomer_premium_support_content');
// Note: add_action must follow 'woocommerce_account_{your-endpoint-slug}_endpoint' format

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

 add_filter( 'woocommerce_is_sold_individually', '__return_true' );

function product_related_courses()
{
    $_related_course = get_post_meta(get_the_ID(), '_related_course', true);
    $_related_course = array_reverse($_related_course);
?>
    <div class="related-courses my-4">
        <h3> Course Included</h3>
        <?php foreach ($_related_course as $course) { ?>
            <div class="course-item">
                <div class="row align-items-center">
                    <div class="col-3">
                        <?= do_shortcode('[_image id="' . get_post_thumbnail_id($course) . '" size="medium"]') ?>
                    </div>
                    <div class="col-9">
                        <?= do_shortcode('[_heading tag="h4" heading="' . get_the_title($course) . '"]') ?>
                        <?= do_shortcode('[_description description="' . get_the_excerpt($course) . '"]'); ?>
                        <div class="mt-3">
                            <?= do_shortcode('[_learndash_course_button]'); ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
<?php
}

add_action('woocommerce_before_add_to_cart_form', 'product_related_courses');
