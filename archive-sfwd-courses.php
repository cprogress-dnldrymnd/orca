<?php get_header() ?>


<section class="archive-courses">
    <div class="container">
        <div class="row">
            <?php while (have_posts()) { ?>
                <?php the_post() ?>
                <div class="col-lg-4">
                    <div class="column-holder">
                       ssss
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</section>

<?php get_footer() ?>