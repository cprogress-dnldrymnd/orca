<?php get_header() ?>
<section class="archive-courses archive-grid background-light-gray py-5">
    <div class="container large-container">
        <?php the_content() ?>
        <?php

        $price = learndash_get_course_price(855)['price'];
        echo $price;
        $product = new WC_Product_Course(855);
        $product->set_regular_price(20);
        $product->save();

        ?>
        <pre>
        </pre>
    </div>
</section>
<?php get_footer() ?>