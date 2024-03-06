<?php
function breadcrumbs()
{
    $html = ' <section class="breadcrumbs-section py-3">';
    $html .= '<div class="container">';
    $html .= '<div class="breadcrumbs-holder">';
    $html .= '<ul class="mb-0">';
    $html .= '<li><a href="' . get_site_url() . '">Home</a></li>';

    if (is_post_type_archive()) {
        $html .= '<li><span>' . get_the_archive_title() . '</span></li>';
    }

    if (is_single()) {
        $post_type_obj = get_post_type_object(get_post_type());
        $post_type = $post_type_obj->labels->name; //Ice Creams.
        $html .= '<li><a href="' . get_post_type_archive_link(get_post_type())  . '">' . $post_type . '</a></li>';
        $html .= '<li><span>' . get_the_title() . '</span></li>';
    }

    $html .= '</ul>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</section>';
    return $html;
}

add_shortcode('breadcrumbs', 'breadcrumbs');

function post_id()
{
    return get_the_ID();
}

add_shortcode('post_id', 'post_id');


function _course_cta()
{
    $cta_heading = get__post_meta('cta_heading');
    $cta_description = get__post_meta('cta_description');

    if ($cta_heading || $cta_description) {
        $html = '<div class="course-cta>"';
        $html .= '</div>';
        return $html;
    }
}
add_shortcode('_course_cta', '_course_cta');

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

    if ($highlight_heading || $highlight_description) {
        return do_shortcode('[elementor-template id="642"]');
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


function _image($atts)
{
    extract(
        shortcode_atts(
            array(
                'id' => '',
                'size' => 'large',
            ),
            $atts
        )
    );
    $image_url = wp_get_attachment_image_url($id, $size);
    $html = '<div class="image-box">';

    if ($image_url) {
        $html .= '<img src="' . $image_url . '" >';
    } else {
        $html .= '<img src="/wp-content/plugins/elementor/assets/images/placeholder.png" >';
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

    return "<div class='heading-box $class'><$tag>$heading</$tag></div>";
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
