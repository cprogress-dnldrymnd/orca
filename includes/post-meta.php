<?php

use Carbon_Fields\Container;
use Carbon_Fields\Complex_Container;
use Carbon_Fields\Field;


/*-----------------------------------------------------------------------------------*/
/* Courses
/*-----------------------------------------------------------------------------------*/

Container::make('post_meta', 'Course settings')
    ->where('post_type', '=', 'sfwd-courses')
    ->set_priority('high')

    ->add_fields(
        array(
            Field::make('text', 'text2', __('text')),

        )
    );

Container::make('post_meta', 'Outcomes')
    ->where('post_type', '=', 'sfwd-courses')
    ->set_priority('high')

    ->add_fields(
        array(
            Field::make('text', 'text2', __('text')),

        )
    );
