<?php get_header() ?>

<section class="page-section">
    <div class="container">
        <?php while (have_posts()) { ?>
            <?php the_post() ?>
            <?php the_content() ?>
        <?php } ?>
    </div>
</section>

<?php get_footer() ?>