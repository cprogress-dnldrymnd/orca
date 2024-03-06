<?php get_header() ?>


<section class="archive-courses">
    <div class="container">
        <div class="row">
            <?php while (have_posts()) { ?>
                <?php the_post() ?>
                <div class="col-lg-4">
                    <div class="column-holder">
                        <?php
                        echo do_shortcode('[_image id="' . get_post_thumbnail_id() . ' size="medium" ]');
                        echo do_shortcode('[_heading heading="' . get_the_title() . '" ]');
                        ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</section>

<?php get_footer() ?>