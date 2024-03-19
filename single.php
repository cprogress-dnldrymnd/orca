<?php get_header() ?>
<section class="archive-courses archive-grid background-light-gray py-5">
    <div class="container large-container">
        <?php the_content() ?>
        <?php
        $product = new WC_Product_Course(498);
        $product->set_price(20);
        $product->save();

        var_dump(learndash_get_course_price(834)['price']);
        ?>
    </div>
</section>
<?php get_footer() ?>