<?php get_header() ?>
<?php
// that's CRUD object
$product = new WC_Product_Course(false);

$product->set_name('Wizard Hat'); // product title

$product->set_slug('medium-size-wizard-hat-in-new-york');

$product->set_regular_price(500.00); // in current shop currency

$product->save();

$product->get_id();

update_post_meta($product->get_id(), '_related_course', array(181));
?>
<pre>
    <?php
    var_dump($product);
    ?>
</pre>
<section class="archive-courses archive-grid background-light-gray py-5">
    <div class="container large-container">
        <?php the_content() ?>
        <pre>
         <?php //var_dump(get_post_meta(get_the_ID())) 
            ?>
        </pre>
    </div>
</section>
<?php get_footer() ?>