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
<div class="footer-decoration text-end position-relative">
    <div class="lines">
        <div></div>
        <div></div>
        <div></div>
        <div></div>
        <div></div>
    </div>
    <div class="container">
        <img src="/wp-content/uploads/2024/02/whale-spotters.6b099e7c005173a154c9.png">
    </div>
</div>
<footer id="site-footer" class="site-footer background-primary py-3">

    <div class="container">
        <div class="row gy-3">
            <div class="col-12 color-white">
                <?= do_shortcode('[breadcrumbs hide_title="true"]') ?>
            </div>
            <div class="col-12">
                <hr>
            </div>
            <div class="col-12">
                <div class="row gy-3">
                    <?php if (is_active_sidebar('footer_left')) { ?>
                        <div class="col-lg-4">
                            <div class="footer-left-holder footer-widget">
                                <?php dynamic_sidebar('footer_left') ?>
                            </div>
                        </div>
                        <div class="col-12 d-block d-lg-none">
                            <hr>
                        </div>
                    <?php } ?>
                    <div class="col-lg-8">
                        <div class="row gy-3">
                            <?php if (is_active_sidebar('footer_column_1')) { ?>
                                <div class="col-md-4">
                                    <div class="footer-right-holder footer-widget">
                                        <?php dynamic_sidebar('footer_column_1') ?>
                                    </div>
                                </div>
                                <div class="col-12 d-block d-lg-none">
                                    <hr>
                                </div>
                            <?php } ?>
                            <?php if (is_active_sidebar('footer_column_2')) { ?>
                                <div class="col-md-4">
                                    <div class="footer-right-holder footer-widget">
                                        <?php dynamic_sidebar('footer_column_2') ?>
                                    </div>
                                </div>
                                <div class="col-12 d-block d-lg-none">
                                    <hr>
                                </div>
                            <?php } ?>
                            <?php if (is_active_sidebar('footer_column_3')) { ?>
                                <div class="col-md-4">
                                    <div class="footer-right-holder footer-widget">
                                        <?php dynamic_sidebar('footer_column_3') ?>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <hr>
                                </div>
                            <?php } ?>

                            <div class="col-12">
                                <?php
                                wp_nav_menu(array(
                                    'theme_location' => 'footer-menu',
                                    'container' => false,
                                    'menu_class' => '',
                                    'fallback_cb' => '__return_false',
                                    'items_wrap' => '<ul id="%1$s" class="menu" >%3$s</ul>',
                                    'depth' => 2,
                                ));
                                ?>


                            </div>

                            <div class="col-12">
                                <hr>
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