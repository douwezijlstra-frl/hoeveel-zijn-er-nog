<?php
namespace Hoeveel_Zijn_Er_Nog\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the top-level admin menu and sub-pages, enqueues admin assets,
 * and wires up the AJAX handler for RDW autocomplete.
 */
class Admin_Controller {

	/**
	 * Register all WordPress hooks.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_hzen_search_rdw', array( $this, 'ajax_search_rdw' ) );
	}

	/**
	 * Register top-level menu and sub-pages.
	 */
	public function register_menus(): void {
		$capability = (string) apply_filters( 'hzen_admin_capability', 'manage_options' );

		add_menu_page(
			__( 'Hoeveel Zijn Er Nog', 'hoeveel-zijn-er-nog' ),
			__( 'Hoeveel Zijn Er Nog', 'hoeveel-zijn-er-nog' ),
			$capability,
			'hzen',
			array( $this, 'page_models_list' ),
			'dashicons-car',
			30
		);

		add_submenu_page(
			'hzen',
			__( 'Tracked Models', 'hoeveel-zijn-er-nog' ),
			__( 'Tracked Models', 'hoeveel-zijn-er-nog' ),
			$capability,
			'hzen',
			array( $this, 'page_models_list' )
		);

		add_submenu_page(
			'hzen',
			__( 'Add Model', 'hoeveel-zijn-er-nog' ),
			__( 'Add Model', 'hoeveel-zijn-er-nog' ),
			$capability,
			'hzen-add',
			array( $this, 'page_add_model' )
		);

		add_submenu_page(
			'hzen',
			__( 'Settings', 'hoeveel-zijn-er-nog' ),
			__( 'Settings', 'hoeveel-zijn-er-nog' ),
			$capability,
			'hzen-settings',
			array( $this, 'page_settings' )
		);
	}

	/**
	 * Enqueue CSS/JS only on our own admin pages.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		$hzen_pages = array(
			'toplevel_page_hzen',
			'hoeveel-zijn-er-nog_page_hzen-add',
			'hoeveel-zijn-er-nog_page_hzen-history',
			'hoeveel-zijn-er-nog_page_hzen-settings',
		);

		if ( ! in_array( $hook_suffix, $hzen_pages, true ) ) {
			return;
		}

		wp_enqueue_style(
			'hzen-admin',
			HOEVEEL_ZIJN_ER_NOG_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			HOEVEEL_ZIJN_ER_NOG_VERSION
		);

		wp_enqueue_script(
			'hzen-admin',
			HOEVEEL_ZIJN_ER_NOG_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			HOEVEEL_ZIJN_ER_NOG_VERSION,
			true
		);

		wp_localize_script(
			'hzen-admin',
			'hzenAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'hzen_admin' ),
				'restUrl' => rest_url( 'hzen/v1/' ),
			)
		);
	}

	/**
	 * Render the tracked-models list page.
	 */
	public function page_models_list(): void {
		if ( ! current_user_can( (string) apply_filters( 'hzen_admin_capability', 'manage_options' ) ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'hoeveel-zijn-er-nog' ) );
		}

		// Handle row actions (toggle active, delete) before rendering.
		$this->handle_list_actions();

		$list_table = new Models_List_Table();
		$list_table->prepare_items();

