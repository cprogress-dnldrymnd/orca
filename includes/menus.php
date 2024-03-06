<?php
/*-----------------------------------------------------------------------------------*/
/* Register main menu for Wordpress use
/*-----------------------------------------------------------------------------------*/
function menu_locations()
{

    register_nav_menus(
        array(

            'header-menu'    =>    __('Header Menu'),
            'footer-main-menu'    =>    __('Footer Main Menu'),
            'footer-menu'    =>    __('Footer Menu'),
        )

    );
}

add_action('init', 'menu_locations');
