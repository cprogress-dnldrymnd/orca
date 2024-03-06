<?php get_header() ?>


<section class="archive-courses archive-grid background-light-gray">
    <div class="container large-container">
        <div class="row">
            <?php while (have_posts()) { ?>
                <?php the_post() ?>
                <div class="col-lg-4">
                    <div class="column-holder background-white">
                        <?= do_shortcode('[_learndash_image id="' . get_post_thumbnail_id() . '" size="medium" learndash_status_bubble="true" taxonomy="ld_course_category"]') ?>
                        <div class="content-holder">
                            <?= do_shortcode('[_heading tag="h3" heading="' . get_the_title() . '"]'); ?>
                            <?= do_shortcode('[_description description="If you want to get onboard and become one of our volunteer Marine Mammal Surveyors..."]'); ?>
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