<?php
add_action('wp_ajax_nopriv_archive_ajax', 'archive_ajax'); // for not logged in users
add_action('wp_ajax_archive_ajax', 'archive_ajax');
function archive_ajax()
{
	$taxonomy_terms = $_POST['taxonomy_terms'];
	$taxonomy = $_POST['taxonomy'];
	$post_type = $_POST['post_type'];
	$offset = $_POST['offset'];
	//$sortby = $_POST['sortby'];
	$posts_per_page = 6;

	$args = array(
		'post_type' => $post_type,
		'posts_per_page' => $posts_per_page,
	);

	if ($offset) {
		$args['offset'] = $offset;
	}

	/*
    if ($sortby) {
        if ($sortby == 'oldest') {
            $args['order'] = 'ASC';
        } else if ($sortby == 'title') {
            $args['orderby'] = 'title';
            $args['order'] = 'ASC';
        }
    }*/

	if ($taxonomy_terms) {
		if ($taxonomy != 'category') {
			$args['tax_query'] = array(
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $taxonomy_terms,
				),
			);
		} else {
			$args['cat'] = $taxonomy_terms;
		}
	}

	$the_query = new WP_Query($args);

	$count = $the_query->found_posts;
	echo hide_load_more($count, $offset, $posts_per_page);

	echo do_shortcode("[archive_grid offset='$offset' args='$args']");

	die();
}


/*
add_action('wp_ajax_nopriv_careers_ajax', 'careers_ajax'); // for not logged in users
add_action('wp_ajax_careers_ajax', 'careers_ajax');
function careers_ajax()
{
	$DisplayData = new DisplayData();
	$location = $_POST['location'];
	$taxonomy = 'location';
	$post_type = 'jobs';
	$offset = $_POST['offset'];
	$posts_per_page = 5;

	$args = array(
		'post_type' => $post_type,
		'posts_per_page' => $posts_per_page,
	);

	if ($offset) {
		$args['offset'] = $offset;
	}

	if ($location) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => $location,
			),
		);
	}

	$the_query = new WP_Query($args);

	$count = $the_query->found_posts;

	echo hide_load_more($count, $offset, $posts_per_page);

	if ($the_query->have_posts()) {
	?>
		<?php if (!$offset) { ?>
			<div class="career-wrapper">
			<?php } ?>
			<?php
			while ($the_query->have_posts()) {
				$the_query->the_post(); ?>
				<?php
				$postterms = get_the_terms(get_the_ID(), 'location');
				$salary = carbon_get_the_post_meta('salary');
				$accordion = carbon_get_the_post_meta('accordion');
				$apply_form_url = carbon_get_the_post_meta('apply_form_url');
				?>

				<div class="career-holder background-white post-item">
					<div class="inner">
						<div class="header justify-space-between">
							<div class="career-title align-center">
								<h3><?php the_title() ?></h3>
								<span class="salary"><?= $salary ?></span>
                                <div class="location align-center">
								<svg xmlns="http://www.w3.org/2000/svg" width="10.908" height="15.583" viewBox="0 0 10.908 15.583">
									<path id="Icon_material-location-on" data-name="Icon material-location-on" d="M12.954,3A5.45,5.45,0,0,0,7.5,8.454c0,4.091,5.454,10.129,5.454,10.129s5.454-6.038,5.454-10.129A5.45,5.45,0,0,0,12.954,3Zm0,7.4A1.948,1.948,0,1,1,14.9,8.454,1.949,1.949,0,0,1,12.954,10.4Z" transform="translate(-7.5 -3)" fill="#001f2b" />
								</svg>
								<span>
									<?php foreach ($postterms as $postterm) { ?>
										<span><?= $postterm->name ?></span>
									<?php } ?>
								</span>
							</div>
							</div>
							
						</div>
						<div class="body">
							<div class="career-description d-none d-sm-block">
								<?php the_content() ?>
							</div>
							<?php if ($accordion) { ?>
								<div class="accordion-holder accordion-style-1">
									<div class="accordion" id="accordion-<?= get_the_ID() ?>">
										<div class="accordion-item d-block d-sm-none">
											<h2 class="accordion-header" id="heading<?= get_the_ID() . '-description'  ?>">
												<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= get_the_ID() . '-description'  ?>" aria-expanded="false" aria-controls="collapse<?= get_the_ID() . '-description'  ?>">
													<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus" viewBox="0 0 16 16">
														<path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4" />
													</svg>
													<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-dash" viewBox="0 0 16 16">
														<path d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8" />
													</svg>
													<span> Job Description </span>
												</button>
											</h2>
											<div id="collapse<?= get_the_ID() . '-description'  ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= get_the_ID() . '-description'  ?>" data-bs-parent="#accordion-<?= get_the_ID() ?>">
												<div class="accordion-body">
													<?php the_excerpt()?>
												</div>
											</div>
										</div>
										<?php foreach ($accordion as $key => $acc) { ?>
										<?php if($acc['accordion_content'] != '') { ?>
											<div class="accordion-item">
												<h2 class="accordion-header" id="heading<?= get_the_ID() . '-' . $key ?>">
													<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= get_the_ID() . '-' . $key ?>" aria-expanded="false" aria-controls="collapse<?= get_the_ID() . '-' . $key ?>">
														<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus" viewBox="0 0 16 16">
															<path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4" />
														</svg>
														<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-dash" viewBox="0 0 16 16">
															<path d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8" />
														</svg>
														<span> <?= $acc['accordion_title'] ?></span>
													</button>
												</h2>
												<div id="collapse<?= get_the_ID() . '-' . $key ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= get_the_ID() . '-' . $key ?>" data-bs-parent="#accordion-<?= get_the_ID() ?>">
													<div class="accordion-body">
														<?= wpautop($acc['accordion_content']) ?>
													</div>
												</div>
											</div>
										<?php } ?>
										<?php } ?>

									</div>
								</div>
							<?php } ?>
						</div>
						<div class="footer">
							<div class="button-group-box d-flex flex-wrap justify-flex-end">
								<div class="button-box button-black">
									<a href="<?= get_permalink() ?>" type="button" class="button">
										<span>READ MORE</span>
									</a>
								</div>
								<div class="button-box">
									<a type="button" class="button accent-button apply-button" href="<?= $apply_form_url ?>" target="_blank">
										<span>APPLY FOR THIS POSITION</span>
										<svg xmlns="http://www.w3.org/2000/svg" width="21.587" height="21.549" viewBox="0 0 21.587 21.549">
											<g id="Group_18" data-name="Group 18" transform="translate(6.042 -4.628) rotate(45)">
												<path id="Path_7" data-name="Path 7" d="M0,0,7.518,7.518,0,15.036" transform="translate(12.345)" fill="none" stroke="currentColor" stroke-width="2" />
												<line id="Line_1" data-name="Line 1" x1="20" transform="translate(0 7.545)" fill="none" stroke="currentColor" stroke-width="2" />
											</g>
										</svg>
                                        </a>
								</div>
								
							</div>
						</div>
					</div>
				</div>
			<?php } ?>
			<?php wp_reset_postdata() ?>
			<?php if (!$offset) { ?>
			</div>
		<?php } ?>
	<?php
	} else {
	?>
		<h2>No Results Found</h2>
<?php
	}
	die();
}
*/
function hide_load_more($count, $offset, $posts_per_page)
{
	if ($count == ($offset + $posts_per_page) || $count < ($offset + $posts_per_page) || $count < $posts_per_page + 1) {
		return '<style>.load-more {display: none} </style>';
	}
}
