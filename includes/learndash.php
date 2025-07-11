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
    $product_id = _learndash_has_linked_product($id, true)[0];
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

function _learndash_has_linked_product($course_id, $exclude_bundles = false)
{

    $args = array();
    $args['fields'] = 'ids';
    $args['post_type'] = 'product';
    $args['meta_query'] = array(
        array(
            'key'   => '_related_course',
            'value' => serialize(intval($course_id)),
            'compare' => 'LIKE'
        )
    );

    if ($exclude_bundles == true) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => array('bundles'),
                'operator' => 'NOT IN'
            )
        );
    }

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
        if (has_term(array('bundles'), 'product_cat', $id)) {
            $html .= '<div class="ld-status ld-status-complete ld-secondary-background">Bundle</div>';
        } else if (has_term(array('online-courses'), 'product_cat', $id)) {
            $html .= '<div class="ld-status ld-status-complete ld-secondary-background">Live, Online course</div>';
        } else if (has_term(array('wps_wgm_giftcard'), 'product_cat', $id)) {
            $html .= '<div class="ld-status ld-status-complete ld-secondary-background">Gift Card</div>';
        }

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
            $html .= '<div class="col-lg-6">';
            $html .= "<a  href='$permalink' class='btn btn-black w-100'>View Course</a>";
            $html .= '</div>';
            $html .= '<div class="col-lg-6">';
            $html .= do_shortcode('[_learndash_linked_product id="' . $id . '" hide_bubble="true"]');
            $html .= '</div>';
        } else if (_user_has_access($id) == true && _can_be_purchased($id)) {
            $html .= '<div class="col-lg-6">';
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

        if (has_term(array('bundles'), 'product_cat', $id)) {
            $button_text = 'View Bundle';
        } else if (has_term(array('online-courses'), 'product_cat', $id)) {
            $button_text = 'View Course';
        } else if (has_term(array('wps_wgm_giftcard'), 'product_cat', $id)) {
            $button_text = 'View Gift Card';
        } else {
            $button_text = 'View Course';
        }

        $html .= '<div class="col-12">';
        $html .= "<a  href='$permalink' class='btn btn-black w-100'>$button_text</a>";
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

    $products = _learndash_has_linked_product($id, true);

    $html = '';


    if ($hide_bubble == 'false') {
        $html .= '<span class="ld-status ld-status-waiting ld-tertiary-background" data-ld-tooltip="Enroll in this course to get access" data-ld-tooltip-id="52073"> Not Enrolled</span>';
    }

    if (_user_has_access($id) == false && _can_be_purchased($id)) {
        if ($hide_add_to_cart == 'false') {

            if ($products) {
                if (count($products) == 1) {
                    $html .= _add_to_cart_button($products[0]);
                    //$html .= var_dump($products);
                } /*else if (count($products) > 1) {
                    $html .= '<a class="button add_to_cart_button" href="' . get_permalink(wc_get_page_id('shop')) . '?id=' . $id . '" >  Add to cart </a>';
                }*/
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


function _ld_certificate($atts)
{
    extract(
        shortcode_atts(
            array(
                'id' => get_the_ID(),
                'label' => "You've earned a certificate!",
                'featured_image' => false
            ),
            $atts
        )
    );
    $ld_certificate = learndash_get_course_certificate_link($id, get_current_user_id());
    $html = '';
    $html .= '<div class="certificate-box">';
    $html .= '<div class="certificate-holder">';

    $html .= '<div class="row align-items-center g-4">';

    $html .= '<div class="col-auto col-lg-2 col-image">';
    if ($featured_image) {
        $html .= do_shortcode('[_image size="medium" id="' . get_post_thumbnail_id($id) . '"]');
    } else {
        $html .= '<span class="ld-icon ld-icon-certificate"></span>';
    }
    $html .= '</div>';

    $html .= '<div class="col-auto col-lg-5 col-label">';
    $html .= "<p>$label<p>";
    $html .= '</div>';


    $html .= '<div class="col-lg-5 text-end col-button">';
    $html .= '<a class="d-inline-flex justify-content-center" target="_blank" href="' . $ld_certificate . '"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-download" viewBox="0 0 16 16"> <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/> <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/> </svg><span>Download Certificate</span></a>';
    $html .= '</div>';

    $html .= '</div>';

    $html .= '</div>';
    $html .= '</div>';
    return $html;
}

add_shortcode('_ld_certificate', '_ld_certificate');

function _course_access()
{

    $user_id = get_current_user_id();
    $course_id = get_the_ID();
    $completed = learndash_course_completed($user_id, $course_id);
    if ($completed) {
        $users_completed_the_course = carbon_get_the_post_meta('users_completed_the_course');

        $existed = array_search($user_id, array_column($users_completed_the_course, 'id'));

        if ($existed) {
            echo 'existed';
        } else {
            echo 'not existed';
        }

        $new_user = array(
            'value' => 'user:user:' . $user_id,
            'type' => 'user',
            'subtype' => 'user',
            'id' => $user_id,
        );

        $users_completed_the_course[] = $new_user;
        echo '<pre>';
        var_dump($users_completed_the_course);
        echo '</pre>';

        //carbon_set_post_meta(get_the_ID(), 'users_completed_the_course', $users_completed_the_course);
    }
}