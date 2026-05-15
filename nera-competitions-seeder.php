<?php
/**
 * Plugin Name: Nera Competitions Seeder
 * Description: Seeds demo competition/lottery products covering every variant supported by Lottery for WooCommerce. Adds a page under Tools → Nera Competitions Seeder.
 * Version: 1.0.0
 * Author: Giveaway Guru
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * Text Domain: nera-competitions-seeder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GG_DEMO_PRODUCTS_FILE', __FILE__ );
define( 'GG_DEMO_PRODUCTS_DIR', plugin_dir_path( __FILE__ ) );
define( 'GG_DEMO_PRODUCTS_URL', plugin_dir_url( __FILE__ ) );
define( 'GG_DEMO_PRODUCTS_VERSION', '1.0.0' );
define( 'GG_DEMO_PRODUCTS_OPTION', 'gg_demo_products_seeded_ids' );
define( 'GG_DEMO_PRODUCTS_CATEGORY', 'Demo Competitions' );

require_once GG_DEMO_PRODUCTS_DIR . 'includes/class-gg-demo-fixtures.php';
require_once GG_DEMO_PRODUCTS_DIR . 'includes/class-gg-demo-image.php';
require_once GG_DEMO_PRODUCTS_DIR . 'includes/class-gg-demo-seeder.php';
require_once GG_DEMO_PRODUCTS_DIR . 'includes/class-gg-demo-admin-page.php';

register_activation_hook( __FILE__, 'gg_demo_products_activate' );
function gg_demo_products_activate() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			esc_html__( 'Nera Competitions Seeder requires WooCommerce to be active.', 'nera-competitions-seeder' ),
			esc_html__( 'Plugin dependency missing', 'nera-competitions-seeder' ),
			array( 'back_link' => true )
		);
	}
}

add_action( 'plugins_loaded', 'gg_demo_products_boot' );
function gg_demo_products_boot() {
	if ( is_admin() ) {
		( new GG_Demo_Admin_Page() )->register();
	}
}
