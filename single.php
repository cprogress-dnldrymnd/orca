<?php get_header() ?>
<section class="archive-courses archive-grid background-light-gray py-5">
    <div class="container large-container">
        <?php the_content() ?>
        <pre>
         <?php var_dump(get_post_meta(181)['_sfwd-courses']) 
            ?>
        </pre>
    </div>
</section>
<?php get_footer() ?>