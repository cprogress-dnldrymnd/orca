<?php
function breadcrumbs($atts)
{
    extract(
        shortcode_atts(
            array(
                'hide_title' => 'false',
            ),
            $atts
        )
    );
    $html = ' <section class="breadcrumbs-section mt-4">';
    $html .= '<div class="container">';
    $html .= '<div class="breadcrumbs-holder">';
    $html .= '<ul class="mb-0">';
    $html .= '<li><a href="' . get_site_url() . '">Home</a></li>';

    if (is_post_type_archive()) {
        $title = get_the_archive_title();
        $html .= '<li><span>' . $title . '</span></li>';
    } else if (is_tax()) {
        $title = get_queried_object()->name;
        if (is_tax('ld_course_category')) {
            $html .= '<li><a href="' . get_post_type_archive_link('sfwd-courses')  . '">Courses</a></li>';
        }
        $html .= '<li><span>' . $title . '</span></li>';
    } else if (is_single() || is_page()) {
        $title = get_the_title();
        $post_type_obj = get_post_type_object(get_post_type());
        $post_type = $post_type_obj->labels->name; //Ice Creams.
        if (!is_page()) {
            $html .= '<li><a href="' . get_post_type_archive_link(get_post_type())  . '">' . $post_type . '</a></li>';
        }
        $html .= '<li><span>' . $title . '</span></li>';
    }

    $html .= '</ul>';
    $html .= '</div>';
    $html .= '</div>';
    if ($hide_title != 'true' && !is_product()) {
        $html .= '<div class="container large-container my-5">';
        $html .= '<div class="row align-items-center">';
        $html .= '<div class="col">';
        $html .= do_shortcode("[_heading class='page-title' tag='h1' heading='$title']");
        $html .= '</div>';

        if (is_single() && get_post_type() == 'sfwd-courses') {
            $html .= do_shortcode('[_course_group]');
        }

        if (is_post_type_archive('sfwd-courses')) {
            $html .= do_shortcode('[_course_group_archive]');
        }
        $html .= '</div>';
        $html .= '</div>';
    }
    $html .= '</section>';
    return $html;
}

add_shortcode('breadcrumbs', 'breadcrumbs');

function post_id()
{
    return get_the_ID();
}

add_shortcode('post_id', 'post_id');


function _image($atts)
{
    extract(
        shortcode_atts(
            array(
                'id' => get_post_thumbnail_id(),
                'size' => 'large',
                'class' => ''
            ),
            $atts
        )
    );
    $image_url = wp_get_attachment_image_url($id, $size);
    $html = "<div class='image-box $class'>";

    if ($image_url) {
        $html .= '<img src="' . $image_url . '" >';
    } else {
        $html .= '<img src="' . image_dir . 'placeholder.jpg" >';
    }
    $html .= '</div>';

    return $html;
}
add_shortcode('_image', '_image');



function _heading($atts)
{
    extract(
        shortcode_atts(
            array(
                'tag' => 'h2',
                'heading' => '',
                'class' => ''
            ),
            $atts
        )
    );

    $html = "<div class='heading-box $class'><$tag>$heading</$tag>";

    if (get_post_type() == 'sfwd-courses') {
        $enrolled = ld_course_access_from(get_the_ID(),  get_current_user_id());
        $expires = ld_course_access_expires_on(get_the_ID(),  get_current_user_id());
        $compare = learndash_get_course_prerequisite_compare(get_the_ID());
        $prerequisites = learndash_get_course_prerequisite(get_the_ID());
        $prerequisite_enabled =  learndash_get_course_prerequisite_enabled(get_the_ID());
        $bundles = _learndash_included_in_bundle(get_the_ID());

        if ($enrolled && is_single()) {

            $html .= '<div class="learndash-course-access">';
            $html .= '<strong>Enrolled Date:</strong> ' . date('F j, Y g:i A', $enrolled);
            $html .= '&nbsp;|&nbsp;';
            $html .= '<strong>Expires:</strong> ' . date('F j, Y g:i A', $expires);
            $html .= '</div>';
        }

        if ($prerequisite_enabled && !$enrolled && is_single()) {
            $html .= '<div class="learndash-course-prerequisites">';
            $html .= '<strong>This course requires ' . strtolower($compare) . ' of the following course to be completed in order to purchase. </strong>';

            $html .= '<ul>';
            foreach ($prerequisites as $key => $prerequisite) {
                $html .= '<li><a href="' . get_the_permalink($prerequisite) . '">' . get_the_title($prerequisite) . '</a></li>';
            }
            $html .= '</ul>';
            $html .= '</div>';
        }

        if ($bundles) {
            $html .= '<div class="learndash-course-prerequisites">';
            $html .= '<strong> ' . get_the_title() . ' </strong> is included the following bundles.';
            $html .= '<ul>';

            foreach ($bundles as $bundle) {
                $html .= '<li><a href="' . get_the_permalink($bundle) . '">' . get_the_title($bundle) . '</a></li>';
            }
            $html .= '</ul>';

            $html .= '</div>';
        }
    }
    $html .= "</div>";


    return $html;
}
add_shortcode('_heading', '_heading');


function _description($atts)
{
    extract(
        shortcode_atts(
            array(
                'description' => ''
            ),
            $atts
        )
    );

    return "<div class='description-box'> " . wpautop($description) . " </div>";
}
add_shortcode('_description', '_description');

function _button($atts)
{
    extract(
        shortcode_atts(
            array(
                'class' => '',
                'button_text' => '',
                'button_link' => '',
                'attribute' => '',
            ),
            $atts
        )
    );

    return "<div class='button-box'> <a $attribute href='$button_link' class='btn $class'>$button_text</a> </div>";
}
add_shortcode('_button', '_button');

function _post_taxonomy_terms($atts)
{
    extract(
        shortcode_atts(
            array(
                'taxonomy' => '',
                'post_id' => get_the_ID()
            ),
            $atts
        )
    );

    if ($taxonomy) {
        $terms = get_the_terms($post_id, $taxonomy);
        $html = "<div class='taxonomy-terms d-flex'>";

        foreach ($terms as $term) {
            $html .= '<a href="' . get_term_link($term->term_id) . '">';
            $html .= $term->name;
            $html .= '</a>';
        }

        $html .= "</div>";

        return $html;
    }
}
add_shortcode('_post_taxonomy_terms', '_post_taxonomy_terms');
