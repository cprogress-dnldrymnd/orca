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
    $html = '<div class="course-meta">';
    $html .= '<p><strong>Duration:</strong> 2 weeks</p>';
    $html .= '<p><strong>Certification:</strong> ORCA Certified</p>';
    $html .= '</div>';

    return $html;
}
add_shortcode('_learndash_course_meta', '_learndash_course_meta');


function _learndash_status_bubble()
{

    //return var_dump(learndash_user_get_enrolled_courses(get_current_user_id()));
    if (_user_has_access()) {
        $course_status = learndash_course_status(get_the_ID(), get_current_user_id());
        return learndash_status_bubble($course_status);
    } else {
        return do_shortcode('[_learndash_linked_product]');
    }
}
add_shortcode('_learndash_status_bubble', '_learndash_status_bubble');


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


function _learndash_linked_product()
{
    ob_start();
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


    if ($products) {

        $html = '<div class="course-add-to-cart">';
        $html .= '<span class="ld-status ld-status-waiting ld-tertiary-background" data-ld-tooltip="Enroll in this course to get access" data-ld-tooltip-id="52073"> Not Enrolled</span>';

        if (count($products) == 1) {

            $html .= '<a href="/shop/?add-to-cart=' . $products[0]->ID . '" data-quantity="1" class="button product_type_course add_to_cart_button ajax_add_to_cart" data-product_id="' . $products[0]->ID . '"  aria-describedby="" rel="nofollow">Add to basket</a>';
        }
        $html .= '</div>';

        return $html;
    }
}

add_shortcode('_learndash_linked_product', '_learndash_linked_product');
