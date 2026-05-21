<?php
namespace Hoeveel_Zijn_Er_Nog\Cron;

use Hoeveel_Zijn_Er_Nog\Db\Tracked_Models_Repo;
use Hoeveel_Zijn_Er_Nog\Db\Snapshots_Repo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages the WP-Cron events that take daily snapshots of every tracked model.
 *
 * Flow:
 *   1. Master event `hzen_daily_fetch` fires once per day.
 *   2. It loops active tracked models and schedules one single event
 *      `hzen_fetch_model_{id}` per model, offset by 30 s each.
 *   3. Each per-model event fetches live stats (cache bypassed), upserts a
 *      snapshot row, and fires action `hzen_stats_fetched`.
 */
class Snapshot_Runner {

	const DAILY_EVENT = 'hzen_daily_fetch';

	/**
	 * Register all WP action hooks.
	 */
	public function register(): void {
		add_action( self::DAILY_EVENT, array( $this, 'dispatch_models' ) );

		// Hook the per-model single events; the hook name includes the model id.
		add_action( 'hzen_fetch_model', array( $this, 'fetch_model' ) );
	}

	/**
	 * Schedule the master daily event on plugin activation.
	 * Safe to call multiple times — checks if already scheduled.
	 */
	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::DAILY_EVENT ) ) {
			wp_schedule_event( time(), 'daily', self::DAILY_EVENT );
		}
	}

	/**
	 * Unschedule the master daily event on plugin deactivation/uninstall.
	 */
	public static function unschedule(): void {
		$timestamp = wp_next_scheduled( self::DAILY_EVENT );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::DAILY_EVENT );
		}
		wp_clear_scheduled_hook( self::DAILY_EVENT );
	}

	/**
	 * Fired by `hzen_daily_fetch`: schedule one single event per active model.
	 */
	public function dispatch_models(): void {
		$models = Tracked_Models_Repo::all( true );
		$offset = 0;

		foreach ( $models as $model ) {
			wp_schedule_single_event(
				time() + $offset,
				'hzen_fetch_model',
				array( (int) $model['id'] )
			);
			$offset += 30;
		}
	}

	/**
	 * Fired by `hzen_fetch_model`: fetch stats and upsert a snapshot.
	 *
	 * @param int $model_id
	 */
	public function fetch_model( int $model_id ): void {
		$model = Tracked_Models_Repo::find( $model_id );
		if ( ! $model || ! $model['active'] ) {
			return;
		}

		$criteria = array(
			'merk'            => $model['merk'],
			'handelsbenaming' => $model['handelsbenaming'],
			'voertuigsoort'   => $model['voertuigsoort'],
			'inrichting'      => $model['inrichting'],
		);

		// Bypass the transient cache for cron fetches so we always get fresh data.
		add_filter( 'hzen_cache_bypass', '__return_true' );

		$provider = apply_filters( 'hzen_stats_provider_raw', null );
		if ( ! $provider instanceof \Hoeveel_Zijn_Er_Nog\Services\Stats_Provider ) {
			// Fall back to building our own provider.
			$provider = self::build_provider();
		}

		$stats = $provider->get_stats( $criteria );

		remove_filter( 'hzen_cache_bypass', '__return_true' );

		if ( empty( $stats['total'] ) && 0 === (int) $stats['total'] && ! isset( $stats['apk_valid'] ) ) {
			// Nothing useful returned; skip upsert.
			return;
		}

		Snapshots_Repo::upsert( $model_id, $stats );

		/**
		 * Fires after a snapshot has been saved.
		 *
		 * @param int   $model_id The tracked-model row ID.
		 * @param array $model    The full model row.
		 * @param array $stats    The fetched stats array.
		 * @param string $date    Snapshot date (YYYY-MM-DD).
		 */
		do_action( 'hzen_stats_fetched', $model_id, $model, $stats, gmdate( 'Y-m-d' ) );
	}

	/**
	 * Build a bare Rdw_Stats_Provider when the filter-injected one isn't available.
	 */
	private static function build_provider(): \Hoeveel_Zijn_Er_Nog\Services\Stats_Provider {
		$cache  = new \Hoeveel_Zijn_Er_Nog\Cache\Cache();
		$client = new \Hoeveel_Zijn_Er_Nog\Api\Rdw_Client( $cache );
		return new \Hoeveel_Zijn_Er_Nog\Services\Rdw_Stats_Provider( $client );
	}
}
