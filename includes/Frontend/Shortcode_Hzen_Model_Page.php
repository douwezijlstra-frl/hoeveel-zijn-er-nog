<?php
namespace Hoeveel_Zijn_Er_Nog\Frontend;

use Hoeveel_Zijn_Er_Nog\Db\Tracked_Models_Repo;
use Hoeveel_Zijn_Er_Nog\Db\Snapshots_Repo;
use Hoeveel_Zijn_Er_Nog\Services\Stats_Provider;
use Hoeveel_Zijn_Er_Nog\Services\License_Plate_Formatter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * [hzen_model_page slug=] — full per-model dashboard.
 *
 * Renders: current stats grid + 12-month history chart + latest snapshot info.
 * The user places this shortcode on any WP page of their choosing.
 *
 * Attributes:
 *   slug   (string, required) — tracked model slug.
 *   range  (string) — history range (default 12m).
 *   metric (string) — history metric (default total).
 */
class Shortcode_Hzen_Model_Page {

	/** @var Stats_Provider */
	private $provider;

	public function __construct( Stats_Provider $provider ) {
		$this->provider = $provider;
	}

	public function register(): void {
		add_shortcode( 'hzen_model_page', array( $this, 'render' ) );
	}

	/**
	 * @param array|string $atts
	 * @return string
	 */
	public function render( $atts ): string {
		$atts = shortcode_atts(
			array(
				'slug'   => '',
				'range'  => '12m',
				'metric' => 'total',
			),
			$atts,
			'hzen_model_page'
		);

		$slug = sanitize_title( $atts['slug'] );
		if ( ! $slug ) {
			return '<p class="hzen-error">' . esc_html__( 'Please provide a model slug.', 'hoeveel-zijn-er-nog' ) . '</p>';
		}

		$model = Tracked_Models_Repo::find_by_slug( $slug );
		if ( ! $model ) {
			return '<p class="hzen-error">' . esc_html__( 'Model not found.', 'hoeveel-zijn-er-nog' ) . '</p>';
		}

		$criteria = array(
			'merk'            => $model['merk'],
			'handelsbenaming' => $model['handelsbenaming'],
			'voertuigsoort'   => $model['voertuigsoort'],
			'inrichting'      => $model['inrichting'],
		);

		$stats  = $this->provider->get_stats( $criteria );
		$metric = in_array( $atts['metric'], array( 'total', 'apk_valid', 'both' ), true )
			? $atts['metric']
			: 'total';

		// Delegate the history chart to [hzen_history] shortcode.
		$history_sc = '[hzen_history'
			. ' merk="' . esc_attr( $model['merk'] ) . '"'
			. ' handelsbenaming="' . esc_attr( $model['handelsbenaming'] ) . '"'
			. ' voertuigsoort="' . esc_attr( $model['voertuigsoort'] ) . '"'
			. ' inrichting="' . esc_attr( $model['inrichting'] ) . '"'
			. ' range="' . esc_attr( $atts['range'] ) . '"'
			. ' metric="' . esc_attr( $metric ) . '"'
			. ']';

		$oldest = $stats['oldest'];
		$newest = $stats['newest'];

		ob_start();
		?>
		<div class="hzen-model-page">
			<h2 class="hzen-model-title"><?php echo esc_html( $model['label'] ); ?></h2>

			<div class="rdw-stats-grid">
				<div class="rdw-stat-item">
					<span class="rdw-stat-label"><?php esc_html_e( 'Geregistreerd', 'hoeveel-zijn-er-nog' ); ?></span>
					<span class="rdw-stat-value"><?php echo esc_html( number_format_i18n( (int) $stats['total'] ) ); ?></span>
				</div>
				<div class="rdw-stat-item">
					<span class="rdw-stat-label"><?php esc_html_e( 'Geldige APK', 'hoeveel-zijn-er-nog' ); ?></span>
					<span class="rdw-stat-value"><?php echo esc_html( number_format_i18n( (int) $stats['apk_valid'] ) ); ?></span>
				</div>
				<div class="rdw-stat-item">
					<span class="rdw-stat-label"><?php esc_html_e( 'Oudste', 'hoeveel-zijn-er-nog' ); ?></span>
					<span class="rdw-stat-value">
						<?php if ( $oldest ) : ?>
							<?php echo esc_html( License_Plate_Formatter::format( $oldest['kenteken'] ) ); ?><br>
							<small><?php echo esc_html( $oldest['datum_eerste_toelating'] ); ?></small>
						<?php else : ?>
							<?php echo esc_html_x( 'N/A', 'no data', 'hoeveel-zijn-er-nog' ); ?>
						<?php endif; ?>
					</span>
				</div>
				<div class="rdw-stat-item">
					<span class="rdw-stat-label"><?php esc_html_e( 'Nieuwste', 'hoeveel-zijn-er-nog' ); ?></span>
					<span class="rdw-stat-value">
						<?php if ( $newest ) : ?>
							<?php echo esc_html( License_Plate_Formatter::format( $newest['kenteken'] ) ); ?><br>
							<small><?php echo esc_html( $newest['datum_eerste_toelating'] ); ?></small>
						<?php else : ?>
							<?php echo esc_html_x( 'N/A', 'no data', 'hoeveel-zijn-er-nog' ); ?>
						<?php endif; ?>
					</span>
				</div>
			</div>

			<div class="hzen-model-history">
				<h3><?php esc_html_e( 'Historical trend', 'hoeveel-zijn-er-nog' ); ?></h3>
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo do_shortcode( $history_sc );
				?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
