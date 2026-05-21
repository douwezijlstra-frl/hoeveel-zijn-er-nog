<?php
namespace Hoeveel_Zijn_Er_Nog\Db;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thin $wpdb wrapper for the hzen_snapshots table.
 */
class Snapshots_Repo {

	/**
	 * Return the full table name.
	 */
	private static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'hzen_snapshots';
	}

	/**
	 * Fetch all snapshots for a model, ordered by date ascending.
	 *
	 * @param int    $model_id
	 * @param string $from YYYY-MM-DD inclusive.
	 * @param string $to   YYYY-MM-DD inclusive.
	 * @return array<int, array<string, mixed>>
	 */
	public static function for_model( int $model_id, string $from = '', string $to = '' ): array {
		global $wpdb;
		$table = self::table();

		if ( $from && $to ) {
			$rows = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE model_id = %d AND snapshot_date BETWEEN %s AND %s ORDER BY snapshot_date ASC",
					$model_id,
					$from,
					$to
				),
				ARRAY_A
			);
		} else {
			$rows = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE model_id = %d ORDER BY snapshot_date ASC",
					$model_id
				),
				ARRAY_A
			);
		}

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Upsert a snapshot row for today (or a given date).
	 *
	 * Uses INSERT … ON DUPLICATE KEY UPDATE so it is safe to run multiple times per day.
	 *
	 * @param int    $model_id
	 * @param array  $stats     Keys: total, apk_valid, oldest_date (YYYYMMDD), newest_date (YYYYMMDD).
	 * @param string $date      Snapshot date as YYYY-MM-DD (defaults to today).
	 * @return bool
	 */
	public static function upsert( int $model_id, array $stats, string $date = '' ): bool {
		global $wpdb;

		if ( ! $date ) {
			$date = gmdate( 'Y-m-d' );
		}

		$oldest = self::yyyymmdd_to_date( $stats['oldest_date'] ?? '' );
		$newest = self::yyyymmdd_to_date( $stats['newest_date'] ?? '' );

		$table = self::table();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare(
			"INSERT INTO {$table}
				(model_id, snapshot_date, total, apk_valid, oldest_date, newest_date)
			VALUES
				(%d, %s, %d, %d, %s, %s)
			ON DUPLICATE KEY UPDATE
				total        = VALUES(total),
				apk_valid    = VALUES(apk_valid),
				oldest_date  = VALUES(oldest_date),
				newest_date  = VALUES(newest_date)",
			$model_id,
			$date,
			(int) ( $stats['total'] ?? 0 ),
			(int) ( $stats['apk_valid'] ?? 0 ),
			$oldest,
			$newest
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return false !== $wpdb->query( $sql );
	}

	/**
	 * Delete all snapshots for a model (called before deleting the model itself).
	 */
	public static function delete_for_model( int $model_id ): bool {
		global $wpdb;
		$result = $wpdb->delete(
			self::table(),
			array( 'model_id' => $model_id ),
			array( '%d' )
		);
		return false !== $result;
	}

	/**
	 * Return the latest snapshot for a model.
	 */
	public static function latest( int $model_id ): ?array {
		global $wpdb;
		$table = self::table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE model_id = %d ORDER BY snapshot_date DESC LIMIT 1",
				$model_id
			),
			ARRAY_A
		);
		return $row ?: null;
	}

	/**
	 * Convert a RDW-style YYYYMMDD string to a MySQL DATE string YYYY-MM-DD.
	 * Returns NULL string on invalid input.
	 */
	private static function yyyymmdd_to_date( string $val ): ?string {
		if ( 8 === strlen( $val ) && ctype_digit( $val ) ) {
			return substr( $val, 0, 4 ) . '-' . substr( $val, 4, 2 ) . '-' . substr( $val, 6, 2 );
		}
		// Accept YYYY-MM-DD passthrough too (from the aggregate provider).
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $val ) ) {
			return $val;
		}
		return null;
	}
}
