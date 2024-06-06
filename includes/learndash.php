<?php
function _user_has_access($id)
{
    $has_access = ld_course_check_user_access($id, get_current_user_id());

    if ($has_access) {
        return true;
    } else {
        return false;
    }
}

function _can_be_purchased($id)
{
    $compare = learndash_get_course_prerequisite_compare($id);
    $prerequisites = learndash_get_course_prerequisites($id, get_current_user_id());
    $prerequisite_enabled =  learndash_get_course_prerequisite_enabled($id);
    if ($prerequisite_enabled) {
        if (is_user_logged_in()) {
            if ($compare == 'ALL') {
                if (in_array(false, $prerequisites)) {
                    return false;
                } else {
                    return true;
                }
            } else {
                if (in_array(true, $prerequisites)) {
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
    } else {
        return true;
    }
}

function _learndash_course_progress($atts)
{
    extract(
        shortcode_atts(
            array(
                'wrapper' => false,
            ),
            $atts
        )
    );
    $html = '';
    if (_user_has_access(get_the_ID())) {
        if ($wrapper) {
            $html .=  '<div class="' . $wrapper . '">';
        }
        $html .=  do_shortcode('[learndash_course_progress course_id="' . get_the_ID() . '"]');
        if ($wrapper && _user_has_access(get_the_ID())) {
            $html .= '</div>';
        }

        return $html;
    }
}

add_shortcode('_learndash_course_progress', '_learndash_course_progress');


function _learndash_course_meta($atts)
{
    extract(
        shortcode_atts(
            array(
                'id' => get_the_ID(),
            ),
            $atts
        )
    );
    $post_type = get_post_type($id);
    $certification = get__post_meta_by_id($id, 'certification');
    $product_id = get_product_by_sku($id);
    $html =  '<div class="course-meta mb-3">';

    if ($certification) {
        $html .= '<p class="d-none"><strong>Duration:</strong> 2 weeks</p>';
        $html .= '<p><strong>Certification:</strong> ' . $certification . '</p>';
    }
    if ($post_type == 'product') {
        $product_p = wc_get_product($id);
        $price_p = $product_p->get_price_html();
        if ($price_p) {
            $html .= '<p"><strong>Price:</strong> ' . $price_p . '</p>';
        }
    } else {
        if (!_user_has_access($id) && $product_id && $post_type != 'product') {
            $product = wc_get_product($product_id);
            $price = $product->get_price_html();
            if ($price) {
                $html .= '<p"><strong>Price:</strong> ' . $price . '</p>';
            }
        }
    }

    $html .= '</div>';

    return $html;
}
add_shortcode('_learndash_course_meta', '_learndash_course_meta');

function _product_meta($atts)
{
    extract(
        shortcode_atts(
            array(
                'id' => '',
            ),
            $atts
        )
    );

    $html =  '<div class="course-meta mb-3">';

    $product = wc_get_product($id);
    $price = $product->get_price_html();

    if ($price) {
        $html .= '<p"><strong>Price:</strong> ' . $price . '</p>';
    }
    $html .= '</div>';

    return $html;
}
add_shortcode('_product_meta', '_product_meta');

function get_product_by_sku($sku)
{

    global $wpdb;

    $product_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku));

    if ($product_id) return $product_id;

    return null;
}
function _learndash_status_bubble($atts)
{
    extract(
        shortcode_atts(
            array(
                'id' => '',
            ),
            $atts
        )
    );
    $course_status = learndash_course_status($id, get_current_user_id());
    return learndash_status_bubble($course_status, NULL, false);
}
add_shortcode('_learndash_status_bubble', '_learndash_status_bubble');

function _learndash_status($atts)
{
    extract(
        shortcode_atts(
            array(
                'id' => '',
            ),
            $atts
        )
    );

    if (_user_has_access($id)) {
        return _learndash_status_bubble($id);
    } else {
        return do_shortcode('<div class="course-add-to-cart d-flex align-items-center justify-content-end">[_learndash_linked_product id="' . $id . '"]</div>');
    }
}
add_shortcode('_learndash_status', '_learndash_status');

function learndash_wp_head()
{
    if (!_user_has_access(get_the_ID())) {
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
    $html = '<a href="/shop/?add-to-cart=' . $product_id . '" data-quantity="1" class="button product_type_course add_to_cart_button ajax_add_to_cart" data-product_id="' . $product_id . '"  aria-describedby="" rel="nofollow">Add to cart';
    $html .= '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-clockwise" viewBox="0 0 16 16"> <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z"/> <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466"/> </svg>';
    $html .= '</a>';

    return $html;
}

function _learndash_has_linked_product($course_id)
{

    $args = array(
        'fields' => 'ids',
        'post_type'  => 'product',
        'meta_query' => array(
            array(
                'key'   => '_related_course',
                'value' => serialize(intval($course_id)),
                'compare' => 'LIKE'
            )
        )
    );

    $products = get_posts($args);

    if (count($products) > 0) {
        return $products;
    } else {
        return false;
    }
}

function _learndash_included_in_bundle($id)
{
    $args = array(
        'fields' => 'ids',
        'post_type'  => 'product',
        'meta_query' => array(
            array(
                'key'   => '_related_course',
                'value' => serialize(intval($id)),
                'compare' => 'LIKE'
            )
        ),
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => 'bundles',
            )
        )
    );

    $products = get_posts($args);

    if (count($products) > 0) {
        return $products;
    } else {
        return false;
    }
}



