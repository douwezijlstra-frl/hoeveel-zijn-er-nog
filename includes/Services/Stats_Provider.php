<?php
namespace Hoeveel_Zijn_Er_Nog\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface Stats_Provider {

	/**
	 * Return current stats for the given criteria.
	 *
	 * Keys: total (int), apk_valid (int),
	 *       oldest (array|null: kenteken, datum_eerste_toelating),
	 *       newest (array|null: kenteken, datum_eerste_toelating).
	 */
	public function get_stats( array $criteria ): array;

	/**
	 * Return historical snapshots between two YYYY-MM-DD dates.
	 * Phase 1 returns an empty array; Phase 2 plugs in DB-backed history.
	 */
	public function get_history( array $criteria, string $from, string $to ): array;
}
