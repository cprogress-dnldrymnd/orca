<?php get_header() ?>
<section class="archive-courses archive-grid background-light-gray py-5">
    <div class="container large-container">
        <?php the_content() ?>
        <?php
        echo learndash_get_course_price(181);
        ?>
    </div>
</section>
<?php get_footer() ?>