<?php get_header() ?>
<?php while (have_posts()) { ?>
    <?php the_post() ?>
    <section class="single-course-section pt-3 background-light-gray">
        <div class="container large-container">
            <div class="learndash-single-banner">
                <?= do_shortcode('[_course_banner]') ?>
            </div>
            <div class="single-course-content-holder background-white pt-4">
                <div class="learndash-single-holder learndash-single-status-top">
                    <div class="inner background-light-gray">
                        <div class="row">
                            <div class="col-md-8">
                                <?= do_shortcode('[_learndash_course_progress]') ?>
                            </div>
                            <div class="col-md-4 text-end">
                                <?= do_shortcode('[_learndash_status]') ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="learndash-single-holder learndash-single-navigation">
                    <ul></ul>
                </div>

                <div class="learndash-single-holder learndash-single-content" id="about">
                    <?php the_content() ?>
                </div>

                <div class="learndash-single-holder learndash-single-cta" id="cta">
                    <?= do_shortcode('[_course_cta]') ?>
                </div>

                <div class="learndash-single-holder learndash-single-course-outcomes" id="outcomes">
                    <?= do_shortcode('[_course_outcomes]') ?>
                </div>

                <div class="learndash-single-holder learndash-single-course-highlight background-accent" id="highlight">
                    <?= do_shortcode('[_course_highlight]') ?>
                </div>

                <div class="learndash-single-holder learndash-single-course-breakdown" id="course-breakdown">
                    <?= do_shortcode('[_course_breakdown]') ?>
                </div>

                <div class="learndash-single-holder learndash-single-module" id="module">
                    <?= do_shortcode('[course_content]') ?>
                </div>
            </div>
        </div>
    </section>
<?php } ?>

<?php get_footer() ?>