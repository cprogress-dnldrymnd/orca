<?php
function breadcrumbs()
{
    $html = ' <section class="breadcrumbs-section mt-4">';
    $html .= '<div class="container">';
    $html .= '<div class="breadcrumbs-holder">';
    $html .= '<ul class="mb-0">';
    $html .= '<li><a href="' . get_site_url() . '">Home</a></li>';

    if (is_post_type_archive()) {
        $title = get_the_archive_title();
        $html .= '<li><span>' . $title . '</span></li>';
    }

    if (is_single()) {
        $title = get_the_title();
        $post_type_obj = get_post_type_object(get_post_type());
        $post_type = $post_type_obj->labels->name; //Ice Creams.
        $html .= '<li><a href="' . get_post_type_archive_link(get_post_type())  . '">' . $post_type . '</a></li>';
        $html .= '<li><span>' . $title . '</span></li>';
    }

    $html .= '</ul>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="container large-container my-5">';
    $html .= do_shortcode("[_heading class='page-title' tag='h1' heading='$title']");
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


function _image($atts)
{
    extract(
        shortcode_atts(
            array(
                'id' => '',
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
