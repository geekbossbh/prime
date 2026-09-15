<?php
/**
 * Plugin Name: Lioness Prime — Course Page
 * Description: Serves the Lioness Prime Course enrollment page, emails the invoice to the
 *              buyer and to Lioness Prime when a payment is confirmed, and records every
 *              enrollment under Enrollments in the admin. Payment screenshots are kept out
 *              of the media library and served only to signed-in staff.
 * Version:     2.3.0
 * Author:      Lioness Prime
 * License:     GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads the newest core file in core/.
 *
 * This file deliberately holds no logic of its own beyond the search below, so
 * that it never needs to change between releases — which matters because a
 * server caching compiled PHP may go on running an old copy of THIS file long
 * after it has been replaced. The search itself does not change, so even a
 * stale copy of it still finds and loads the newest core.
 */
$lioness_cores = glob( __DIR__ . '/core/lioness-core-*.php' );

if ( ! empty( $lioness_cores ) ) {
	usort( $lioness_cores, function ( $a, $b ) {
		return version_compare(
			basename( $a, '.php' ),
			basename( $b, '.php' )
		);
	} );
	require_once end( $lioness_cores );
}
