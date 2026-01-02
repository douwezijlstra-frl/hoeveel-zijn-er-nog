<?php
/**
 * Class Hoeveel_Zijn_Er_Nog_Rdw_Service
 *
 * Handles communication with the RDW Open Data API.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Hoeveel_Zijn_Er_Nog_Rdw_Service {

	/**
	 * API Endpoint URL.
	 */
	const API_URL = 'https://opendata.rdw.nl/resource/m9d7-ebf2.json';

	/**
	 * Fetch data from the RDW API with caching.
	 *
	 * @param array $args Query arguments (SoQL).
	 * @return mixed API response or false on failure.
	 */
	private function fetch_api( $args ) {
		// Create a unique cache key based on the arguments.
		$cache_key = 'rdw_' . md5( wp_json_encode( $args ) );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached && ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) ) {
			return $cached;
		}

		$url = add_query_arg( $args, self::API_URL );
		
		// Log the API request for debugging
		error_log( 'RDW API Request: ' . $url );

		// Add an app token header if available (optional but recommended for higher limits).
		// For now, we'll go without, or add a filter for it.
		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			error_log( 'RDW API Error: ' . $response->get_error_message() );
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		error_log( 'RDW API Response Code: ' . $response_code );

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		
		if (json_last_error() !== JSON_ERROR_NONE) {
			error_log( 'RDW API JSON Decode Error: ' . json_last_error_msg() );
			// Also log body excerpt if possible to see what came back (could be HTML error page)
			error_log( 'RDW API Raw Body (first 200 chars): ' . substr( $body, 0, 200 ) );
		}

		// Cache for 24 hours.
		set_transient( $cache_key, $data, 24 * HOUR_IN_SECONDS );

		return $data;
	}

	/**
	 * Build base filters from user criteria.
	 *
	 * @param array $criteria User defined criteria (merk, handelsbenaming, etc).
	 * @return string SoQL where clause.
	 */
	private function build_where_clause( $criteria ) {
		$conditions = array();

		// Map friendly names to API fields if necessary, or just use direct keys.
		$allowed_fields = array( 'merk', 'handelsbenaming', 'voertuigsoort', 'inrichting' );

		foreach ( $allowed_fields as $field ) {
			if ( ! empty( $criteria[ $field ] ) ) {
				// Upper case is safer for some text fields in this API.
				$value = strtoupper( sanitize_text_field( $criteria[ $field ] ) );
				// Use starts_with for partial matches as requested.
				$conditions[] = "starts_with({$field}, '{$value}')";
			}
		}

		return ! empty( $conditions ) ? implode( ' AND ', $conditions ) : '1=1'; // Fallback to all if empty.
	}

	/**
	 * Get total count of vehicles matching criteria.
	 *
	 * @param array $criteria Filter criteria.
	 * @return int Count.
	 */
	public function get_counts( $criteria ) {
		$where = $this->build_where_clause( $criteria );
		$args  = array(
			'$select' => 'count(kenteken)',
			'$where'  => $where,
		);

		$data = $this->fetch_api( $args );

		if ( ! empty( $data ) && isset( $data[0]['count_kenteken'] ) ) {
			return (int) $data[0]['count_kenteken'];
		}

		return 0;
	}

	/**
	 * Get count of vehicles with a valid APK.
	 *
	 * @param array $criteria Filter criteria.
	 * @return int Count.
	 */
	public function get_valid_apk_count( $criteria ) {
		$where_base = $this->build_where_clause( $criteria );
		
		// APK valid means vervaldatum_apk date is in the future.
		// Format in API is YYYYMMDD without dashes usually, but let's check. 
		// Sample showed "20260920".
		$today = date( 'Ymd' );
		$where = "{$where_base} AND vervaldatum_apk >= '{$today}'";

		$args = array(
			'$select' => 'count(kenteken)',
			'$where'  => $where,
		);

		$data = $this->fetch_api( $args );

		if ( ! empty( $data ) && isset( $data[0]['count_kenteken'] ) ) {
			return (int) $data[0]['count_kenteken'];
		}

		return 0;
	}

	/**
	 * Get the oldest vehicle.
	 *
	 * @param array $criteria Filter criteria.
	 * @return array|null Vehicle data or null.
	 */
	public function get_oldest_vehicle( $criteria ) {
		$where = $this->build_where_clause( $criteria );
		$args  = array(
			'$where' => $where,
			'$order' => 'datum_eerste_toelating ASC',
			'$limit' => 1,
		);

		$data = $this->fetch_api( $args );

		return ( ! empty( $data ) ) ? $data[0] : null;
	}

	/**
	 * Get the newest vehicle.
	 *
	 * @param array $criteria Filter criteria.
	 * @return array|null Vehicle data or null.
	 */
	public function get_newest_vehicle( $criteria ) {
		$where = $this->build_where_clause( $criteria );
		$args  = array(
			'$where' => $where,
			'$order' => 'datum_eerste_toelating DESC',
			'$limit' => 1,
		);

		$data = $this->fetch_api( $args );

		return ( ! empty( $data ) ) ? $data[0] : null;
	}
	/**
	 * Format a license plate string into Dutch standard formats (Sidecodes 1-14).
	 *
	 * @param string $kenteken Raw license plate string (e.g., PH29RZ).
	 * @return string Formatted license plate (e.g., PH-29-RZ) or original if no match.
	 */
	public function format_license_plate( $kenteken ) {
		$kenteken = strtoupper( preg_replace( '/[^A-Z0-9]/i', '', $kenteken ) );
		$length   = strlen( $kenteken );

		// Sidecode patterns (roughly ordered by commonality and specificity)
		$patterns = array(
			// Sidecode 1: XX-99-99
			'/^([A-Z]{2})(\d{2})(\d{2})$/',
			// Sidecode 2: 99-99-XX
			'/^(\d{2})(\d{2})([A-Z]{2})$/',
			// Sidecode 3: 99-XX-99
			'/^(\d{2})([A-Z]{2})(\d{2})$/',
			// Sidecode 4: XX-99-XX
			'/^([A-Z]{2})(\d{2})([A-Z]{2})$/',
			// Sidecode 5: XX-XX-99
			'/^([A-Z]{2})([A-Z]{2})(\d{2})$/',
			// Sidecode 6: 99-XX-XX
			'/^(\d{2})([A-Z]{2})([A-Z]{2})$/',
			// Sidecode 7: 99-XXX-9
			'/^(\d{2})([A-Z]{3})(\d{1})$/',
			// Sidecode 8: 9-XXX-99
			'/^(\d{1})([A-Z]{3})(\d{2})$/',
			// Sidecode 9: XX-999-X
			'/^([A-Z]{2})(\d{3})([A-Z]{1})$/',
			// Sidecode 10: X-999-XX
			'/^([A-Z]{1})(\d{3})([A-Z]{2})$/',
			// Sidecode 11: XXX-99-X
			'/^([A-Z]{3})(\d{2})([A-Z]{1})$/',
			// Sidecode 12: X-99-XXX
			'/^([A-Z]{1})(\d{2})([A-Z]{3})$/',
			// Sidecode 13: 9-XX-999
			'/^(\d{1})([A-Z]{2})(\d{3})$/',
			// Sidecode 14: 999-XX-9
			'/^(\d{3})([A-Z]{2})(\d{1})$/',
		);

		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $kenteken, $matches ) ) {
				array_shift( $matches ); // Remove full match
				return implode( '-', $matches );
			}
		}

		// Fallback for non-standard lengths or unknown sidecodes (like HVN-52-NK which is 3-2-2)
		// Logic: insert dash when character type changes (letter <-> number)
		// But valid Dutch plates often group same types with dashes (e.g. 99-99-XX).
		// The regexes above cover the standard cases where same types are adjacent.
		// If we fall through here, use a simple heuristic: split groups of letters and numbers.
		
		// Attempt to split into groups of letters and numbers
		if ( preg_match_all( '/[A-Z]+|\d+/', $kenteken, $matches ) ) {
			// join with dashes
			return implode( '-', $matches[0] );
		}

		return $kenteken;
	}
}
