<?php get_header() ?>

<section class="archive-courses archive-grid background-light-gray py-5">
    <div class="container large-container">
        <?php if (have_posts()) { ?>
            <div class="row">
                <?php while (have_posts()) { ?>
                    <?php the_post() ?>
                    <div class="col-md-4 col-6">
                        <div class="column-holder d-flex flex-column justify-content-between background-white h-100">
                            <?= do_shortcode('[_learndash_image id="' . get_post_thumbnail_id() . '" size="medium" learndash_status_bubble="true" taxonomy="ld_course_category"]') ?>
                            <div class="content-holder d-flex flex-column justify-content-between">
                                <div>
                                    <?= do_shortcode('[_heading class="color-primary" tag="h3" heading="' . get_the_title() . '"]'); ?>
                                    <?= do_shortcode('[_description description="If you want to get onboard and become one of our volunteer Marine Mammal Surveyors..."]'); ?>
                                    <hr>
                                    <?= do_shortcode('[_learndash_course_meta]'); ?>
                                </div>
                                <div>
                                    <?= do_shortcode('[_learndash_course_button]'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } else { ?>
            <div class="heading-box">
                <h2>
                    No results found.
                </h2>
            </div>
        <?php } ?>
    </div>
</section>

<?php get_footer() ?>