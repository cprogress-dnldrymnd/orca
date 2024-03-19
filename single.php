<?php get_header() ?>
<?php
// that's CRUD object
$product = new WC_Product_Course();

$product->set_name('Wizard Hat'); // product title

$product->set_slug('medium-size-wizard-hat-in-new-york');

$product->set_regular_price(500.00); // in current shop currency

$product->set_short_description('<p>Here it is... A WIZARD HAT!</p><p>Only here and now.</p>');
// you can also add a full product description
// $product->set_description( 'long description here...' );

$product->set_image_id(90);

// let's suppose that our 'Accessories' category has ID = 19 
$product->set_category_ids(array(19));
// you can also use $product->set_tag_ids() for tags, brands etc

$product->save();
?>
<section class="archive-courses archive-grid background-light-gray py-5">
    <div class="container large-container">
        <?php the_content() ?>
        <pre>
         <?php var_dump(get_post_meta(get_the_ID())) ?>
        </pre>
    </div>
</section>
<?php get_footer() ?>