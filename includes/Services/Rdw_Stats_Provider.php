<?php
namespace Hoeveel_Zijn_Er_Nog\Services;

use Hoeveel_Zijn_Er_Nog\Api\Rdw_Client;
use Hoeveel_Zijn_Er_Nog\Api\Soql_Query_Builder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rdw_Stats_Provider implements Stats_Provider {

	private $client;

	public function __construct( Rdw_Client $client ) {
		$this->client = $client;
	}

	public function get_stats( array $criteria ): array {
		$criteria = (array) apply_filters( 'hzen_criteria', $criteria );
		$where    = Soql_Query_Builder::build_where( $criteria );
		$today    = gmdate( 'Ymd' );

		$select = "count(kenteken) AS total,"
			. " sum(case(vervaldatum_apk >= '{$today}', 1, true, 0)) AS apk_valid,"
			. ' min(datum_eerste_toelating) AS oldest_date,'
			. ' max(datum_eerste_toelating) AS newest_date';

		$agg = $this->client->query(
			array(
				'$select' => $select,
				'$where'  => $where,
			),
			'rdw_aggregate'
		);

		$row = ( is_array( $agg ) && isset( $agg[0] ) ) ? $agg[0] : array();

		$stats = array(
			'total'     => isset( $row['total'] ) ? (int) $row['total'] : 0,
			'apk_valid' => isset( $row['apk_valid'] ) ? (int) $row['apk_valid'] : 0,
			'oldest'    => null,
			'newest'    => null,
		);

		$oldest_date = isset( $row['oldest_date'] ) ? $row['oldest_date'] : '';
		$newest_date = isset( $row['newest_date'] ) ? $row['newest_date'] : '';

		if ( $oldest_date ) {
			$stats['oldest'] = $this->lookup_kenteken( $where, $oldest_date, 'ASC' );
		}
		if ( $newest_date && $newest_date !== $oldest_date ) {
			$stats['newest'] = $this->lookup_kenteken( $where, $newest_date, 'DESC' );
		} elseif ( $newest_date ) {
			$stats['newest'] = $stats['oldest'];
		}

		return (array) apply_filters( 'hzen_stats', $stats, $criteria );
	}

	public function get_history( array $criteria, string $from, string $to ): array {
		return array();
	}

	private function lookup_kenteken( $where, $date, $order ) {
		$date = Soql_Query_Builder::escape_string( $date );
		$data = $this->client->query(
			array(
				'$select' => 'kenteken,datum_eerste_toelating',
				'$where'  => $where . " AND datum_eerste_toelating = '{$date}'",
				'$order'  => 'datum_eerste_toelating ' . ( 'ASC' === $order ? 'ASC' : 'DESC' ),
				'$limit'  => 1,
			),
			'rdw_lookup'
		);
		return ( is_array( $data ) && ! empty( $data[0] ) ) ? $data[0] : null;
	}
}
