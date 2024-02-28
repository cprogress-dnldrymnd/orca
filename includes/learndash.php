<?php
function _user_has_access()
{
    return sfwd_lms_has_access_fn(get_the_ID(), get_current_user_id());
}

function _learndash_course_progress()
{
    if (_user_has_access()) {

        return do_shortcode('[learndash_course_progress course_id="' . get_the_ID() . '"]');
    }
}

add_shortcode('_learndash_course_progress', '_learndash_course_progress');


function _learndash_course_meta()
{
    $html =  '<div class="course-meta">';
    $html .= '<p><strong>Duration:</strong> 2 weeks</p>';
    $html .= '<p><strong>Certification:</strong> ORCA Certified</p>';
    $html .= '</div>';

    return $html;
}
add_shortcode('_learndash_course_meta', '_learndash_course_meta');

function _learndash_status_bubble()
{
    if (_user_has_access()) {
        $course_status = learndash_course_status(get_the_ID(), get_current_user_id());
        return learndash_status_bubble($course_status);
    }
}
add_shortcode('_learndash_status_bubble', '_learndash_status_bubble');

function _learndash_status()
{
    if (_learndash_status_bubble()) {
        return _learndash_status_bubble();
    } else {
        return do_shortcode('<div class="course-add-to-cart">[_learndash_linked_product]</div>');
    }
}
add_shortcode('_learndash_status', '_learndash_status');



function learndash_wp_head()
{
    if (!_user_has_access()) {
?>
        <style>
            #course-info-left {
                display: none;
            }

            #course-info-right {
                width: 100% !important
            }
        </style>
    <?php
    }
}
add_action('wp_head', 'learndash_wp_head');

function learndash_wp_footer()
{
    if (get_post_type() == 'sfwd-courses') {
    ?>
        <script>
            jQuery(document).ready(function() {
                jQuery('.ld-progress-steps').appendTo('#course-progress .learndash-wrapper');
            });
        </script>
<?php
    }
}

add_action('wp_footer', 'learndash_wp_footer');


function _add_to_cart_button($product_id)
{
    $html = '<a href="/shop/?add-to-cart=' . $product_id . '" data-quantity="1" class="button product_type_course add_to_cart_button ajax_add_to_cart" data-product_id="' . $product_id . '"  aria-describedby="" rel="nofollow">Add to basket';
    $html .= '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-clockwise" viewBox="0 0 16 16"> <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z"/> <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466"/> </svg>';
    $html .= '</a>';

    return $html;
}

add_shortcode('_add_to_cart_button', '_add_to_cart_button');

function _learndash_has_linked_product()
{
    $args = array(
        'post_type'  => 'product',
        'meta_query' => array(
            array(
                'key'   => '_related_course',
                'value' => serialize(intval(get_the_ID())),
                'compare' => 'LIKE'
            )
        )
    );
    $products = get_posts($args);

    if (count($products) == 1) {
        return $products;
    } else {
        return false;
    }
}

function _learndash_linked_product()
{
    $products = _learndash_has_linked_product();
    $html = '<span class="ld-status ld-status-waiting ld-tertiary-background" data-ld-tooltip="Enroll in this course to get access" data-ld-tooltip-id="52073"> Not Enrolled</span>';

    if ($products) {
        $html .= _add_to_cart_button($products[0]->ID);
        return $html;
    }
}

add_shortcode('_learndash_linked_product', '_learndash_linked_product');
