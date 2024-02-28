<?php
function breadcrumbs() {
    $html = '<div class="breadcrumbs-holder">';
    $html .= '</div>';
    return $html;
}

add_shortcode('breadcrumbs', 'breadcrumbs');