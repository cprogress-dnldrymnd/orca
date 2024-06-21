<?php get_header() ?>

<?php
$args = array(
    'numberposts' => -1,
    'post_type'   => 'testimonials'
);

$testimonials = get_posts($args);

$groups = learndash_get_course_groups(get_the_ID());
if ($groups && !current_user_can('administrator')) {
    $group_users = learndash_get_course_groups_users_access(get_the_ID());
    if (!in_array(get_current_user_id(), $group_users)) {
        wp_redirect(get_site_url() . '/courses');
        exit;
    }
}
?>

<?php while (have_posts()) { ?>
    <?php the_post() ?>

    <?php
    _course_access();
    $completed = learndash_course_completed(get_current_user_id(), get_the_ID());
    if ($completed) {
        $users_completed_the_course = carbon_get_the_post_meta('users_completed_the_course');
        $users_completed_the_course_arr[] = $users_completed_the_course;
        $new_user = array(
            'value' => 'user:user:11',
            'type' => 'user',
            'subtype' => 'user',
            'id' => '11',
        );

        $users_completed_the_course_arr[] = $new_user;
        echo '<pre>';
        var_dump($users_completed_the_course_arr);
        echo '</pre>';

        carbon_set_post_meta(get_the_ID(), 'users_completed_the_course', $users_completed_the_course_arr);
    }
    ?>


    <section class="single-course-section pt-4 background-light-gray">
        <div class="container large-container">
            <div class="woo-notices">
                <?php wc_print_notices()  ?>
            </div>

            <div class="learndash-single-banner">
                <?= do_shortcode('[_course_banner]') ?>
            </div>
            <div class="single-course-content-holder background-white pt-4">

                <?= do_shortcode('[_ld_certificate]') ?>

                <div class="learndash-single-holder learndash-single-status-top" id="course-progress">
                    <div class="inner background-light-gray">
                        <div class="row gy-3 align-items-center">
                            <?= do_shortcode('[_learndash_course_progress wrapper="col-md-8"]') ?>
                            <div class="text-end <?= _user_has_access(get_the_ID()) ? 'col-md-4' : 'col-12' ?>">
                                <?= do_shortcode('[_learndash_status id="' . get_the_ID() . '"]') ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="learndash-single-holder learndash-single-navigation position-sticky background-white">
                    <ul class="d-flex list-inline">
                        <li><a href="#about" class="active">About</a></li>
                        <li><a href="#outcomes">Outcomes</a></li>
                        <li><a href="#modules">Modules</a></li>
                        <?php if ($testimonials) { ?>
                            <li><a href="#testimonials">Testimonials</a></li>
                        <?php } ?>
                    </ul>
                </div>
                <div class="learndash-single-holder learndash-single-content position-relative">
                    <div class="anchor-link" id="about"></div>
                    <?= do_shortcode(get_the_content()) ?>
                </div>

                <div class="learndash-single-holder learndash-single-cta" id="cta">
                    <?= do_shortcode('[_course_cta]') ?>
                </div>
                <div class="learndash-single-holder learndash-single-course-outcomes position-relative">
                    <div class="anchor-link" id="outcomes"></div>
                    <?= do_shortcode('[_course_outcomes]') ?>
                </div>

                <div class="learndash-single-holder learndash-single-course-highlight background-accent" id="highlight">
                    <div class="anchor-link" id="highlight"></div>
                    <?= do_shortcode('[_course_highlight]') ?>
                </div>

                <div class="learndash-single-holder learndash-single-course-breakdown">
                    <div class="anchor-link" id="course-breakdown"></div>
                    <?= do_shortcode('[_course_breakdown]') ?>
                </div>
                <div class="learndash-single-holder learndash-single-module position-relative">
                    <div class="anchor-link" id="modules"></div>
                    <?= do_shortcode('[course_content]') ?>
                </div>
                <div class="learndash-single-holder learndash-single-testimonial position-relative">
                    <div class="anchor-link" id="testimonials"></div>
                    <?= do_shortcode('[_course_testimonial]') ?>
                </div>

            </div>
        </div>
    </section>
<?php } ?>

<?php get_footer() ?>