<?php
/**
 * Cleanup on plugin uninstall.
 *
 * Drops the custom tables and removes all stored options and transients.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Bootstrap constants so Schema / Cache can reference them.
if ( ! defined( 'HOEVEEL_ZIJN_ER_NOG_VERSION' ) ) {
	define( 'HOEVEEL_ZIJN_ER_NOG_VERSION', '0.2.0' );
}
if ( ! defined( 'HOEVEEL_ZIJN_ER_NOG_PLUGIN_DIR' ) ) {
	define( 'HOEVEEL_ZIJN_ER_NOG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'HOEVEEL_ZIJN_ER_NOG_PLUGIN_URL' ) ) {
	define( 'HOEVEEL_ZIJN_ER_NOG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

require_once HOEVEEL_ZIJN_ER_NOG_PLUGIN_DIR . 'includes/class-autoloader.php';

// 1. Drop custom tables.
\Hoeveel_Zijn_Er_Nog\Db\Schema::uninstall();

// 2. Purge transient cache entries tracked in the index option.
$index = (array) get_option( 'hzen_cache_index', array() );
foreach ( $index as $key ) {
	delete_transient( $key );
}

// 3. Remove all plugin options.
$options = array(
	'hzen_cache_index',
	'hzen_db_version',
	'hzen_app_token',
	'hzen_cache_ttl',
);
foreach ( $options as $option ) {
	delete_option( $option );
}
