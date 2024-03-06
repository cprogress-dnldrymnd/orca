<?php get_header() ?>

<?php
global $wp_query;
var_dump($wp_query);
?>
<section class="archive-courses">
    <div class="container">
        <div class="row">
            <?php while (have_post()) { ?>
                <?php the_post() ?>
                <div class="col-lg-4">
                    <div class="column-holder">
                        <?= do_shortcode('[_image id="' . get_post_thumbnail_id() . '" ]') ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</section>

<?php get_footer() ?>