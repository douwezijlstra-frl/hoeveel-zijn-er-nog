<?php
namespace Hoeveel_Zijn_Er_Nog\Frontend;

use Hoeveel_Zijn_Er_Nog\Services\Stats_Provider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * [hzen_count] — alias of [rdw_count].
 *
 * Accepts identical attributes: merk, handelsbenaming, voertuigsoort, inrichting.
 */
class Shortcode_Hzen_Count {

	/** @var Stats_Provider */
	private $provider;

	public function __construct( Stats_Provider $provider ) {
		$this->provider = $provider;
	}

	public function register(): void {
		add_shortcode( 'hzen_count', array( $this, 'render' ) );
	}

	/**
	 * Delegates entirely to Shortcode_Rdw_Count::render().
	 *
	 * @param array|string $atts
	 * @return string
	 */
	public function render( $atts ): string {
		// Reuse the existing rdw_count shortcode handler.
		return do_shortcode( '[rdw_count ' . $this->atts_to_string( (array) $atts ) . ']' );
	}

	/**
	 * Convert attribute array back to a shortcode attribute string.
	 */
	private function atts_to_string( array $atts ): string {
		$parts = array();
		foreach ( $atts as $k => $v ) {
			if ( is_int( $k ) ) {
				$parts[] = esc_attr( $v );
			} else {
				$parts[] = esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
			}
		}
		return implode( ' ', $parts );
	}
}
