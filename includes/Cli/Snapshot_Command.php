<?php
namespace Hoeveel_Zijn_Er_Nog\Cli;

use Hoeveel_Zijn_Er_Nog\Cron\Snapshot_Runner;
use Hoeveel_Zijn_Er_Nog\Db\Tracked_Models_Repo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP-CLI command: wp hzen snapshot
 *
 * Usage:
 *   wp hzen snapshot --all            Run snapshot for all active models.
 *   wp hzen snapshot --model=<slug>   Run snapshot for one specific model.
 */
class Snapshot_Command {

	/**
	 * Take a snapshot of one or all tracked models.
	 *
	 * ## OPTIONS
	 *
	 * [--model=<slug>]
	 * : Slug of the model to snapshot. Required unless --all is passed.
	 *
	 * [--all]
	 * : Snapshot every active tracked model.
	 *
	 * ## EXAMPLES
	 *
	 *   wp hzen snapshot --all
	 *   wp hzen snapshot --model=tesla-model-3
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Positional arguments (unused).
	 * @param array $assoc_args Named arguments.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$runner = new Snapshot_Runner();
		$all    = isset( $assoc_args['all'] );
		$slug   = $assoc_args['model'] ?? '';

		if ( $all ) {
			$models = Tracked_Models_Repo::all( true );
			if ( ! $models ) {
				\WP_CLI::warning( 'No active tracked models found.' );
				return;
			}
			foreach ( $models as $model ) {
				$this->run_for_model( $runner, (int) $model['id'], $model['slug'] );
			}
			\WP_CLI::success( sprintf( 'Snapshot complete for %d model(s).', count( $models ) ) );
			return;
		}

		if ( $slug ) {
			$model = Tracked_Models_Repo::find_by_slug( $slug );
			if ( ! $model ) {
				\WP_CLI::error( "No tracked model found with slug '{$slug}'." );
				return;
			}
			$this->run_for_model( $runner, (int) $model['id'], $model['slug'] );
			\WP_CLI::success( "Snapshot complete for '{$slug}'." );
			return;
		}

		\WP_CLI::error( 'Please pass --all or --model=<slug>.' );
	}

	/**
	 * Run a single-model snapshot and emit progress.
	 */
	private function run_for_model( Snapshot_Runner $runner, int $id, string $slug ): void {
		\WP_CLI::log( "Fetching snapshot for model #{$id} ({$slug})…" );
		$runner->fetch_model( $id );
	}
}
