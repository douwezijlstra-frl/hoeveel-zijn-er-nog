<?php
// Mock functions for testing
function sanitize_text_field( $str ) { return trim( $str ); }

class Hoeveel_Zijn_Er_Nog_Rdw_Service {
    public function build_where_clause( $criteria ) {
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
}

$service = new Hoeveel_Zijn_Er_Nog_Rdw_Service();
$criteria = [
    'merk' => 'VOLVO',
    'handelsbenaming' => '480',
    'voertuigsoort' => 'Personenauto'
];
echo "SoQL Where Clause: " . $service->build_where_clause($criteria) . "\n";
