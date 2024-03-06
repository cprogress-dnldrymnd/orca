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
    );
