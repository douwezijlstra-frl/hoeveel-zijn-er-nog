<?php
/**
 * Class Hoeveel_Zijn_Er_Nog_Shortcode
 *
 * Handles the registration and rendering of the [rdw_count] shortcode.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Hoeveel_Zijn_Er_Nog_Shortcode {

	/**
	 * RDW Service instance.
	 *
	 * @var Hoeveel_Zijn_Er_Nog_Rdw_Service
	 */
	private $service;

	/**
	 * Constructor.
	 *
	 * @param Hoeveel_Zijn_Er_Nog_Rdw_Service $service Service instance.
	 */
	public function __construct( $service ) {
		$this->service = $service;
	}

	/**
	 * Register the shortcode.
	 */
	public function register() {
		add_shortcode( 'rdw_count', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Format a date from YYYYMMDD to human readable.
	 *
	 * @param string $date_string Date string (YYYYMMDD).
	 * @return string Formatted date (d-m-Y).
	 */
	private function format_date( $date_string ) {
		if ( empty( $date_string ) || strlen( $date_string ) !== 8 ) {
			return $date_string;
		}

		$year  = substr( $date_string, 0, 4 );
		$month = substr( $date_string, 4, 2 );
		$day   = substr( $date_string, 6, 2 );

		return "{$day}-{$month}-{$year}";
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'merk'            => '',
				'handelsbenaming' => '',
				'voertuigsoort'   => 'P',
			),
			$atts,
			'rdw_count'
		);

		// Get data from service.
		$total_count = $this->service->get_counts( $atts );
		$apk_count   = $this->service->get_valid_apk_count( $atts );
		$oldest      = $this->service->get_oldest_vehicle( $atts );
		$newest      = $this->service->get_newest_vehicle( $atts );

		// Prepare display data.
		$oldest_text = $oldest 
			? esc_html( $this->service->format_license_plate( $oldest['kenteken'] ) ) . '<br><small>' . esc_html( $this->format_date( $oldest['datum_eerste_toelating'] ) ) . '</small>' 
			: 'N/A';
		
		$newest_text = $newest 
			? esc_html( $this->service->format_license_plate( $newest['kenteken'] ) ) . '<br><small>' . esc_html( $this->format_date( $newest['datum_eerste_toelating'] ) ) . '</small>' 
			: 'N/A';

		ob_start();
		?>
		<div class="hoeveel-zijn-er-nog-wrapper">
			<div class="rdw-stats-grid">
				<div class="rdw-stat-item">
					<span class="rdw-stat-label"><?php esc_html_e( 'Geregistreerd', 'hoeveel-zijn-er-nog' ); ?></span>
					<span class="rdw-stat-value"><?php echo esc_html( $total_count ); ?></span>
				</div>
				<div class="rdw-stat-item">
					<span class="rdw-stat-label"><?php esc_html_e( 'Geldige APK', 'hoeveel-zijn-er-nog' ); ?></span>
					<span class="rdw-stat-value"><?php echo esc_html( number_format_i18n( $apk_count ) ); ?></span>
				</div>
				<div class="rdw-stat-item">
					<span class="rdw-stat-label"><?php esc_html_e( 'Oudste', 'hoeveel-zijn-er-nog' ); ?></span>
					<span class="rdw-stat-value"><?php echo $oldest_text; // Already escaped above but contains HTML for line break ?></span>
				</div>
				<div class="rdw-stat-item">
					<span class="rdw-stat-label"><?php esc_html_e( 'Nieuwste', 'hoeveel-zijn-er-nog' ); ?></span>
					<span class="rdw-stat-value"><?php echo $newest_text; // Already escaped above but contains HTML for line break ?></span>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
