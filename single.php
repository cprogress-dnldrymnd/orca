<?php get_header() ?>
<section class="archive-courses archive-grid background-light-gray py-5">
    <div class="container large-container">
        <?php the_content() ?>
        <?php

        $price = learndash_get_course_price(855)['price'];
        echo $price;
        $product_price_update = get_option('product_price_update');
        if ($product_price_update) {
            foreach ($product_price_update as $product_id) {
                unset($product_price_update[$product_id]);
                $course_id = get_post_meta($product_id, '_related_course', true);
                $price = learndash_get_course_price($course_id[0])['price'];
                $product = new WC_Product_Course($product_id);
                $product->set_regular_price($price);
                $product->save();
            }
            update_option('product_price_update', array());
        }
        update_option('product_price_update', array());

        ?>
        <pre>
            <?php var_dump(get_option('product_price_update')) ?>
        </pre>
    </div>
</section>
<?php get_footer() ?>