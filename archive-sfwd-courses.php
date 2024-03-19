<?php get_header() ?>
<?php
$terms = get_terms(array(
    'taxonomy'   => 'ld_course_category',
    'hide_empty' => false,
));
?>
<section class="archive-courses archive-grid background-light-gray py-2 py-5">
    <div class="container large-container">
        <div class="row g-4 filter mb-4 align-items-center">
            <div class="col-lg-6">
                <div class="showing">
                    Showing <?php echo $GLOBALS['wp_query']->found_posts ?> of <?php echo $GLOBALS['wp_query']->found_posts ?> Courses
                </div>
            </div>
            <div class="col-lg-6">
                <div class="filter-box d-flex">
                    <div class="filter-select">
                        <select name="ld_course_category" id="ld_course_category">
                            <?php foreach ($terms as $term) { ?>
                                <option value="<?= $term->term_id ?>"><?= $term->name ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="filter-button">
                        <button>Apply Filter</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-4">
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
    </div>
</section>

<?php get_footer() ?>