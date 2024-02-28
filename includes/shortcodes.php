<?php
function breadcrumbs()
{
    $html = '<div class="breadcrumbs-holder">';
    $html .= '<ul>';
    $html .= '<li><a href="' . get_site_url() . '">Home</a></li>';

    if (is_post_type_archive()) {
        $html .= '<li><a href="' . get_site_url() . '">'.get_the_archive_title().'</a></li>';
    }

    $html .= '</ul>';
    $html .= '</div>';
    return $html;
}

add_shortcode('breadcrumbs', 'breadcrumbs');