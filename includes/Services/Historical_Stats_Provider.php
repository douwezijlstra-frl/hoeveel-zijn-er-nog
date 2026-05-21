<?php
namespace Hoeveel_Zijn_Er_Nog\Services;

use Hoeveel_Zijn_Er_Nog\Db\Tracked_Models_Repo;
use Hoeveel_Zijn_Er_Nog\Db\Snapshots_Repo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Decorator around Rdw_Stats_Provider that adds DB-backed history.
 *
 * get_stats() delegates entirely to the wrapped provider.
 * get_history() looks up the tracked model by merk/handelsbenaming/voertuigsoort/inrichting
 * and returns rows from hzen_snapshots.
 *
 * Wired in via the hzen_stats_provider filter in Plugin::boot().
 */
class Historical_Stats_Provider implements Stats_Provider {

	/** @var Stats_Provider */
	private $inner;

	public function __construct( Stats_Provider $inner ) {
		$this->inner = $inner;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_stats( array $criteria ): array {
		return $this->inner->get_stats( $criteria );
	}

	/**
	 * Return historical snapshot rows for the given criteria and date range.
	 *
	 * @param array  $criteria  Same keys as get_stats (merk, handelsbenaming, …).
	 * @param string $from      YYYY-MM-DD inclusive.
	 * @param string $to        YYYY-MM-DD inclusive.
	 * @return array<int, array<string, mixed>> Each row: snapshot_date, total, apk_valid, oldest_date, newest_date.
	 */
	public function get_history( array $criteria, string $from, string $to ): array {
		$model = $this->find_model( $criteria );
		if ( ! $model ) {
			return array();
		}

		return Snapshots_Repo::for_model( (int) $model['id'], $from, $to );
	}

	/**
	 * Find a tracked model matching the given criteria (exact match on the four RDW fields).
	 *
	 * @param array<string, mixed> $criteria
	 * @return array<string, mixed>|null
	 */
	private function find_model( array $criteria ): ?array {
		$merk            = strtoupper( sanitize_text_field( $criteria['merk'] ?? '' ) );
		$handelsbenaming = strtoupper( sanitize_text_field( $criteria['handelsbenaming'] ?? '' ) );
		$voertuigsoort   = strtoupper( sanitize_text_field( $criteria['voertuigsoort'] ?? 'P' ) );
		$inrichting      = strtoupper( sanitize_text_field( $criteria['inrichting'] ?? '' ) );

		global $wpdb;
		$table = $wpdb->prefix . 'hzen_tracked_models';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE merk = %s
				  AND handelsbenaming = %s
				  AND voertuigsoort = %s
				  AND inrichting = %s
				LIMIT 1",
				$merk,
				$handelsbenaming,
				$voertuigsoort,
				$inrichting
			),
			ARRAY_A
		);

		return $row ?: null;
	}
}
