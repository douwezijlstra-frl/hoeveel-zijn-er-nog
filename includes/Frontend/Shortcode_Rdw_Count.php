<?php
namespace Hoeveel_Zijn_Er_Nog\Frontend;

use Hoeveel_Zijn_Er_Nog\Services\Stats_Provider;
use Hoeveel_Zijn_Er_Nog\Services\License_Plate_Formatter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcode_Rdw_Count {

	private $provider;

	public function __construct( Stats_Provider $provider ) {
		$this->provider = $provider;
	}

	public function register() {
		add_shortcode( 'rdw_count', array( $this, 'render' ) );
	}

	public function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'merk'            => '',
				'handelsbenaming' => '',
				'voertuigsoort'   => 'P',
				'inrichting'      => '',
			),
			$atts,
			'rdw_count'
		);
		$atts = (array) apply_filters( 'hzen_shortcode_atts_rdw_count', $atts );

		$stats = $this->provider->get_stats( $atts );

		$oldest = $stats['oldest'];
		$newest = $stats['newest'];

		$vars = array(
			'total'        => (int) $stats['total'],
			'apk_valid'    => (int) $stats['apk_valid'],
			'oldest_plate' => $oldest ? License_Plate_Formatter::format( $oldest['kenteken'] ) : '',
			'oldest_date'  => $oldest ? self::format_date( $oldest['datum_eerste_toelating'] ) : '',
			'newest_plate' => $newest ? License_Plate_Formatter::format( $newest['kenteken'] ) : '',
			'newest_date'  => $newest ? self::format_date( $newest['datum_eerste_toelating'] ) : '',
		);

		return Template_Loader::render( 'rdw-count', $vars );
	}

	private static function format_date( $date_string ) {
		if ( empty( $date_string ) || strlen( $date_string ) !== 8 ) {
			return (string) $date_string;
		}
		return substr( $date_string, 6, 2 ) . '-' . substr( $date_string, 4, 2 ) . '-' . substr( $date_string, 0, 4 );
	}
}
