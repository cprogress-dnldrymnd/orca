<?php get_header() ?>
<section class="archive-courses archive-grid background-light-gray py-5">
    <div class="container large-container">
        <?php the_content() ?>
        <pre>
         <?php var_dump(unserialize(get_post_meta(181)['_sfwd-courses'][0])['sfwd-courses_course_price']) ?>
        </pre>
    </div>
</section>
<?php get_footer() ?>