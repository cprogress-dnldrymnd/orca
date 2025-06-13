<?php

use Carbon_Fields\Container;
use Carbon_Fields\Complex_Container;
use Carbon_Fields\Field;


/*-----------------------------------------------------------------------------------*/
/* Courses
/*-----------------------------------------------------------------------------------*/

Container::make('post_meta', 'Course Settings')
    ->where('post_type', '=', 'sfwd-courses')
    ->set_priority('high')
    ->add_tab(
        'Banner',
        array(
            Field::make('text', 'banner_heading', __('Banner Heading')),
            Field::make('rich_text', 'banner_description', __('Banner Description')),
            Field::make('image', 'banner_background_image', __('Banner Background Image')),
        )
    )
    ->add_tab(
        'CTA',
        array(
            Field::make('text', 'cta_heading', __('CTA Heading')),
            Field::make('rich_text', 'cta_description', __('CTA Description')),
            Field::make('text', 'cta_button_text', __('CTA Button Text')),
            Field::make('text', 'cta_button_link', __('CTA Button Link')),
            Field::make('image', 'cta_background_image', __('CTA Background Image')),

        )
    )
    ->add_tab(
        'Outcomes',
        array(
            Field::make('text', 'outcomes_heading', __('Outcomes Heading')),
            Field::make('rich_text', 'outcomes', __('Outcomes Description')),
        )
    )
    ->add_tab(
        'Highlight Section',
        array(
            Field::make('text', 'highlight_heading', __('Highlight Heading')),
            Field::make('rich_text', 'highlight_description', __('Highlight Description')),
            Field::make('image', 'highlight_image', __('Highlight Image')),
        )
    )
    ->add_tab(
        'Course Breakdown',
        array(
            Field::make('rich_text', 'course_breakdown', __('Course Breakdown')),
        )
    )
    ->add_tab(
        'Certification',
        array(
            Field::make('text', 'certification', __('Certification')),
        )
    );

Container::make('post_meta', 'Course Settings')
    ->where('post_type', '=', 'product')
    ->add_fields(array(
        Field::make('text', 'ld_price_type', __('Price Type'))->set_default_value('paynow')
            ->set_attribute('readOnly', 'true'),
        Field::make('association', 'online_courses_included', __('Online Courses Included'))
            ->set_types(array(
                array(
                    'type'      => 'post',
                    'post_type' => 'product',
                )
            ))
    ));
Container::make('post_meta', 'Beacon Integration Settings')
    ->where('post_type', '=', 'product')
    ->add_fields(array(
        Field::make('text', 'beacon_id', __('Beacon ID')),
        Field::make('select', 'course_type', __('Course Type'))
            ->set_options(array(
                '' => 'Select Course Type',
                'MMS' => 'MMS',
                'OceanWatchers' => 'OceanWatchers',
                'Introduction' => 'Introduction',
                'Deep Dive' => 'Deep Dive',
            )),
    ));

add_filter('carbon_fields_association_field_options_online_courses_included_post_product', function ($query_arguments) {

    $tax_query[] = array(
        'taxonomy' => 'product_cat',
        'field'    => 'slug',
        'terms'    => array('online-courses')
    );

    $query_arguments['tax_query'] = $tax_query;
    $query_arguments['post_status'] = array('publish', 'private');

    return $query_arguments;
});




Container::make('post_meta', 'Email Settings')
    ->where('post_type', '=', 'coursecustomemails')
    ->add_fields(array(
        Field::make('association', 'products', __('Products'))
            ->set_types(array(
                array(
                    'type'      => 'post',
                    'post_type' => 'product',
                )
            ))
    ));



Container::make('term_meta', __('Category Properties'))
    ->where('term_taxonomy', '=', 'ld_course_category')
    ->add_fields(array(
        Field::make('color', 'tag_bg_color', __('Tag BG Color')),
        Field::make('color', 'tag_text_color', __('Tag Text Color')),
    ));
