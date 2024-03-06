<?php get_header() ?>
<section class="single-courses pt-3 background-light-gray">
    <div class="container large-container">
        <div class="learndash-single-status-top">
            <div class="row">
                <div class="col-md-8">
                    <?= do_shortcode('[_learndash_course_progress]') ?>
                </div>
                <div class="col-md-4">
                    <?= do_shortcode('[_learndash_status]') ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php get_footer() ?>