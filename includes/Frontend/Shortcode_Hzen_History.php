<?php
namespace Hoeveel_Zijn_Er_Nog\Frontend;

use Hoeveel_Zijn_Er_Nog\Services\Stats_Provider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * [hzen_history] — renders a Chart.js line chart of historical snapshots.
 *
 * Attributes:
 *   merk            (string)  RDW brand name.
 *   handelsbenaming (string)  RDW model name.
 *   voertuigsoort   (string)  Default P.
 *   inrichting      (string)  Optional body-style filter.
 *   range           (string)  Date range: 3m, 6m, 12m (default), 24m, all.
 *   metric          (string)  total (default), apk_valid, or both.
 *   height          (int)     Canvas height attribute (default 80).
 */
class Shortcode_Hzen_History {

	/** @var Stats_Provider */
	private $provider;

	public function __construct( Stats_Provider $provider ) {
		$this->provider = $provider;
	}

	public function register(): void {
		add_shortcode( 'hzen_history', array( $this, 'render' ) );
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array|string $atts
	 * @return string
	 */
	public function render( $atts ): string {
		$atts = shortcode_atts(
			array(
				'merk'            => '',
				'handelsbenaming' => '',
				'voertuigsoort'   => 'P',
				'inrichting'      => '',
				'range'           => '12m',
				'metric'          => 'total',
				'height'          => 80,
			),
			$atts,
			'hzen_history'
		);

		$criteria = array(
			'merk'            => sanitize_text_field( $atts['merk'] ),
			'handelsbenaming' => sanitize_text_field( $atts['handelsbenaming'] ),
			'voertuigsoort'   => sanitize_text_field( $atts['voertuigsoort'] ),
			'inrichting'      => sanitize_text_field( $atts['inrichting'] ),
		);

		list( $from, $to ) = $this->parse_range( sanitize_text_field( $atts['range'] ) );

		$metric = in_array( $atts['metric'], array( 'total', 'apk_valid', 'both' ), true )
			? $atts['metric']
			: 'total';

		$history = $this->provider->get_history( $criteria, $from, $to );

		if ( empty( $history ) ) {
			return '<p class="hzen-no-data">' . esc_html__( 'No historical data available yet.', 'hoeveel-zijn-er-nog' ) . '</p>';
		}

		// Enqueue Chart.js now that we know we'll render a chart.
		$this->enqueue_chartjs();

		$canvas_id = 'hzen-history-' . wp_unique_id();
		$height    = max( 40, (int) $atts['height'] );

		$labels   = array_column( $history, 'snapshot_date' );
		$datasets = $this->build_datasets( $history, $metric );

		$chart_data = wp_json_encode(
			array(
				'labels'   => $labels,
				'datasets' => $datasets,
			)
		);

		ob_start();
		?>
		<div class="hzen-history-wrap">
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
	 * Parse a range string (3m, 6m, 12m, 24m, all) into [from, to] date pair.
	 *
	 * @return array{0:string, 1:string}
	 */
	private function parse_range( string $range ): array {
		$to = gmdate( 'Y-m-d' );

		if ( 'all' === $range ) {
			$from = '2000-01-01';
		} else {
			$months = (int) rtrim( $range, 'm' );
			$months = max( 1, min( 120, $months ) );
			$from   = gmdate( 'Y-m-d', strtotime( "-{$months} months" ) );
		}

		return array( $from, $to );
	}

	/**
	 * Build Chart.js dataset objects for the given metric.
	 *
	 * @param array  $history Snapshot rows.
	 * @param string $metric  total | apk_valid | both.
	 * @return array<int, array<string, mixed>>
	 */
	private function build_datasets( array $history, string $metric ): array {
		$datasets = array();

		if ( in_array( $metric, array( 'total', 'both' ), true ) ) {
			$datasets[] = array(
				'label'           => __( 'Total', 'hoeveel-zijn-er-nog' ),
				'data'            => array_map( fn( $r ) => (int) $r['total'], $history ),
				'borderColor'     => '#0073aa',
				'backgroundColor' => 'rgba(0,115,170,0.1)',
				'fill'            => true,
				'tension'         => 0.3,
			);
		}

		if ( in_array( $metric, array( 'apk_valid', 'both' ), true ) ) {
			$datasets[] = array(
				'label'           => __( 'Valid APK', 'hoeveel-zijn-er-nog' ),
				'data'            => array_map( fn( $r ) => (int) $r['apk_valid'], $history ),
				'borderColor'     => '#00a32a',
				'backgroundColor' => 'rgba(0,163,42,0.08)',
				'fill'            => true,
				'tension'         => 0.3,
			);
		}

		return $datasets;
	}

	/**
	 * Enqueue Chart.js (bundled vendor file).
	 */
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
