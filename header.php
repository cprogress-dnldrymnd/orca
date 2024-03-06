<?php

/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 *
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">

    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
    <div id="page" class="site">

        <header id="masthead" class="site-header">
            <nav class="navbar navbar-expand-md navbar-light bg-light">
                <div class="container">
                    <div class="row">
                        <div class="col-auto">
                            <div class="navbar-brand">
                                <?= get_custom_logo() ?>
                            </div>
                        </div>
                        <div class="col">
                            <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasExample" aria-controls="offcanvasExample">
                                <span>
                                    <span class="menu-toggler-icon"></span>
                                    <span class="menu-toggler-text">Menu</span>
                                </span>
                            </button>

                            <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasHeaderMenu" aria-labelledby="offcanvasHeaderMenuLabel">
                                <div class="offcanvas-header">
                                    <h5 class="offcanvas-title" id="offcanvasHeaderMenuLabel">Offcanvas</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                                </div>
                                <div class="offcanvas-body">
                                    <div class="dropdown mt-3">
                                        <div class="collapse navbar-collapse" id="main-menu">
                                            <?php
                                            wp_nav_menu(array(
                                                'theme_location' => 'header-menu',
                                                'container' => false,
                                                'menu_class' => '',
                                                'fallback_cb' => '__return_false',
                                                'items_wrap' => '<ul id="%1$s" class="navbar-nav me-auto mb-2 mb-md-0 %2$s">%3$s</ul>',
                                                'depth' => 2,
                                            ));
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>
                </div>
            </nav>
        </header><!-- #masthead -->