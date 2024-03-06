<?php
function action_widgets_init()
{

    register_sidebar(
        array(
            'name'          => 'Footer Left',
            'id'            => 'footer_left',
            'before_widget' => '<div>',
            'after_widget'  => '</div>',
            'before_title'  => '<h5 class="widget-title">',
            'after_title'   => '</h5>',
        )
    );

    register_sidebar(
        array(
            'name'          => 'Footer Right Column 1',
            'id'            => 'footer_column_1',
            'before_widget' => '<div>',
            'after_widget'  => '</div>',
            'before_title'  => '<h5 class="widget-title">',
            'after_title'   => '</h5>',
        )
    );


    register_sidebar(
        array(
            'name'          => 'Footer Right Column 2',
            'id'            => 'footer_column_2',
            'before_widget' => '<div>',
            'after_widget'  => '</div>',
            'before_title'  => '<h5 class="widget-title">',
            'after_title'   => '</h5>',
        )
    );

    register_sidebar(
        array(
            'name'          => 'Footer Right Column 3',
            'id'            => 'footer_column_3',
            'before_widget' => '<div>',
            'after_widget'  => '</div>',
            'before_title'  => '<h5 class="widget-title">',
            'after_title'   => '</h5>',
        )
    );

    register_sidebar(
        array(
            'name'          => 'Footer Right Bottom',
            'id'            => 'footer_bottom',
            'before_widget' => '<div>',
            'after_widget'  => '</div>',
            'before_title'  => '<h5 class="widget-title">',
            'after_title'   => '</h5>',
        )
    );
}
add_action('widgets_init', 'action_widgets_init');
