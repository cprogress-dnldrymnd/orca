<?php get_header() ?>


<section class="archive-courses archive-grid background-light-gray">
    <div class="container large-container">
        <div class="row">
            <?php while (have_posts()) { ?>
                <?php the_post() ?>
                <div class="col-lg-4">
                    <div class="column-holder">
                        <?= do_shortcode('[_learndash_image id="' . get_post_thumbnail_id() . '" size="medium" learndash_status_bubble="true" taxonomy="ld_course_category"]') ?>
                        <div class="content-holder">
                            <?= do_shortcode('[_heading heading="' . get_the_title() . '"]'); ?>
                            <?= do_shortcode('[_description description="' . get_the_excerpt() . '"]'); ?>
                            <hr>
                            <?= do_shortcode('[_learndash_course_meta]'); ?>
                            <?= do_shortcode('[_learndash_course_button]'); ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</section>

<?php get_footer() ?>