<?php get_header() ?>
<?php while (have_posts()) { ?>
    <?php the_post() ?>
    <section class="single-courses pt-3 background-light-gray">
        <div class="container large-container">
            <div class="learndash-single-holder learndash-single-status-top">
                <div class="row">
                    <div class="col-md-8">
                        <?= do_shortcode('[_learndash_course_progress]') ?>
                    </div>
                    <div class="col-md-4">
                        <?= do_shortcode('[_learndash_status]') ?>
                    </div>
                </div>
            </div>

            <div class="learndash-single-holder learndash-single-navigation">

            </div>

            <div class="learndash-single-holder learndash-single-content" id="about">
                <?php the_content() ?>
            </div>

            <div class="learndash-single-holder learndash-single-course-outcomes" id="outcomes">
                <?= do_shortcode('[_course_outcomes]') ?>
            </div>

            <div class="learndash-single-holder learndash-single-course-outcomes" id="outcomes">
                <?= do_shortcode('[_course_highlight]') ?>
            </div>

            <div class="learndash-single-holder learndash-single-module" id="module">
                <?= do_shortcode('[course_content]') ?>
            </div>



        </div>
    </section>
<?php } ?>

<?php get_footer() ?>