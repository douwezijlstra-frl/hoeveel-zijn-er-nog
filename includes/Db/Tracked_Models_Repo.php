<?php
namespace Hoeveel_Zijn_Er_Nog\Db;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thin $wpdb wrapper for the hzen_tracked_models table.
 * All writes use prepared statements; only safe column names are interpolated.
 */
class Tracked_Models_Repo {

	/**
	 * Return the full table name.
	 */
	private static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'hzen_tracked_models';
	}

	/**
	 * Fetch all models, optionally filtered to active-only.
	 *
	 * @param bool $active_only When true, returns only rows with active = 1.
	 * @return array<int, array<string, mixed>>
	 */
	public static function all( bool $active_only = false ): array {
		global $wpdb;
		$table = self::table();

		if ( $active_only ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results( "SELECT * FROM {$table} WHERE active = 1 ORDER BY label ASC", ARRAY_A );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY label ASC", ARRAY_A );
		}

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Fetch a single model by its primary key.
	 */
	public static function find( int $id ): ?array {
		global $wpdb;
		$table = self::table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
		return $row ?: null;
	}

	/**
	 * Fetch a single model by slug.
	 */
	public static function find_by_slug( string $slug ): ?array {
		global $wpdb;
		$table = self::table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s", $slug ), ARRAY_A );
		return $row ?: null;
	}

	/**
	 * Insert a new tracked model.
	 *
	 * @param array<string, mixed> $data Keys: merk, handelsbenaming, voertuigsoort, inrichting, slug, label, active.
	 * @return int|false Inserted ID or false on failure.
	 */
	public static function insert( array $data ) {
		global $wpdb;

		$sanitized = self::sanitize( $data );
		if ( ! $sanitized['merk'] || ! $sanitized['slug'] ) {
			return false;
		}

		$result = $wpdb->insert(
			self::table(),
			$sanitized,
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%d' )
		);

		return false === $result ? false : (int) $wpdb->insert_id;
	}

	/**
	 * Update an existing tracked model.
	 *
	 * @param int                  $id   Row ID.
	 * @param array<string, mixed> $data Columns to update.
	 * @return bool
	 */
	public static function update( int $id, array $data ): bool {
		global $wpdb;

		$sanitized = self::sanitize( $data );
		$result    = $wpdb->update(
			self::table(),
			$sanitized,
			array( 'id' => $id ),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%d' ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Toggle the active flag for a model.
	 */
	public static function set_active( int $id, bool $active ): bool {
		global $wpdb;
		$result = $wpdb->update(
			self::table(),
			array( 'active' => $active ? 1 : 0 ),
			array( 'id' => $id ),
			array( '%d' ),
			array( '%d' )
		);
		return false !== $result;
	}

	/**
	 * Delete a model row. Related snapshots are NOT deleted automatically.
	 */
	public static function delete( int $id ): bool {
		global $wpdb;
		$result = $wpdb->delete(
			self::table(),
			array( 'id' => $id ),
			array( '%d' )
		);
		return false !== $result;
	}

	/**
	 * Generate a URL-safe unique slug from merk + handelsbenaming.
	 */
	public static function make_slug( string $merk, string $handelsbenaming ): string {
		$raw  = trim( $merk . ' ' . $handelsbenaming );
		$slug = sanitize_title( strtolower( $raw ) );
		if ( ! $slug ) {
			$slug = 'model-' . time();
		}
		// Ensure uniqueness by appending a counter.
		$original = $slug;
		$counter  = 1;
		while ( self::slug_exists( $slug ) ) {
			$slug = $original . '-' . $counter++;
		}
		return $slug;
	}

	/**
	 * Check whether a slug is already taken.
	 */
	private static function slug_exists( string $slug ): bool {
		global $wpdb;
		$table = self::table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE slug = %s", $slug ) );
		return $count > 0;
	}

	/**
	 * Sanitize an input array to only the allowed columns.
	 *
	 * @param array<string, mixed> $data Raw input.
	 * @return array<string, mixed> Sanitized output.
	 */
	private static function sanitize( array $data ): array {
		return array(
			'merk'            => strtoupper( sanitize_text_field( $data['merk'] ?? '' ) ),
			'handelsbenaming' => strtoupper( sanitize_text_field( $data['handelsbenaming'] ?? '' ) ),
			'voertuigsoort'   => strtoupper( sanitize_text_field( $data['voertuigsoort'] ?? 'P' ) ),
			'inrichting'      => strtoupper( sanitize_text_field( $data['inrichting'] ?? '' ) ),
			'slug'            => sanitize_title( $data['slug'] ?? '' ),
			'label'           => sanitize_text_field( $data['label'] ?? '' ),
			'active'          => isset( $data['active'] ) ? ( $data['active'] ? 1 : 0 ) : 1,
		);
	}
}
