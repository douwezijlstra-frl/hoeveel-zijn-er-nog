<?php
namespace Hoeveel_Zijn_Er_Nog\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Soql_Query_Builder {

	const ALLOWED_FIELDS = array( 'merk', 'handelsbenaming', 'voertuigsoort', 'inrichting' );

	public static function escape_string( $value ) {
		$value = (string) $value;
		$value = preg_replace( '/[\x00-\x1F\x7F]/u', '', $value );
		return str_replace( "'", "''", $value );
	}

	public static function allowed_fields() {
		return (array) apply_filters( 'hzen_allowed_fields', self::ALLOWED_FIELDS );
	}

	public static function build_where( array $criteria ) {
		$allowed    = self::allowed_fields();
		$conditions = array();

		foreach ( $allowed as $field ) {
			if ( empty( $criteria[ $field ] ) ) {
				continue;
			}
			$value        = strtoupper( sanitize_text_field( $criteria[ $field ] ) );
			$value        = self::escape_string( $value );
			$field_clean  = preg_replace( '/[^a-z_]/', '', strtolower( $field ) );
			$conditions[] = "starts_with({$field_clean}, '{$value}')";
		}

		$where = $conditions ? implode( ' AND ', $conditions ) : '1=1';
		return (string) apply_filters( 'hzen_soql_where', $where, $criteria );
	}
}
