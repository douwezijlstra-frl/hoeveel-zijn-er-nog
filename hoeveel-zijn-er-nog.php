<?php
/**
 * Plugin Name: Hoeveel Zijn Er Nog
 * Description: Displays Dutch vehicle statistics from RDW Open Data via shortcode [rdw_count].
 * Version: 0.0.1
 * Author: Douwe Zijlstra
 * Text Domain: hoeveel-zijn-er-nog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define plugin constants.
define( 'HOEVEEL_ZIJN_ER_NOG_VERSION', '0.0.1' );
define( 'HOEVEEL_ZIJN_ER_NOG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HOEVEEL_ZIJN_ER_NOG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Autoload classes.
 */
require_once HOEVEEL_ZIJN_ER_NOG_PLUGIN_DIR . 'includes/class-rdw-service.php';
require_once HOEVEEL_ZIJN_ER_NOG_PLUGIN_DIR . 'includes/class-rdw-shortcode.php';

/**
 * Initialize the plugin.
 */
function hoeveel_zijn_er_nog_init() {
	$service   = new Hoeveel_Zijn_Er_Nog_Rdw_Service();
	$shortcode = new Hoeveel_Zijn_Er_Nog_Shortcode( $service );
	$shortcode->register();
}
add_action( 'plugins_loaded', 'hoeveel_zijn_er_nog_init' );

/**
 * Enqueue styles.
 */
function hoeveel_zijn_er_nog_enqueue_scripts() {
	wp_enqueue_style(
		'hoeveel-zijn-er-nog-style',
		HOEVEEL_ZIJN_ER_NOG_PLUGIN_URL . 'assets/css/style.css',
		array(),
		HOEVEEL_ZIJN_ER_NOG_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'hoeveel_zijn_er_nog_enqueue_scripts' );
