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
<div class="footer-decoration">
    <div></div>
    <div></div>
    <div></div>
    <div></div>
</div>
<footer id="site-footer" class="site-footer background-primary">

    <div class="container">
        <div class="row">
            <div class="col-12">
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'footer-main-menu',
                    'container' => false,
                    'menu_class' => '',
                    'fallback_cb' => '__return_false',
                    'items_wrap' => '<ul id="%1$s" class="menu footer-main-menu" >%3$s</ul>',
                    'depth' => 2,
                ));
                ?>
                <hr>
            </div>
            <div class="col-12">
                <div class="row">
                    <?php if (is_active_sidebar('footer_left')) { ?>
                        <div class="col-lg-4">
                            <div class="footer-left-holder">
                                <?php dynamic_sidebar('footer_left') ?>
                            </div>
                        </div>
                    <?php } ?>
                    <div class="col-lg-8">
                        <div class="row">
                            <?php if (is_active_sidebar('footer_column_1')) { ?>
                                <div class="col-lg-4">
                                    <div class="footer-right-holder">
                                        <?php dynamic_sidebar('footer_column_1') ?>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php if (is_active_sidebar('footer_column_2')) { ?>
                                <div class="col-lg-4">
                                    <div class="footer-right-holder">
                                        <?php dynamic_sidebar('footer_column_3') ?>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php if (is_active_sidebar('footer_column_3')) { ?>
                                <div class="col-lg-4">
                                    <div class="footer-right-holder">
                                        <?php dynamic_sidebar('footer_column_3') ?>
                                    </div>
                                </div>
                            <?php } ?>
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
                            </div>
                            <?php if (is_active_sidebar('footer_bottom')) { ?>
                                <div class="col-12">
                                    <div class="footer-bottom-holder">
                                        <?php dynamic_sidebar('footer_bottom') ?>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</footer>
</div><!-- #page -->

<?php wp_footer(); ?>

</body>

</html>