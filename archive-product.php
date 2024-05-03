<?php get_header() ?>
<?php
$terms = get_terms(array(
    'taxonomy'   => 'product_cat',
    'hide_empty' => false,
));

$posts_per_page = 12;

$args = array(
    'post_type' => 'product',
    'posts_per_page' => $posts_per_page,
    'orderby' => 'menu_order',
    'order' => 'ASC'
);

$course_query = new WP_Query($args);
$count = $course_query->found_posts;
if ($count >= $posts_per_page) {
    $final_count = $posts_per_page;
} else {
    $final_count = $count;
}
echo hide_load_more($count, 0, $posts_per_page);
?>
<section class="archive-courses archive-grid archive-section background-light-gray py-5">
    <div class="container large-container">
        <div class="row g-4 filter mb-4 align-items-center">
            <div class="col-lg-6">
                <div class="showing">
                    Showing <span class="post-number"><?php echo $final_count ?></span> of <span class="found-post"><?php echo $count ?> </span> Products
                </div>
            </div>
            <div class="col-lg-6 text-end">
                <div class="filter-box d-inline-flex">
                    <div class="filter-select me-3">
                        <input type="hidden" name="post-type" value="<?= get_post_type() ?>">
                        <input type="hidden" name="taxonomy" value="ld_course_category">
                        <select name="taxonomy-terms" id="taxonomy-terms" class="archive-form-filter">
                            <option value="">All Products</option>
                            <?php foreach ($terms as $term) { ?>
                                <option value="<?= $term->term_id ?>"><?= $term->name ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="filter-button">
                        <button id="apply-filter">Apply Filter</button>
                    </div>
                </div>
            </div>
        </div>
        <div id="results">
            <div class="results-holder">
                <div class="row row-archive g-4">
                    <?php
                    if ($course_query->have_posts()) {
                        while ($course_query->have_posts()) {
                            $course_query->the_post();
                    ?>
                            <div class="col-md-4 col-6 post-item">
                                <div class="column-holder d-flex flex-column justify-content-between background-white h-100">
                                    <?= do_shortcode('[_image id="' . get_post_thumbnail_id() . '" size="medium"]') ?>
                                    <div class="content-holder d-flex flex-column justify-content-between">
                                        <div>
                                            <?= do_shortcode('[_heading class="color-primary" tag="h3" heading="' . get_the_title() . '"]'); ?>
                                            <?= do_shortcode('[_description description="' . get_the_excerpt() . '"]'); ?>
                                            <hr>
                                            <?= do_shortcode('[_learndash_course_meta]'); ?>
                                        </div>
                                        <div>
                                            <?= do_shortcode('[_button class="button add_to_cart_button" button_text="Add to Cart" button_link="' . get_permalink(get_the_ID()) . '"]'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php }
                        wp_reset_postdata();
                    } else {
                        ?>
                        <h2>No Results Found</h2>
                    <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        <div class="load-more text-center mt-5 d-none">
            <a href="#" id="load-more" class="btn btn-accent">
                <span>Load more</span>
                <svg class="spin" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                    <path fill="currentColor" d="M304 48a48 48 0 1 0 -96 0 48 48 0 1 0 96 0zm0 416a48 48 0 1 0 -96 0 48 48 0 1 0 96 0zM48 304a48 48 0 1 0 0-96 48 48 0 1 0 0 96zm464-48a48 48 0 1 0 -96 0 48 48 0 1 0 96 0zM142.9 437A48 48 0 1 0 75 369.1 48 48 0 1 0 142.9 437zm0-294.2A48 48 0 1 0 75 75a48 48 0 1 0 67.9 67.9zM369.1 437A48 48 0 1 0 437 369.1 48 48 0 1 0 369.1 437z" />
                </svg>
            </a>
        </div>
    </div>
</section>
<?php get_footer() ?>