<?php
/**
 * Uninstall handler — removes the tracking option.
 * The seeded products themselves are NOT auto-deleted on uninstall; use the
 * "Wipe All Demo Competitions" button in Tools → Nera Competitions Seeder before uninstalling
 * if you want them removed.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'gg_demo_products_seeded_ids' );
