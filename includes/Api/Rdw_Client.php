<?php
namespace Hoeveel_Zijn_Er_Nog\Api;

use Hoeveel_Zijn_Er_Nog\Cache\Cache;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rdw_Client {

	const API_URL = 'https://opendata.rdw.nl/resource/m9d7-ebf2.json';

	private $cache;

	public function __construct( Cache $cache ) {
		$this->cache = $cache;
	}

	public function query( array $args, $context = 'rdw_query' ) {
		$cache_key = $context . '_' . md5( wp_json_encode( $args ) );
		$cached    = $this->cache->get( $cache_key, $context );
		if ( false !== $cached ) {
			return $cached;
		}

		$url      = add_query_arg( array_map( 'rawurlencode', $args ), self::API_URL );
		$url      = str_replace( '%24', '$', $url );
		$defaults = array(
			'timeout'    => 8,
			'redirection' => 1,
			'sslverify'  => true,
			'user-agent' => 'HoeveelZijnErNog/' . HOEVEEL_ZIJN_ER_NOG_VERSION . '; ' . home_url( '/' ),
			'headers'    => array(),
		);

		$token = (string) apply_filters( 'hzen_rdw_app_token', '' );
		if ( $token ) {
			$defaults['headers']['X-App-Token'] = $token;
		}

		$request_args = (array) apply_filters( 'hzen_rdw_request_args', $defaults, $args, $context );

		$response = wp_remote_get( $url, $request_args );
		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
			return false;
		}

		$this->cache->set( $cache_key, $data, $context );
		return $data;
	}
}
