<?php
function breadcrumbs()
{
    $html = '<div class="breadcrumbs-holder">';
    $html .= '<ul>';
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
    return $html;
}

add_shortcode('breadcrumbs', 'breadcrumbs');

function post_id() {
	return get_the_ID();
}

add_shortcode('post_id', 'post_id');


function _course_cta() {
    $cta_heading = get__post_meta('cta_heading');
    $cta_description = get__post_meta('cta_description');

    if($cta_heading || $cta_description) {
        return do_shortcode('[elementor-template id="632"]');
    }
}

add_shortcode('_course_cta', '_course_cta');