/*
function _learndash_sticky_add_to_cart()
{
    if (!_user_has_access(get_the_ID())) {
        return do_shortcode('[elementor-template id="550"]');
    }
}

add_shortcode('_learndash_sticky_add_to_cart', '_learndash_sticky_add_to_cart');
*/

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
                'image_id' => '',
                'id' => get_the_ID(),
                'size' => 'large',
                'learndash_status_bubble' => 'false',
                'taxonomy' => '',
            ),
            $atts
        )
    );
    $post_type = get_post_type($id);
    $image_url = wp_get_attachment_image_url($image_id, $size);
    $html = '<div class="image-box image-box-course">';
    if ($learndash_status_bubble == 'true' || $taxonomy) {
        $html .= '<div class="meta-box d-flex align-items-center justify-content-end flex-wrap">';
    }
    if ($learndash_status_bubble) {
        $html .= do_shortcode('[_learndash_status_bubble id="' . $id . '"]');
    }

    if ($post_type == 'product') {
        $html .= '<div class="ld-status ld-status-complete ld-secondary-background">Bundle</div>';
    }

    if ($taxonomy) {
        $html .= do_shortcode("[_post_taxonomy_terms taxonomy='$taxonomy']");
    }
    if ($learndash_status_bubble || $taxonomy) {

        $html .= '</div>';
    }


    if ($image_url) {
        $html .= '<img src="' . $image_url . '" >';
    } else {
        $html .= '<img src="' . image_dir . '/placeholder.jpg" >';
    }
    $html .= '</div>';

    return $html;
}
add_shortcode('_learndash_image', '_learndash_image');


function _learndash_course_button($atts)
{
    extract(
        shortcode_atts(
            array(
                'id' => '',
            ),
            $atts
        )
    );
    $post_type = get_post_type($id);
    $permalink = get_the_permalink($id);
    $html = '<div class="row g-3 button-group">';


    if ($post_type == 'sfwd-courses') {

        if (_user_has_access($id) == false && _can_be_purchased($id)) {
            $html .= '<div class="col-6">';
            $html .= "<a  href='$permalink' class='btn btn-black w-100'>View Course</a>";
            $html .= '</div>';
            $html .= '<div class="col-lg-6">';
            $html .= do_shortcode('[_learndash_linked_product id="' . $id . '" hide_bubble="true"]');
            $html .= '</div>';
        } else if (_user_has_access($id) == true && _can_be_purchased($id)) {
            $html .= '<div class="col-6">';
            $html .= "<a  href='$permalink' class='btn btn-black w-100'>View Course</a>";
            $html .= '</div>';
            $html .= '<div class="col-lg-6">';
            $html .= do_shortcode('[_button class="button add_to_cart_button disabled" button_text="Already Enrolled" button_link="#"]');
            $html .= '</div>';
        } else {
            $html .= '<div class="col-12">';
            $html .= "<a  href='$permalink' class='btn btn-black w-100'>View Course</a>";
            $html .= '</div>';
        }
    } else {
        $html .= '<div class="col-12">';
        $html .= "<a  href='$permalink' class='btn btn-black w-100'>View Bundle</a>";
        $html .= '</div>';
    }

    $html .= '</div>';
    return $html;
}

add_shortcode('_learndash_course_button', '_learndash_course_button');




