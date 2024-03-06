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
        'CTA',
        array(
            Field::make('text', 'cta_heading', __('CTA Heading')),
            Field::make('rich_text', 'cta_description', __('CTA Description')),
            Field::make('text', 'cta_button_text', __('CTA Button Text')),
            Field::make('text', 'cta_button_link', __('CTA Button Link')),
            Field::make('image', 'background_image', __('CTA Background Image')),

        )
    )
    ->add_tab(
        'Outcomes',
        array(
            Field::make('rich_text', 'outcomes', __('Outcomes')),
        )
    )
    ->add_tab(
        'Course Breakdown',
        array(
            Field::make('rich_text', 'course_breakdown', __('Course Breakdown')),
        )
    );