		include HOEVEEL_ZIJN_ER_NOG_PLUGIN_DIR . 'includes/templates/admin/models-list.php';
	}

	/**
	 * Render the add-model page.
	 */
	public function page_add_model(): void {
		if ( ! current_user_can( (string) apply_filters( 'hzen_admin_capability', 'manage_options' ) ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'hoeveel-zijn-er-nog' ) );
		}

		$notice = '';

		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			$notice = $this->handle_add_model_form();
		}

		include HOEVEEL_ZIJN_ER_NOG_PLUGIN_DIR . 'includes/templates/admin/add-model.php';
	}

	/**
	 * Render the settings page.
	 */
	public function page_settings(): void {
		if ( ! current_user_can( (string) apply_filters( 'hzen_admin_capability', 'manage_options' ) ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'hoeveel-zijn-er-nog' ) );
		}

		$notice = '';
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			$notice = $this->handle_settings_form();
		}

		include HOEVEEL_ZIJN_ER_NOG_PLUGIN_DIR . 'includes/templates/admin/settings.php';
	}

	// -------------------------------------------------------------------------
	// Form handlers
	// -------------------------------------------------------------------------

	/**
	 * Process list-page row actions (toggle active, delete, view history).
	 */
	private function handle_list_actions(): void {
		$capability = (string) apply_filters( 'hzen_admin_capability', 'manage_options' );

		if ( isset( $_GET['hzen_action'] ) && isset( $_GET['model_id'] ) && isset( $_GET['_wpnonce'] ) ) {
			$action   = sanitize_key( $_GET['hzen_action'] );
			$model_id = (int) $_GET['model_id'];
			$nonce    = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );

			if ( ! wp_verify_nonce( $nonce, 'hzen_model_action_' . $model_id ) ) {
				wp_die( esc_html__( 'Nonce check failed.', 'hoeveel-zijn-er-nog' ) );
			}

			if ( ! current_user_can( $capability ) ) {
				wp_die( esc_html__( 'Insufficient permissions.', 'hoeveel-zijn-er-nog' ) );
			}

			switch ( $action ) {
				case 'activate':
					\Hoeveel_Zijn_Er_Nog\Db\Tracked_Models_Repo::set_active( $model_id, true );
					break;
				case 'deactivate':
					\Hoeveel_Zijn_Er_Nog\Db\Tracked_Models_Repo::set_active( $model_id, false );
					break;
				case 'delete':
					\Hoeveel_Zijn_Er_Nog\Db\Snapshots_Repo::delete_for_model( $model_id );
					\Hoeveel_Zijn_Er_Nog\Db\Tracked_Models_Repo::delete( $model_id );
					break;
			}

			wp_safe_redirect( admin_url( 'admin.php?page=hzen' ) );
			exit;
		}
	}

	/**
	 * Process the add-model form submission.
	 *
	 * @return string HTML notice string.
	 */
	private function handle_add_model_form(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'hzen_add_model' ) ) {
			return '<div class="notice notice-error"><p>' . esc_html__( 'Nonce verification failed.', 'hoeveel-zijn-er-nog' ) . '</p></div>';
		}

		$merk            = strtoupper( sanitize_text_field( wp_unslash( $_POST['merk'] ?? '' ) ) );
		$handelsbenaming = strtoupper( sanitize_text_field( wp_unslash( $_POST['handelsbenaming'] ?? '' ) ) );
		$voertuigsoort   = strtoupper( sanitize_text_field( wp_unslash( $_POST['voertuigsoort'] ?? 'P' ) ) );
		$inrichting      = strtoupper( sanitize_text_field( wp_unslash( $_POST['inrichting'] ?? '' ) ) );
		$label           = sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) );

		if ( ! $merk ) {
			return '<div class="notice notice-error"><p>' . esc_html__( 'Merk is required.', 'hoeveel-zijn-er-nog' ) . '</p></div>';
		}

		$slug = \Hoeveel_Zijn_Er_Nog\Db\Tracked_Models_Repo::make_slug( $merk, $handelsbenaming );

		if ( ! $label ) {
			$label = trim( $merk . ' ' . $handelsbenaming );
		}

		$id = \Hoeveel_Zijn_Er_Nog\Db\Tracked_Models_Repo::insert(
			array(
				'merk'            => $merk,
				'handelsbenaming' => $handelsbenaming,
				'voertuigsoort'   => $voertuigsoort,
				'inrichting'      => $inrichting,
				'slug'            => $slug,
				'label'           => $label,
				'active'          => 1,
			)
		);

		if ( false === $id ) {
			return '<div class="notice notice-error"><p>' . esc_html__( 'Could not save model (it may already exist).', 'hoeveel-zijn-er-nog' ) . '</p></div>';
		}

		return '<div class="notice notice-success"><p>' . esc_html__( 'Model added successfully.', 'hoeveel-zijn-er-nog' ) . '</p></div>';
	}

	/**
	 * Process the settings form submission.
	 *
	 * @return string HTML notice string.
	 */
	private function handle_settings_form(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'hzen_settings' ) ) {
			return '<div class="notice notice-error"><p>' . esc_html__( 'Nonce verification failed.', 'hoeveel-zijn-er-nog' ) . '</p></div>';
		}

		$token   = sanitize_text_field( wp_unslash( $_POST['hzen_app_token'] ?? '' ) );
		$ttl     = (int) ( $_POST['hzen_cache_ttl'] ?? DAY_IN_SECONDS );

		update_option( 'hzen_app_token', $token, false );
		update_option( 'hzen_cache_ttl', max( 60, $ttl ), false );

		return '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'hoeveel-zijn-er-nog' ) . '</p></div>';
	}

	// -------------------------------------------------------------------------
	// AJAX
	// -------------------------------------------------------------------------

	/**
	 * AJAX handler: search RDW Open Data for merk/handelsbenaming autocomplete.
	 * Returns JSON array of { merk, handelsbenaming } objects.
	 */
	public function ajax_search_rdw(): void {
		check_ajax_referer( 'hzen_admin', 'nonce' );

		if ( ! current_user_can( (string) apply_filters( 'hzen_admin_capability', 'manage_options' ) ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions.' ), 403 );
		}

		$term          = strtoupper( sanitize_text_field( wp_unslash( $_GET['term'] ?? '' ) ) );
		$search_field  = sanitize_key( $_GET['field'] ?? 'merk' );
		$allowed       = array( 'merk', 'handelsbenaming' );

		if ( ! in_array( $search_field, $allowed, true ) || strlen( $term ) < 2 ) {
			wp_send_json_success( array() );
		}

		// Use the RDW API to search distinct values.
		$cache  = new \Hoeveel_Zijn_Er_Nog\Cache\Cache();
		$client = new \Hoeveel_Zijn_Er_Nog\Api\Rdw_Client( $cache );

		$escaped_field = preg_replace( '/[^a-z_]/', '', $search_field );
		$escaped_term  = \Hoeveel_Zijn_Er_Nog\Api\Soql_Query_Builder::escape_string( $term );

		$results = $client->query(
			array(
				'$select' => "DISTINCT {$escaped_field}",
				'$where'  => "starts_with({$escaped_field}, '{$escaped_term}')",
				'$limit'  => 20,
				'$order'  => $escaped_field . ' ASC',
			),
			'rdw_autocomplete'
		);

		$items = array();
		if ( is_array( $results ) ) {
			foreach ( $results as $row ) {
				if ( isset( $row[ $search_field ] ) ) {
					$items[] = array( 'value' => esc_html( $row[ $search_field ] ) );
				}
			}
		}

		wp_send_json_success( $items );
	}
}
