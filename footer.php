<?php

/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package orca
 */

?>

<footer id="site-footer" class="site-footer background-primary">
    <div class="footer-decoration">
        <div></div>
        <div></div>
        <div></div>
        <div></div>
    </div>
    <div class="container">
        <div class="row">
            <div class="col-12">
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'footer-main-menu',
                    'container' => false,
                    'menu_class' => '',
                    'fallback_cb' => '__return_false',
                    'items_wrap' => '<ul id="%1$s" class="menu" >%3$s</ul>',
                    'depth' => 2,
                ));
                ?>
                <hr>
            </div>
            <div class="col-12">
                <div class="row">
                    <?php if (get_sidebar('footer_left')) { ?>
                        <div class="col-lg-4">
                            <div class="footer-left-holder">
                                <?php dynamic_sidebar('footer_left') ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</footer>
</div><!-- #page -->

<?php wp_footer(); ?>

</body>

</html>