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
    $course_status = learndash_course_status(get_the_ID(), get_current_user_id());
    return learndash_status_bubble($course_status, NULL, false);
}
add_shortcode('_learndash_status_bubble', '_learndash_status_bubble');

function _learndash_status()
{
    if (_user_has_access()) {
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
                width: 100% !important;
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



            var lastScrollTop = 0;
            jQuery(window).scroll(function(event) {
                var st = jQuery(this).scrollTop();
                if (st > 500) {
                    jQuery('div#sticky-add-to-cart').addClass('show-sticky');
                } else {
                    jQuery('div#sticky-add-to-cart').removeClass('show-sticky');
                }
                lastScrollTop = st;
            });

            jQuery('.ld-item-list-section-heading').click(function(e) {
                jQuery(this).parent().toggleClass('active');
                e.preventDefault();
            });

            jQuery('.ld-section-heading .ld-expand-button').click(function(e) {
                jQuery('.lesson-parent-item').toggleClass('active');
                e.preventDefault();

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

function _learndash_linked_product($atts)
{
    extract(
        shortcode_atts(
            array(
                'hide_bubble' => 'false',
                'show_price' => 'false',
            ),
            $atts
        )
    );

    $products = _learndash_has_linked_product();

    $html = '';

    if ($hide_bubble == 'false') {
        $html .= '<span class="ld-status ld-status-waiting ld-tertiary-background" data-ld-tooltip="Enroll in this course to get access" data-ld-tooltip-id="52073"> Not Enrolled</span>';
    }


    if ($products) {

        $product = wc_get_product($products[0]->ID);

        if ($show_price == 'true') {
            $html .= $product->get_price_html();
        }

        $html .= _add_to_cart_button($products[0]->ID);
        return $html;
    }
}

add_shortcode('_learndash_linked_product', '_learndash_linked_product');


function _learndash_sticky_add_to_cart()
{
    if (!_user_has_access()) {
        return do_shortcode('[elementor-template id="550"]');
    }
}

add_shortcode('_learndash_sticky_add_to_cart', '_learndash_sticky_add_to_cart');


//modify course the_content
function new_default_content($content)
{
    global $post;
    if ($post->post_type == 'sfwd-courses') {
        $content = $post->post_content;
    }
    return $content;
}
add_filter('the_content', 'new_default_content', 9999);

/*
function action_learndash_focus_header_before() {
    echo do_shortcode('[elementor-template id="122"]');
}

add_action('learndash-focus-header-before', 'action_learndash_focus_header_before');*/

function action_learndash_before_section_heading()
{
    if (is_single() && get_post_type() == 'sfwd-courses') {
        echo '</div><div class="lesson-parent-item">';
    }
}

add_action('learndash-before-section-heading', 'action_learndash_before_section_heading');

function _learndash_image($atts)
{
    extract(
        shortcode_atts(
            array(
                'id' => '',
                'size' => 'large',
                'learndash_status_bubble' => 'false',
                'taxonomy' => '',
            ),
            $atts
        )
    );
    $image_url = wp_get_attachment_image_url($id, $size);
    $html = '<div class="image-box image-box-course">';
    if ($learndash_status_bubble || $taxonomy) {
        $html .= '<div class="meta-box d-flex align-items-center justify-content-end">';
    }
    if ($learndash_status_bubble) {

        $html .= do_shortcode('[_learndash_status_bubble]');
    }

    if($taxonomy) {
        $html .= do_shortcode("[_post_taxonomy_terms taxonomy='$taxonomy']");
    }
    if ($learndash_status_bubble || $taxonomy) {

        $html .= '</div>';
    }


    if ($image_url) {
        $html .= '<img src="' . $image_url . '" >';
    } else {
        $html .= '<img src="/wp-content/plugins/elementor/assets/images/placeholder.png" >';
    }
    $html .= '</div>';

    return $html;
}
add_shortcode('_learndash_image', '_learndash_image');


function _learndash_course_button()
{
    $permalink = get_the_permalink();

    $html = '<div class="row g-3 button-group mt-3">';
    $html .= '<div class="' . (_user_has_access() ? 'col-sm-6' : 'col-6') . '">';
    $html .= "<a  href='$permalink' class='btn btn-black w-100'>View Course</a>";
    $html .= '</div>';

    if (_user_has_access()) {
        $html .= '<div class="col-sm-6">';
        $html .= do_shortcode('[_learndash_linked_product hide_bubble="true"]');
        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
}

add_shortcode('_learndash_course_button', '_learndash_course_button');