function _learndash_linked_product($atts)
{
    extract(
        shortcode_atts(
            array(
                'id' => '',
                'hide_bubble' => 'false',
                'hide_add_to_cart' => 'false',
                'redirect_to_single' => 'false'
            ),
            $atts
        )
    );

    $products = _learndash_has_linked_product($id);

    $html = '';

    if ($hide_bubble == 'false') {
        $html .= '<span class="ld-status ld-status-waiting ld-tertiary-background" data-ld-tooltip="Enroll in this course to get access" data-ld-tooltip-id="52073"> Not Enrolled</span>';
    }

    if (_user_has_access($id) == false && _can_be_purchased($id)) {
        if ($hide_add_to_cart == 'false') {
            if ($products) {
                $html .= '<a class="button add_to_cart_button" href="' . get_permalink(wc_get_page_id('shop')) . '?id=' . $id . '" >  Add to cart </a>';
            }
        }
    }

    return $html;
}

add_shortcode('_learndash_linked_product', '_learndash_linked_product');

function _course_cta()
{

    $cta_heading = get__post_meta('cta_heading');
    $cta_description = get__post_meta('cta_description');
    $cta_button_text = get__post_meta('cta_button_text');
    $cta_button_link = get__post_meta('cta_button_link');
    $cta_background_image = get__post_meta('cta_background_image');
    if ($cta_heading || $cta_description) {
        $html = '<div class="course-cta position-relative">';
        if ($cta_background_image) {
            $html .= do_shortcode("[_image id='$cta_background_image' size='large' class='not-absolute image-box-background']");
        }
        $html .= '<div class="inner color-white position-relative ">';

        if ($cta_heading) {
            $html .= "<p><strong> $cta_heading </strong></p>";
        }
        if ($cta_description) {
            $html .= wpautop($cta_description);
        }

        if ($cta_button_text) {
            $html .= do_shortcode("[_button button_text='$cta_button_text' button_link='$cta_button_link' class='btn-white']");
        }

        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }
}
add_shortcode('_course_cta', '_course_cta');


function _course_banner()
{

    $banner_heading = get__post_meta('banner_heading');
    $banner_description = get__post_meta('banner_description');
    $banner_background_image = get__post_meta('banner_background_image');
    if ($banner_heading || $banner_description) {
        $html = '<div class="course-banner position-relative background-primary">';


        if ($banner_background_image) {
            $html .= do_shortcode("[_image id='$banner_background_image' size='large' class='not-absolute image-box-background']");
        }
        $html .= '<div class="inner color-white position-relative ">';

        if ($banner_heading) {
            $html .= "<h2><strong> $banner_heading </strong></h2>";
        }
        if ($banner_description) {
            $html .= wpautop($banner_description);
        }


        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }
}

add_shortcode('_course_banner', '_course_banner');


function _course_outcomes()
{
    $outcomes_heading = get__post_meta('outcomes_heading');
    $outcomes = get__post_meta('outcomes');

    if ($outcomes_heading || $outcomes) {
        if ($outcomes_heading) {
            $html = '<p><strong> Outcomes: ' . $outcomes_heading . '</strong></p>';
        }
        if ($outcomes) {
            $html = wpautop($outcomes);
        }

        return $html;
    }
}
add_shortcode('_course_outcomes', '_course_outcomes');

