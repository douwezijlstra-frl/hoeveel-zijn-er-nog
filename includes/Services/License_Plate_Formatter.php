<?php
namespace Hoeveel_Zijn_Er_Nog\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class License_Plate_Formatter {

	private static $patterns = array(
		'/^([A-Z]{2})(\d{2})(\d{2})$/',     // 1
		'/^(\d{2})(\d{2})([A-Z]{2})$/',     // 2
		'/^(\d{2})([A-Z]{2})(\d{2})$/',     // 3
		'/^([A-Z]{2})(\d{2})([A-Z]{2})$/',  // 4
		'/^([A-Z]{2})([A-Z]{2})(\d{2})$/',  // 5
		'/^(\d{2})([A-Z]{2})([A-Z]{2})$/',  // 6
		'/^(\d{2})([A-Z]{3})(\d{1})$/',     // 7
		'/^(\d{1})([A-Z]{3})(\d{2})$/',     // 8
		'/^([A-Z]{2})(\d{3})([A-Z]{1})$/',  // 9
		'/^([A-Z]{1})(\d{3})([A-Z]{2})$/',  // 10
		'/^([A-Z]{3})(\d{2})([A-Z]{1})$/',  // 11
		'/^([A-Z]{1})(\d{2})([A-Z]{3})$/',  // 12
		'/^(\d{1})([A-Z]{2})(\d{3})$/',     // 13
		'/^(\d{3})([A-Z]{2})(\d{1})$/',     // 14
	);

	public static function format( $kenteken ) {
		$kenteken = strtoupper( preg_replace( '/[^A-Z0-9]/i', '', (string) $kenteken ) );

		foreach ( self::$patterns as $pattern ) {
			if ( preg_match( $pattern, $kenteken, $matches ) ) {
				array_shift( $matches );
				return implode( '-', $matches );
			}
		}

		if ( preg_match_all( '/[A-Z]+|\d+/', $kenteken, $matches ) ) {
			return implode( '-', $matches[0] );
		}

		return $kenteken;
	}
}
