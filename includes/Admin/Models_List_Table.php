<?php
namespace Hoeveel_Zijn_Er_Nog\Admin;

use Hoeveel_Zijn_Er_Nog\Db\Tracked_Models_Repo;
use Hoeveel_Zijn_Er_Nog\Db\Snapshots_Repo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * WP_List_Table subclass for the tracked models admin screen.
 */
class Models_List_Table extends \WP_List_Table {

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'model',
				'plural'   => 'models',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Define table columns.
	 */
	public function get_columns(): array {
		return array(
			'label'          => __( 'Label', 'hoeveel-zijn-er-nog' ),
			'merk'           => __( 'Merk', 'hoeveel-zijn-er-nog' ),
			'handelsbenaming' => __( 'Handelsbenaming', 'hoeveel-zijn-er-nog' ),
			'voertuigsoort'  => __( 'Voertuigsoort', 'hoeveel-zijn-er-nog' ),
			'slug'           => __( 'Slug', 'hoeveel-zijn-er-nog' ),
			'active'         => __( 'Status', 'hoeveel-zijn-er-nog' ),
			'last_snapshot'  => __( 'Last Snapshot', 'hoeveel-zijn-er-nog' ),
		);
	}

	/**
	 * Sortable columns.
	 */
	protected function get_sortable_columns(): array {
		return array(
			'label'  => array( 'label', true ),
			'merk'   => array( 'merk', false ),
			'active' => array( 'active', false ),
		);
	}

	/**
	 * Load data into $this->items.
	 */
	public function prepare_items(): void {
		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
		);
		$this->items = Tracked_Models_Repo::all();
	}

	/**
	 * Default column renderer.
	 */
	public function column_default( $item, $column_name ): string {
		return esc_html( $item[ $column_name ] ?? '' );
	}

	/**
	 * Label column with row actions.
	 */
	public function column_label( $item ): string {
		$id    = (int) $item['id'];
		$nonce = wp_create_nonce( 'hzen_model_action_' . $id );
		$base  = admin_url( 'admin.php' );

		$actions = array(
			'history' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( add_query_arg( array( 'page' => 'hzen', 'hzen_view' => 'history', 'model_id' => $id ), $base ) ),
				esc_html__( 'History', 'hoeveel-zijn-er-nog' )
			),
		);

		if ( $item['active'] ) {
			$actions['deactivate'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( add_query_arg( array( 'page' => 'hzen', 'hzen_action' => 'deactivate', 'model_id' => $id, '_wpnonce' => $nonce ), $base ) ),
				esc_html__( 'Deactivate', 'hoeveel-zijn-er-nog' )
			);
		} else {
			$actions['activate'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( add_query_arg( array( 'page' => 'hzen', 'hzen_action' => 'activate', 'model_id' => $id, '_wpnonce' => $nonce ), $base ) ),
				esc_html__( 'Activate', 'hoeveel-zijn-er-nog' )
			);
		}

		$actions['delete'] = sprintf(
			'<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
			esc_url( add_query_arg( array( 'page' => 'hzen', 'hzen_action' => 'delete', 'model_id' => $id, '_wpnonce' => $nonce ), $base ) ),
			esc_js( __( 'Delete this model and all its snapshots?', 'hoeveel-zijn-er-nog' ) ),
			esc_html__( 'Delete', 'hoeveel-zijn-er-nog' )
		);

		return sprintf(
			'<strong>%s</strong>%s',
			esc_html( $item['label'] ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Status column.
	 */
	public function column_active( $item ): string {
		if ( $item['active'] ) {
			return '<span style="color:green;">' . esc_html__( 'Active', 'hoeveel-zijn-er-nog' ) . '</span>';
		}
		return '<span style="color:grey;">' . esc_html__( 'Inactive', 'hoeveel-zijn-er-nog' ) . '</span>';
	}

	/**
	 * Last snapshot column — fetches latest snapshot date.
	 */
	public function column_last_snapshot( $item ): string {
		$snap = Snapshots_Repo::latest( (int) $item['id'] );
		if ( $snap ) {
			return esc_html( $snap['snapshot_date'] );
		}
		return esc_html__( 'Never', 'hoeveel-zijn-er-nog' );
	}

	/**
	 * Message when no items.
	 */
	public function no_items(): void {
		esc_html_e( 'No tracked models yet. Add your first model!', 'hoeveel-zijn-er-nog' );
	}
}
