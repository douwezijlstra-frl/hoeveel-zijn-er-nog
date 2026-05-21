<?php
/**
 * Plugin Name: Hoeveel Zijn Er Nog
 * Description: Displays Dutch vehicle statistics from RDW Open Data via shortcode [rdw_count].
 * Version: 0.2.0
 * Author: Douwe Zijlstra
 * Text Domain: hoeveel-zijn-er-nog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HOEVEEL_ZIJN_ER_NOG_VERSION', '0.2.0' );
define( 'HOEVEEL_ZIJN_ER_NOG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HOEVEEL_ZIJN_ER_NOG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'HOEVEEL_ZIJN_ER_NOG_PLUGIN_FILE', __FILE__ );

require_once HOEVEEL_ZIJN_ER_NOG_PLUGIN_DIR . 'includes/class-autoloader.php';

// Backwards-compatible aliases for the pre-0.1.0 flat class names.
class_alias( 'Hoeveel_Zijn_Er_Nog\\Services\\Rdw_Stats_Provider', 'Hoeveel_Zijn_Er_Nog_Rdw_Service' );
class_alias( 'Hoeveel_Zijn_Er_Nog\\Frontend\\Shortcode_Rdw_Count', 'Hoeveel_Zijn_Er_Nog_Shortcode' );

// -------------------------------------------------------------------------
// Activation hook
// -------------------------------------------------------------------------
register_activation_hook(
	__FILE__,
	function () {
		\Hoeveel_Zijn_Er_Nog\Db\Schema::install();
		\Hoeveel_Zijn_Er_Nog\Cron\Snapshot_Runner::schedule();
	}
);

// -------------------------------------------------------------------------
// Deactivation hook
// -------------------------------------------------------------------------
register_deactivation_hook(
	__FILE__,
	function () {
		\Hoeveel_Zijn_Er_Nog\Cron\Snapshot_Runner::unschedule();
	}
);

// -------------------------------------------------------------------------
// Boot
// -------------------------------------------------------------------------
add_action(
	'plugins_loaded',
	function () {
		// Run schema upgrades when plugin version changes.
		\Hoeveel_Zijn_Er_Nog\Db\Schema::maybe_upgrade();

		\Hoeveel_Zijn_Er_Nog\Plugin::instance()->boot();
	},
	20
);

// -------------------------------------------------------------------------
// WP-CLI command registration
// -------------------------------------------------------------------------
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	\WP_CLI::add_command( 'hzen snapshot', 'Hoeveel_Zijn_Er_Nog\\Cli\\Snapshot_Command' );
}
