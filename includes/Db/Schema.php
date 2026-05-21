<?php
namespace Hoeveel_Zijn_Er_Nog\Db;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates and upgrades the custom DB tables used by this plugin.
 *
 * Tables:
 *   {prefix}hzen_tracked_models — vehicles that are actively tracked.
 *   {prefix}hzen_snapshots      — daily aggregate snapshots per model.
 *
 * Schema version stored in option `hzen_db_version`.
 */
class Schema {

	const DB_VERSION        = '1.0.0';
	const DB_VERSION_OPTION = 'hzen_db_version';

	/**
	 * Run on activation and on plugins_loaded when schema is out-of-date.
	 */
	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();

		$tracked = "CREATE TABLE {$wpdb->prefix}hzen_tracked_models (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  merk VARCHAR(64) NOT NULL DEFAULT '',
  handelsbenaming VARCHAR(128) NOT NULL DEFAULT '',
  voertuigsoort VARCHAR(8) NOT NULL DEFAULT 'P',
  inrichting VARCHAR(32) NOT NULL DEFAULT '',
  slug VARCHAR(160) NOT NULL DEFAULT '',
  label VARCHAR(160) NOT NULL DEFAULT '',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY slug (slug),
  UNIQUE KEY uniq_combo (merk, handelsbenaming, voertuigsoort, inrichting)
) {$charset};";

		$snapshots = "CREATE TABLE {$wpdb->prefix}hzen_snapshots (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  model_id BIGINT UNSIGNED NOT NULL,
  snapshot_date DATE NOT NULL,
  total INT UNSIGNED NOT NULL DEFAULT 0,
  apk_valid INT UNSIGNED NOT NULL DEFAULT 0,
  oldest_date DATE NULL,
  newest_date DATE NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY uniq_day (model_id, snapshot_date),
  KEY idx_date (snapshot_date)
) {$charset};";

		dbDelta( $tracked );
		dbDelta( $snapshots );

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION, false );
	}

	/**
	 * Drop both custom tables. Called from uninstall.php.
	 */
	public static function uninstall() {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}hzen_snapshots" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}hzen_tracked_models" );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		delete_option( self::DB_VERSION_OPTION );
	}

	/**
	 * Check whether the schema needs upgrading and run install() if so.
	 * Hooked to plugins_loaded.
	 */
	public static function maybe_upgrade() {
		if ( get_option( self::DB_VERSION_OPTION ) !== self::DB_VERSION ) {
			self::install();
		}
	}
}
