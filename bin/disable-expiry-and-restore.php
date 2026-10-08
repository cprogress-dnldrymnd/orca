<?php
/**
 * One-off: disable course expire_access on all courses and restore expired users.
 *
 * Usage (from app/public, with mysqli loaded):
 *   wp eval-file wp-content/themes/orca/bin/disable-expiry-and-restore.php \
 *     --skip-plugins=wordfence,google-site-kit,updraftplus,wp-mail-smtp,beacon-crm-integration,learndash-notifications,learndash-zapier,zapier
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run via WP-CLI: wp eval-file ...\n" );
	exit( 1 );
}

if ( ! function_exists( 'learndash_update_setting' ) || ! function_exists( 'ld_update_course_access' ) ) {
	WP_CLI::error( 'LearnDash is not loaded.' );
}

// Hard-stop outbound email and noisy integrations during bulk restore.
add_filter( 'pre_wp_mail', '__return_true', 1 );
add_filter( 'woocommerce_email_enabled_new_order', '__return_false', 99 );
add_filter( 'woocommerce_email_enabled_customer_completed_order', '__return_false', 99 );
add_filter( 'woocommerce_email_enabled_customer_processing_order', '__return_false', 99 );
remove_all_actions( 'learndash_update_course_access' );
remove_all_actions( 'learndash_user_course_access_expired' );

$disabled = 0;
$courses  = get_posts(
	array(
		'post_type'      => 'sfwd-courses',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

foreach ( $courses as $course_id ) {
	$current = learndash_get_setting( $course_id, 'expire_access' );
	if ( 'on' === $current || ! empty( $current ) ) {
		learndash_update_setting( $course_id, 'expire_access', '' );
		$disabled++;
		WP_CLI::log( "Disabled expire_access on course {$course_id}" );
	}
}

WP_CLI::success( "Disabled expiry on {$disabled} course(s); scanned " . count( $courses ) . ' total.' );

global $wpdb;
$rows = $wpdb->get_results(
	"SELECT user_id, meta_key FROM {$wpdb->usermeta} WHERE meta_key LIKE 'learndash_course_expired_%'"
);

$total    = count( $rows );
$restored = 0;
$by_course = array();

WP_CLI::log( "Restoring {$total} expired enrolment(s)..." );

foreach ( $rows as $i => $row ) {
	if ( ! preg_match( '/^learndash_course_expired_(\d+)$/', $row->meta_key, $m ) ) {
		continue;
	}

	$user_id   = (int) $row->user_id;
	$course_id = (int) $m[1];

	// Re-enrol without firing integration side-effects (hooks removed above).
	ld_update_course_access( $user_id, $course_id, false );
	delete_user_meta( $user_id, 'learndash_course_expired_' . $course_id );
	delete_user_meta( $user_id, 'learndash_course_' . $course_id . '_access_extended_until' );

	$restored++;
	if ( ! isset( $by_course[ $course_id ] ) ) {
		$by_course[ $course_id ] = 0;
	}
	$by_course[ $course_id ]++;

	if ( 0 === ( $restored % 50 ) ) {
		WP_CLI::log( "  Progress: {$restored}/{$total}" );
	}
}

WP_CLI::success( "Restored {$restored} expired enrolment(s)." );
foreach ( $by_course as $course_id => $count ) {
	WP_CLI::log( "  Course {$course_id}: {$count}" );
}

$remaining = (int) $wpdb->get_var(
	"SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key LIKE 'learndash_course_expired_%'"
);
WP_CLI::log( "Remaining learndash_course_expired_* rows: {$remaining}" );
