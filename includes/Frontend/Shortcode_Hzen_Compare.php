<?php
namespace Hoeveel_Zijn_Er_Nog\Frontend;

use Hoeveel_Zijn_Er_Nog\Db\Tracked_Models_Repo;
use Hoeveel_Zijn_Er_Nog\Db\Snapshots_Repo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * [hzen_compare] — multi-line Chart.js chart comparing multiple models.
 *
 * Attributes:
 *   models  (string) Comma-separated list of model slugs.
 *   metric  (string) total (default) | apk_valid.
 *   range   (string) 3m, 6m, 12m (default), 24m, all.
 *   height  (int)    Canvas height (default 80).
 */
class Shortcode_Hzen_Compare {

	/** Distinct colours for up to 10 series. */
	const COLORS = array(
		'#0073aa', '#00a32a', '#d63638', '#dba617',
		'#3858e9', '#1d2327', '#72aee6', '#68de7c',
		'#f86368', '#f0c33c',
	);

	public function register(): void {
		add_shortcode( 'hzen_compare', array( $this, 'render' ) );
	}

	/**
	 * @param array|string $atts
	 * @return string
	 */
	public function render( $atts ): string {
		$atts = shortcode_atts(
			array(
				'models' => '',
				'metric' => 'total',
				'range'  => '12m',
				'height' => 80,
			),
			$atts,
			'hzen_compare'
		);

		$slugs = array_filter( array_map( 'sanitize_title', explode( ',', $atts['models'] ) ) );
		if ( empty( $slugs ) ) {
			return '<p class="hzen-error">' . esc_html__( 'Please provide at least one model slug in the models= attribute.', 'hoeveel-zijn-er-nog' ) . '</p>';
		}

		$metric = in_array( $atts['metric'], array( 'total', 'apk_valid' ), true )
			? $atts['metric']
			: 'total';

		list( $from, $to ) = $this->parse_range( sanitize_text_field( $atts['range'] ) );
		$height = max( 40, (int) $atts['height'] );

		// Collect all dates across models for a unified X axis.
		$all_dates = array();
		$series    = array();

		foreach ( array_values( $slugs ) as $idx => $slug ) {
			$model = Tracked_Models_Repo::find_by_slug( $slug );
			if ( ! $model ) {
				continue;
			}

			$rows        = Snapshots_Repo::for_model( (int) $model['id'], $from, $to );
			$keyed       = array();
			foreach ( $rows as $row ) {
				$keyed[ $row['snapshot_date'] ] = (int) $row[ $metric ];
				$all_dates[]                    = $row['snapshot_date'];
			}

			$color    = self::COLORS[ $idx % count( self::COLORS ) ];
			$series[] = array(
				'label'  => $model['label'],
				'keyed'  => $keyed,
				'color'  => $color,
			);
		}

		if ( empty( $series ) ) {
			return '<p class="hzen-no-data">' . esc_html__( 'No data found for the specified models.', 'hoeveel-zijn-er-nog' ) . '</p>';
		}

		$all_dates = array_values( array_unique( $all_dates ) );
		sort( $all_dates );

		// Build datasets; fill null for missing dates.
		$datasets = array();
		foreach ( $series as $s ) {
			$data = array_map(
				fn( $d ) => isset( $s['keyed'][ $d ] ) ? $s['keyed'][ $d ] : null,
				$all_dates
			);
			$datasets[] = array(
				'label'                => $s['label'],
				'data'                 => $data,
				'borderColor'          => $s['color'],
				'backgroundColor'      => 'transparent',
				'spanGaps'             => true,
				'tension'              => 0.3,
			);
		}

		$this->enqueue_chartjs();

		$canvas_id  = 'hzen-compare-' . wp_unique_id();
		$chart_data = wp_json_encode(
			array(
				'labels'   => $all_dates,
				'datasets' => $datasets,
			)
		);

		ob_start();
		?>
		<div class="hzen-compare-wrap">
			<canvas id="<?php echo esc_attr( $canvas_id ); ?>" height="<?php echo esc_attr( $height ); ?>"></canvas>
		</div>
		<script>
		(function() {
			document.addEventListener('DOMContentLoaded', function() {
				var ctx = document.getElementById(<?php echo wp_json_encode( $canvas_id ); ?>);
				if (!ctx || typeof Chart === 'undefined') return;
				new Chart(ctx, {
					type: 'line',
					data: <?php echo $chart_data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>,
					options: {
						responsive: true,
						plugins: { legend: { position: 'top' } },
						scales: { y: { beginAtZero: false } }
					}
				});
			});
		})();
		</script>
		<?php
		return ob_get_clean();
	}

	/**
	 * Parse a range string into [from, to].
	 *
	 * @return array{0:string, 1:string}
	 */
	private function parse_range( string $range ): array {
		$to = gmdate( 'Y-m-d' );
		if ( 'all' === $range ) {
			return array( '2000-01-01', $to );
		}
		$months = max( 1, min( 120, (int) rtrim( $range, 'm' ) ) );
		return array( gmdate( 'Y-m-d', strtotime( "-{$months} months" ) ), $to );
	}

	private function enqueue_chartjs(): void {
		if ( ! wp_script_is( 'hzen-chartjs', 'enqueued' ) ) {
			wp_enqueue_script(
				'hzen-chartjs',
				HOEVEEL_ZIJN_ER_NOG_PLUGIN_URL . 'assets/vendor/chart.umd.js',
				array(),
				'4.4.0',
				true
			);
		}
	}
}
