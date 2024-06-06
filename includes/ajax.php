<?php
add_action('wp_ajax_nopriv_archive_ajax', 'archive_ajax'); // for not logged in users
add_action('wp_ajax_archive_ajax', 'archive_ajax');
function archive_ajax()
{
	$taxonomy_terms = $_POST['taxonomy_terms'];
	$taxonomy = $_POST['taxonomy'];
	$post_type = $_POST['post_type'];
	$offset = $_POST['offset'];
	$include_product = $_POST['include_product'];
	//$sortby = $_POST['sortby'];
	$posts_per_page = 12;
	//$post_type_arr[] = $post_type;

	$args = array();
	if ($include_product == 'yes') {
		$post_type_arr[] = 'product';
	}
	$args['post_type'] = $post_type_arr;
	$args['posts_per_page'] = $posts_per_page;
	$args['menu_order'] = 'menu_order';
	$args['order'] = 'ASC';

	if ($offset) {
		$args['offset'] = $offset;
	}

	if ($include_product == 'yes') {
		$args['tax_query'][] = array(
			'taxonomy' => 'product_cat',
			'field' => 'slug',
			'terms' => array('bundles'),
		);
	}
	if ($taxonomy_terms) {
		if ($taxonomy != 'category') {
			$args['tax_query'][] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => $taxonomy_terms,
			);
		} else {
			$args['cat'] = $taxonomy_terms;
		}
	}

	echo '<pre>';
	var_dump($args);
	echo '</pre>';

	$argsx = array(
		'post_type' => 'product',
		'tax_query' => array(
			array(
				'taxonomy' => 'product_cat',
				'field' => 'slug',
				'terms' => 'bundles',
			),
		),
	);
	$query = new WP_Query($argsx);
	if ($query->have_posts()) {
		while ($query->have_posts()) {
			$query->the_post();
			echo get_the_title();
			echo '<br>';
		}
	} else {
		echo 'no post';
	}
	echo '<pre>';
	var_dump($args);
	echo '</pre>';
	$the_query = new WP_Query($argsx);

	$count = $the_query->found_posts;

	echo hide_load_more($count, $offset, $posts_per_page);
?>
	<?php if (!$offset) { ?>
		<div class="row row-archive g-4">
		<?php } ?>
		<?php
		if ($the_query->have_posts()) {
			while ($the_query->have_posts()) {
				$the_query->the_post();
		?>
				<div class="col-md-4 col-6 post-item">
					<div class="column-holder d-flex flex-column justify-content-between background-white h-100">
						<?= do_shortcode('[_learndash_image id="' . get_the_ID() . '" image_id="' . get_post_thumbnail_id() . '" size="medium" learndash_status_bubble="true" taxonomy="ld_course_category"]') ?>
						<div class="content-holder d-flex flex-column justify-content-between">
							<div>
								<?= do_shortcode('[_heading class="color-primary" tag="h3" heading="' . get_the_title() . '"]'); ?>
								<?= do_shortcode('[_description description="' . get_the_excerpt() . '"]'); ?>
								<hr>
								<?= do_shortcode('[_learndash_course_meta]'); ?>
							</div>
							<div>
								<?= do_shortcode('[_learndash_course_button id="' . get_the_ID() . '"]'); ?>
							</div>
						</div>
					</div>
				</div>
			<?php }
		} else {
			?>
			<h2>No Results Found</h2>
		<?php
		}
		wp_reset_postdata();
		?>
		<?php if (!$offset) { ?>
		</div>
	<?php } ?>
	<script>
		jQuery(document).ready(function() {
			$final_count = jQuery('.post-item').length;
			jQuery('.post-number').text($final_count);
			jQuery('.found-post').text(<?= $count ?>);
		});
	</script>
	<?php

	die();
}


function hide_load_more($count, $offset, $posts_per_page)
{
	ob_start();
	if ($count == ($offset + $posts_per_page) || $count < ($offset + $posts_per_page) || $count < $posts_per_page + 1) {
	?>
		<script>
			jQuery(document).ready(function() {
				jQuery('.load-more').addClass('d-none');
			});
		</script>
	<?php
	} else {
	?>
		<script>
			jQuery(document).ready(function() {
				jQuery('.load-more').removeClass('d-none');
			});
		</script>
<?php
	}
	return ob_get_clean();
}
