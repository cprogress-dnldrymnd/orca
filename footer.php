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

<footer id="site-footer" class="site-footer">
    <div class="container">
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
</footer>
</div><!-- #page -->

<?php wp_footer(); ?>

</body>

</html>