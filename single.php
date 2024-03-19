<?php get_header() ?>
<section class="archive-courses archive-grid background-light-gray py-5">
    <div class="container large-container">
        <?php the_content() ?>
        <?php

        $price = learndash_get_course_price(846)['price'];
        echo $price;
        846

        ?>
        <pre>
            <?php var_dump(get_post_meta(846)) ?>
        </pre>
    </div>
</section>
<?php get_footer() ?>