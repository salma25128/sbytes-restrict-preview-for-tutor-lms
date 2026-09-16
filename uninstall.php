<?php
/**
 * Uninstall routine.
 *
 * Removes the plugin's stored settings. Course, lesson and user data are
 * owned by Tutor LMS and WordPress and are never touched.
 *
 * @package SbytesRestrictPreviewForTutorLMS
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'rptl_settings' );

// Multisite: clean each site in the network.
if ( is_multisite() ) {
	$site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		delete_option( 'rptl_settings' );
		restore_current_blog();
	}
}
