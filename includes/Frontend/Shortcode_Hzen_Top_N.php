<?php
namespace Hoeveel_Zijn_Er_Nog\Frontend;

use Hoeveel_Zijn_Er_Nog\Db\Tracked_Models_Repo;
use Hoeveel_Zijn_Er_Nog\Db\Snapshots_Repo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * [hzen_top_n] — leaderboard of tracked models ordered by a metric.
 *
 * Attributes:
 *   metric        (string) total (default) | apk_valid.
 *   limit         (int)    Number of rows to show (default 10, max 50).
 *   voertuigsoort (string) Filter by voertuigsoort (optional).
 */
class Shortcode_Hzen_Top_N {

	public function register(): void {
		add_shortcode( 'hzen_top_n', array( $this, 'render' ) );
	}

	/**
	 * Render the leaderboard.
	 *
	 * @param array|string $atts
	 * @return string
	 */
	public function render( $atts ): string {
		$atts = shortcode_atts(
			array(
				'metric'        => 'total',
				'limit'         => 10,
				'voertuigsoort' => '',
			),
			$atts,
			'hzen_top_n'
		);

		$metric = in_array( $atts['metric'], array( 'total', 'apk_valid' ), true )
			? $atts['metric']
			: 'total';

		$limit         = max( 1, min( 50, (int) $atts['limit'] ) );
		$voertuigsoort = strtoupper( sanitize_text_field( $atts['voertuigsoort'] ) );

		$models = Tracked_Models_Repo::all( true );

		if ( $voertuigsoort ) {
			$models = array_filter(
				$models,
				fn( $m ) => $m['voertuigsoort'] === $voertuigsoort
			);
		}

		// Fetch latest snapshot for each model and sort.
		$rows = array();
		foreach ( $models as $model ) {
			$snap = Snapshots_Repo::latest( (int) $model['id'] );
			if ( $snap ) {
				$rows[] = array(
					'label'  => $model['label'],
					'slug'   => $model['slug'],
					'value'  => (int) $snap[ $metric ],
					'date'   => $snap['snapshot_date'],
				);
			}
		}

		usort( $rows, fn( $a, $b ) => $b['value'] <=> $a['value'] );

		$rows = array_slice( $rows, 0, $limit );

		if ( empty( $rows ) ) {
			return '<p class="hzen-no-data">' . esc_html__( 'No data available yet.', 'hoeveel-zijn-er-nog' ) . '</p>';
		}

		$metric_label = 'apk_valid' === $metric
			? __( 'Valid APK', 'hoeveel-zijn-er-nog' )
			: __( 'Total', 'hoeveel-zijn-er-nog' );

		ob_start();
		?>
		<div class="hzen-top-n-wrap">
			<table class="hzen-top-n-table">
				<thead>
					<tr>
						<th class="hzen-rank">#</th>
						<th class="hzen-model"><?php esc_html_e( 'Model', 'hoeveel-zijn-er-nog' ); ?></th>
						<th class="hzen-value"><?php echo esc_html( $metric_label ); ?></th>
						<th class="hzen-date"><?php esc_html_e( 'As of', 'hoeveel-zijn-er-nog' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $i => $row ) : ?>
						<tr>
							<td class="hzen-rank"><?php echo esc_html( $i + 1 ); ?></td>
							<td class="hzen-model"><?php echo esc_html( $row['label'] ); ?></td>
							<td class="hzen-value"><?php echo esc_html( number_format_i18n( $row['value'] ) ); ?></td>
							<td class="hzen-date"><?php echo esc_html( $row['date'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		return ob_get_clean();
	}
}
