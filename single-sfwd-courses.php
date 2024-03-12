<?php get_header() ?>
<?php while (have_posts()) { ?>
    <?php the_post() ?>
    <section class="single-course-section pt-4 background-light-gray">
        <div class="container large-container">
            <div class="learndash-single-banner">
                <?= do_shortcode('[_course_banner]') ?>
            </div>
            <div class="single-course-content-holder background-white pt-4">
                <div class="learndash-single-holder learndash-single-status-top" id="course-progress">
                    <div class="inner background-light-gray">
                        <div class="row gy-3 align-items-center">
                            <div class="col-md-8">
                                <?= do_shortcode('[_learndash_course_progress]') ?>
                            </div>
                            <div class="col-md-4 text-end">
                                <?= do_shortcode('[_learndash_status]') ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="learndash-single-holder learndash-single-navigation position-sticky background-white">
                    <ul class="d-flex list-inline">
                        <li><a href="#about" class="active">About</a></li>
                        <li><a href="#outcomes">Outcomes</a></li>
                        <li><a href="#modules">Modules</a></li>
                        <li><a href="#testimonials">Testimonials</a></li>
                    </ul>
                </div>
                <div class="learndash-single-holder learndash-single-content position-relative">
                    <div class="anchor-link" id="about"></div>
                    <?php the_content() ?>
                </div>

                <div class="learndash-single-holder learndash-single-cta" id="cta">
                    <?= do_shortcode('[_course_cta]') ?>
                </div>
                <div class="learndash-single-holder learndash-single-course-outcomes position-relative">
                    <div class="anchor-link" id="outcomes"></div>
                    <?= do_shortcode('[_course_outcomes]') ?>
                </div>

                <div class="learndash-single-holder learndash-single-course-highlight background-accent" id="highlight">
                    <?= do_shortcode('[_course_highlight]') ?>
                </div>

                <div class="learndash-single-holder learndash-single-course-breakdown" id="course-breakdown">
                    <?= do_shortcode('[_course_breakdown]') ?>
                </div>
                <div class="learndash-single-holder learndash-single-module position-relative">
                    <div class="anchor-link" id="modules"></div>
                    <?= do_shortcode('[course_content]') ?>
                </div>
                <div class="learndash-single-holder learndash-single-module position-relative background-dark py-4 px-5">
                    <div class="anchor-link" id="modules"></div>
                    <?= do_shortcode('[_course_testimonial]') ?>
                </div>
            </div>
        </div>
    </section>
<?php } ?>

<?php get_footer() ?>