function _course_highlight()
{
    $highlight_heading = get__post_meta('highlight_heading');
    $highlight_description = get__post_meta('highlight_description');
    $highlight_image = get__post_meta('highlight_image');

    if ($highlight_heading || $highlight_description) {
        $html = '<div class="course-highlight">';
        $html .= '<div class="row align-items-center gy-4 gy-lg-0">';
        $html .= '<div class="col-md-8">';
        if ($highlight_heading) {
            $html .= "<p><strong> $highlight_heading </strong></p>";
        }
        if ($highlight_description) {
            $html .= wpautop($highlight_description);
        }
        $html .= '</div>';

        if ($highlight_image) {
            $html .= '<div class="col-md-4 text-end">';
            $html .= do_shortcode("[_image id='$highlight_image' size='large' class='not-absolute']");
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }
}
add_shortcode('_course_highlight', '_course_highlight');


function _course_breakdown()
{
    $course_breakdown = get__post_meta('course_breakdown');

    if ($course_breakdown) {
        $html = '<p><strong> Course Breakdown </strong></p>';

        $html .= wpautop($course_breakdown);

        return $html;
    }
}
add_shortcode('_course_breakdown', '_course_breakdown');


function _course_testimonial()
{
    $args = array(
        'numberposts' => -1,
        'post_type'   => 'testimonials'
    );

    $testimonials = get_posts($args);

    if ($testimonials) {
        $html = '<div class="background-dark">';
        $html .= '<div class="swiper-testimonial-holder px-4 py-5">';
        $html .= '<div class="swiper swiper-testimonial">';
        $html .= '<div class="swiper-wrapper">';

        foreach ($testimonials as $testimonial) {
            $html .= '<div class="swiper-slide">';
            $html .= '<div class="row g-5 align-items-center">';
            $html .= '<div class="col-lg-6">';
            $html .= do_shortcode('[_image id="' . get_post_thumbnail_id($testimonial->ID) . '"]');
            $html .= '</div>';
            $html .= '<div class="col-lg-6">';
            $html .= '<div class="title"><strong>A word from our alumni…</strong></div>';
            $html .= '<div class="quote"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-quote" viewBox="0 0 16 16"> <path d="M12 12a1 1 0 0 0 1-1V8.558a1 1 0 0 0-1-1h-1.388q0-.527.062-1.054.093-.558.31-.992t.559-.683q.34-.279.868-.279V3q-.868 0-1.52.372a3.3 3.3 0 0 0-1.085.992 4.9 4.9 0 0 0-.62 1.458A7.7 7.7 0 0 0 9 7.558V11a1 1 0 0 0 1 1zm-6 0a1 1 0 0 0 1-1V8.558a1 1 0 0 0-1-1H4.612q0-.527.062-1.054.094-.558.31-.992.217-.434.559-.683.34-.279.868-.279V3q-.868 0-1.52.372a3.3 3.3 0 0 0-1.085.992 4.9 4.9 0 0 0-.62 1.458A7.7 7.7 0 0 0 3 7.558V11a1 1 0 0 0 1 1z"/> </svg></div>';
            $html .= '<div class="content ps-4 font-sm">';
            $html .= do_shortcode('[_description description="' . get_the_content(NULL, false, $testimonial->ID) . '"]');
            $html .= '<div class="author color-accent fw-semibold mt-3">' . get_the_title($testimonial->ID) . '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }


        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="swiper-button-next swiper-button"></div> <div class="swiper-button-prev swiper-button"></div></div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    } else {
        return '<br><br><br>';
    }
}
add_shortcode('_course_testimonial', '_course_testimonial');

function _course_group()
{
    if (_user_has_access(get_the_ID())) {

        $learndash_get_users_group_ids = learndash_get_users_group_ids(get_current_user_id());
        if ($learndash_get_users_group_ids) {
            $html = '<div class="col-auto">';
            $html .= '<div class="course-group">';
            foreach ($learndash_get_users_group_ids as $group) {
                $image_id = get_post_thumbnail_id($group);
                $html .= do_shortcode('[_image id="' . $image_id . '" class="not-absolute" size="medium"]');
            }

            $html .= '</div>';
            $html .= '</div>';

            return $html;
        }
    }
}

add_shortcode('_course_group', '_course_group');
function _course_group_archive()
{
    $learndash_get_users_group_ids = learndash_get_users_group_ids(get_current_user_id());
    if ($learndash_get_users_group_ids) {
        $html = '<div class="col-auto">';
        $html .= '<div class="course-group">';
        foreach ($learndash_get_users_group_ids as $group) {
            $image_id = get_post_thumbnail_id($group);
            $html .= do_shortcode('[_image id="' . $image_id . '" class="not-absolute" size="medium"]');
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}

add_shortcode('_course_group_archive', '_course_group_archive');


function lessons_images()
{
    if (get_post_type() == 'sfwd-lessons') {
        $image = get_the_post_thumbnail_url(get_the_ID(), 'large');
        if ($image) {
        ?>
            <style>
                #learndash_post_<?= get_the_ID() ?>::before {
                    content: '';
                    padding: 27%;
                    background-size: cover;
                    background-position: center;
                    display: block;
                    background-image: url(<?= $image ?>);
                    border-radius: 10px;
                    margin-bottom: 1rem;
                    background-color: var(--accent-color);
                }
            </style>
<?php
        }
    }
}
add_action('wp_head', 'lessons_images');


function _ld_certificate()
{
    $ld_certificate =  learndash_get_course_certificate_link(get_the_ID());
    $html = '';

    if ($ld_certificate) {
        $html .= '<div class="certificate-box">';

        $html .= '<div class="row g-3">';

        $html .= '<div class="col-auto">';
        $html .= '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000" version="1.1" id="Capa_1" width="800px" height="800px" viewBox="0 0 169.351 169.351" xml:space="preserve"> <g> <g> <path d="M19.449,100.249c0,8.281,50.336,8.281,50.336,0c0-7.003-8.068-16.465-18.962-19.229c4.552-3.054,7.691-9.101,7.691-14.538    c0-7.675-6.217-13.904-13.898-13.904c-7.675,0-13.899,6.229-13.899,13.904c0,5.438,3.136,11.484,7.676,14.538    C27.523,83.784,19.449,93.246,19.449,100.249z M43.498,85.062h-0.061l-2.302-2.648c1.12,0.396,2.268,0.651,3.486,0.651    c1.215,0,2.363-0.243,3.483-0.64l-2.305,2.637h-0.058l5.943,14.358l-7.063,7.039l-7.06-7.039L43.498,85.062z"/> <path d="M69.785,79.509c0,1.802,1.44,3.251,3.242,3.251h39.391c0.512-0.365,1.114-0.694,1.937-0.925v-5.572H73.027    C71.234,76.263,69.785,77.715,69.785,79.509z"/> <path d="M114.354,61.467H73.027c-1.793,0-3.242,1.446-3.242,3.249c0,1.79,1.44,3.249,3.242,3.249h41.327V61.467z"/> <path d="M20.164,128.612c-6.67,0-12.096-5.432-12.096-12.093V42.52c0-6.667,5.426-12.093,12.096-12.093h94.19v-8.062h-94.19    C9.042,22.365,0,31.402,0,42.52v73.993c0,11.118,9.042,20.148,20.164,20.148h94.738l2.156-8.062H20.164V128.612z"/> <path d="M153.995,23.007v8.431c4.287,1.863,7.289,6.123,7.289,11.082v73.993c0,6.234-4.769,11.319-10.827,11.965l2.101,7.855    c9.518-1.62,16.794-9.853,16.794-19.82V42.52C169.351,33.073,162.788,25.178,153.995,23.007z"/> <path d="M119.469,75.518c1.912-2.271,5.206-2.409,8.805,0.085c3.021-6.348,8.769-6.342,11.795-0.006    c3.599-2.494,6.887-2.356,8.805-0.079c1.163,1.382,1.626,3.339,1.419,5.976c0.231-0.023,0.371,0.037,0.591,0.024V16.23h-33.411    v65.3c0.219,0,0.353-0.055,0.597-0.037C117.837,78.864,118.312,76.9,119.469,75.518z"/> <path d="M128.579,119.083c-0.098-0.024-0.183-0.055-0.286-0.092c-2.211,1.596-4.232,2.083-5.895,1.767l-7.154,26.707l6.929-4.713    l3.075,7.404l7.143-26.652C130.905,122.851,129.62,121.396,128.579,119.083z"/> <path d="M140.056,118.991c-0.097,0.037-0.188,0.067-0.286,0.092c-1.205,2.697-2.752,4.189-4.555,4.622l7.886,29.416l2.667-10.084    l7.35,7.398l-7.922-29.538C143.673,120.916,141.926,120.349,140.056,118.991z"/> <path d="M137.846,116.86c0.938-0.183,1.827-0.487,2.722-0.792c4.865,4.092,7.514,2.558,6.406-3.702    c0.7-0.608,1.352-1.267,1.954-1.973c6.272,1.12,7.794-1.534,3.702-6.394c0.311-0.889,0.615-1.771,0.792-2.716    c5.882-2.168,5.882-5.188-0.013-7.355c-0.17-0.938-0.469-1.827-0.779-2.716c4.092-4.865,2.558-7.514-3.702-6.406    c-0.615-0.693-1.267-1.352-1.961-1.961c1.108-6.262-1.54-7.791-6.405-3.701c-0.889-0.305-1.766-0.604-2.716-0.78    c-2.168-5.897-5.188-5.897-7.355,0c-0.932,0.176-1.827,0.475-2.716,0.78c-4.865-4.089-7.515-2.561-6.394,3.701    c-0.707,0.621-1.364,1.268-1.974,1.967c-6.259-1.113-7.794,1.541-3.702,6.4c-0.304,0.895-0.608,1.777-0.779,2.716    c-5.894,2.167-5.894,5.2,0,7.355c0.177,0.938,0.476,1.827,0.779,2.722c-4.092,4.865-2.557,7.514,3.702,6.4    c0.615,0.706,1.267,1.352,1.961,1.96c-1.108,6.267,1.541,7.794,6.406,3.702c0.889,0.305,1.777,0.609,2.728,0.792    C132.658,122.748,135.691,122.748,137.846,116.86z"/> </g> </g> </svg>';
        $html .= '</div>';

        $html .= '<div class="col-auto">';
        $html .= "<p>You've earned a certificate!<p>";
        $html .= '</div>';

        $html .= '<div class="col-auto">';
        $html .= '<a href="' . $ld_certificate . '"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-download" viewBox="0 0 16 16"> <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/> <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/> </svg>Download Certificate<a>';
        $html .= '</div>';

        $html .= '</div>';

        $html .= '</div>';
    }

    return $html;
}

add_shortcode('_ld_certificate', '_ld_certificate');
