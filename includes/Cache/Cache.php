<?php
namespace Hoeveel_Zijn_Er_Nog\Cache;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cache {

	const INDEX_OPTION = 'hzen_cache_index';

	public function get( $key, $context = '' ) {
		if ( $this->bypass( $context ) ) {
			return false;
		}
		return get_transient( $this->build_key( $key ) );
	}

	public function set( $key, $value, $context = '' ) {
		$ttl = (int) apply_filters( 'hzen_cache_ttl', DAY_IN_SECONDS, $context, $key );
		if ( $ttl <= 0 ) {
			return false;
		}
		$full = $this->build_key( $key );
		set_transient( $full, $value, $ttl );
		$this->remember( $full );
		return true;
	}

	public function flush() {
		$index = (array) get_option( self::INDEX_OPTION, array() );
		foreach ( $index as $full_key ) {
			delete_transient( $full_key );
		}
		delete_option( self::INDEX_OPTION );
	}

	private function bypass( $context ) {
		$default = defined( 'WP_DEBUG' ) && WP_DEBUG;
		return (bool) apply_filters( 'hzen_cache_bypass', $default, $context );
	}

	private function build_key( $key ) {
		return 'hzen_v' . HOEVEEL_ZIJN_ER_NOG_VERSION . '_' . $key;
	}

	private function remember( $full_key ) {
		$index = (array) get_option( self::INDEX_OPTION, array() );
		if ( ! in_array( $full_key, $index, true ) ) {
			$index[] = $full_key;
			update_option( self::INDEX_OPTION, $index, false );
		}
	}
}
