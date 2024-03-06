<?php

use Carbon_Fields\Container;
use Carbon_Fields\Complex_Container;
use Carbon_Fields\Field;


/*-----------------------------------------------------------------------------------*/
/* Courses
/*-----------------------------------------------------------------------------------*/

Container::make('post_meta', 'CTA')
    ->where('post_type', '=', 'sfwd-courses')
    ->set_priority('high')

    ->add_tab('CTA',
        array(
            Field::make('text', 'cta_heading', __('CTA Heading')),
            Field::make('rich_text', 'cta_description', __('CTA Description')),
            Field::make('rich_text', 'cta_button_text', __('CTA Button Text')),
            Field::make('rich_text', 'cta_button_link', __('CTA Button Text')),

        )
    );

Container::make('post_meta', 'Outcomes')
    ->where('post_type', '=', 'sfwd-courses')
    ->set_priority('high')
    ->add_fields(
        array(
            Field::make('rich_text', 'outcomes', __('Outcomes')),

        )
    );
