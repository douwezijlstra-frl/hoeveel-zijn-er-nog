<?php
namespace Hoeveel_Zijn_Er_Nog\Rest;

use Hoeveel_Zijn_Er_Nog\Db\Tracked_Models_Repo;
use Hoeveel_Zijn_Er_Nog\Db\Snapshots_Repo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API controller for historical snapshot data.
 *
 * Routes:
 *   GET hzen/v1/history — public; returns snapshot rows for a slug + date range.
 *
 * Query params:
 *   slug   (required) — tracked model slug.
 *   from   (optional) — YYYY-MM-DD, defaults to 12 months ago.
 *   to     (optional) — YYYY-MM-DD, defaults to today.
 *   metric (optional) — total|apk_valid|both (default: both).
 */
class History_Controller extends \WP_REST_Controller {

	const NAMESPACE = 'hzen/v1';
	const ROUTE     = '/history';

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => '__return_true', // public read
					'args'                => $this->get_collection_params(),
				),
			)
		);
	}

	/**
	 * Handle GET hzen/v1/history.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_items( $request ) {
		$slug = sanitize_title( $request->get_param( 'slug' ) );
		if ( ! $slug ) {
			return new \WP_Error(
				'hzen_missing_slug',
				__( 'The slug parameter is required.', 'hoeveel-zijn-er-nog' ),
				array( 'status' => 400 )
			);
		}

		$model = Tracked_Models_Repo::find_by_slug( $slug );
		if ( ! $model ) {
			return new \WP_Error(
				'hzen_model_not_found',
				__( 'No tracked model found for this slug.', 'hoeveel-zijn-er-nog' ),
				array( 'status' => 404 )
			);
		}

		$to   = $request->get_param( 'to' )   ?: gmdate( 'Y-m-d' );
		$from = $request->get_param( 'from' ) ?: gmdate( 'Y-m-d', strtotime( '-12 months' ) );

		// Sanitize dates.
		$from = $this->sanitize_date( $from );
		$to   = $this->sanitize_date( $to );

		$rows   = Snapshots_Repo::for_model( (int) $model['id'], $from, $to );
		$metric = $request->get_param( 'metric' ) ?: 'both';

		$data = array_map(
			function ( $row ) use ( $metric ) {
				$item = array( 'date' => $row['snapshot_date'] );
				if ( in_array( $metric, array( 'total', 'both' ), true ) ) {
					$item['total'] = (int) $row['total'];
				}
				if ( in_array( $metric, array( 'apk_valid', 'both' ), true ) ) {
					$item['apk_valid'] = (int) $row['apk_valid'];
				}
				return $item;
			},
			$rows
		);

		return rest_ensure_response(
			array(
				'model'     => array(
					'id'    => (int) $model['id'],
					'slug'  => $model['slug'],
					'label' => $model['label'],
				),
				'from'      => $from,
				'to'        => $to,
				'snapshots' => $data,
			)
		);
	}

	/**
	 * Define the accepted query parameters.
	 */
	public function get_collection_params(): array {
		return array(
			'slug'   => array(
				'description'       => __( 'Tracked model slug.', 'hoeveel-zijn-er-nog' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_title',
			),
			'from'   => array(
				'description'       => __( 'Start date (YYYY-MM-DD).', 'hoeveel-zijn-er-nog' ),
				'type'              => 'string',
				'format'            => 'date',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'to'     => array(
				'description'       => __( 'End date (YYYY-MM-DD).', 'hoeveel-zijn-er-nog' ),
				'type'              => 'string',
				'format'            => 'date',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'metric' => array(
				'description'       => __( 'Metric to return: total, apk_valid, or both.', 'hoeveel-zijn-er-nog' ),
				'type'              => 'string',
				'enum'              => array( 'total', 'apk_valid', 'both' ),
				'default'           => 'both',
				'sanitize_callback' => 'sanitize_key',
			),
		);
	}

	/**
	 * Ensure a date string is YYYY-MM-DD; falls back to today.
	 */
	private function sanitize_date( string $date ): string {
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return $date;
		}
		return gmdate( 'Y-m-d' );
	}
}
