<?php
namespace Hoeveel_Zijn_Er_Nog;

use Hoeveel_Zijn_Er_Nog\Admin\Admin_Controller;
use Hoeveel_Zijn_Er_Nog\Api\Rdw_Client;
use Hoeveel_Zijn_Er_Nog\Cache\Cache;
use Hoeveel_Zijn_Er_Nog\Cron\Snapshot_Runner;
use Hoeveel_Zijn_Er_Nog\Frontend\Shortcode_Rdw_Count;
use Hoeveel_Zijn_Er_Nog\Frontend\Shortcode_Hzen_Count;
use Hoeveel_Zijn_Er_Nog\Frontend\Shortcode_Hzen_History;
use Hoeveel_Zijn_Er_Nog\Frontend\Shortcode_Hzen_Top_N;
use Hoeveel_Zijn_Er_Nog\Frontend\Shortcode_Hzen_Compare;
use Hoeveel_Zijn_Er_Nog\Frontend\Shortcode_Hzen_Model_Page;
use Hoeveel_Zijn_Er_Nog\Rest\History_Controller;
use Hoeveel_Zijn_Er_Nog\Services\Historical_Stats_Provider;
use Hoeveel_Zijn_Er_Nog\Services\Rdw_Stats_Provider;
use Hoeveel_Zijn_Er_Nog\Services\Stats_Provider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin {

	private static $instance = null;

	/** @var Stats_Provider */
	private $provider;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		$cache    = new Cache();
		$client   = new Rdw_Client( $cache );
		$raw      = new Rdw_Stats_Provider( $client );

		// Expose the raw provider so Snapshot_Runner can grab it without circular deps.
		add_filter(
			'hzen_stats_provider_raw',
			function () use ( $raw ) {
				return $raw;
			}
		);

		// Decorate with historical DB-backed provider.
		$provider = new Historical_Stats_Provider( $raw );

		/**
		 * Filter the active Stats_Provider.
		 * Third-party code can swap or further decorate the implementation here.
		 */
		$provider = apply_filters( 'hzen_stats_provider', $provider, $client, $cache );

		if ( ! $provider instanceof Stats_Provider ) {
			$provider = new Historical_Stats_Provider( $raw );
		}

		$this->provider = $provider;

		// Wire in the app-token stored in DB settings.
		add_filter(
			'hzen_rdw_app_token',
			function () {
				return (string) get_option( 'hzen_app_token', '' );
			}
		);

		// Wire in the cache TTL stored in DB settings.
		add_filter(
			'hzen_cache_ttl',
			function ( $ttl ) {
				$stored = (int) get_option( 'hzen_cache_ttl', 0 );
				return $stored > 0 ? $stored : $ttl;
			}
		);

		// Frontend shortcodes.
		( new Shortcode_Rdw_Count( $provider ) )->register();
		( new Shortcode_Hzen_Count( $provider ) )->register();
		( new Shortcode_Hzen_History( $provider ) )->register();
		( new Shortcode_Hzen_Top_N() )->register();
		( new Shortcode_Hzen_Compare() )->register();
		( new Shortcode_Hzen_Model_Page( $provider ) )->register();

		// Admin UI.
		if ( is_admin() ) {
			( new Admin_Controller() )->register();
		}

		// WP-Cron.
		( new Snapshot_Runner() )->register();

		// REST API.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Gutenberg blocks (server-side rendered).
		add_action( 'init', array( $this, 'register_blocks' ) );

		// Frontend assets.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes(): void {
		( new History_Controller() )->register_routes();
	}

	/**
	 * Register server-side Gutenberg blocks.
	 */
	public function register_blocks(): void {
		$blocks = array(
			'hzen-count',
			'hzen-history',
			'hzen-top-n',
			'hzen-compare',
			'hzen-model-page',
		);

		foreach ( $blocks as $block_slug ) {
			$block_dir = HOEVEEL_ZIJN_ER_NOG_PLUGIN_DIR . 'blocks/' . $block_slug;
			if ( is_dir( $block_dir ) ) {
				register_block_type( $block_dir );
			}
		}
	}

	/**
	 * Enqueue frontend CSS.
	 */
	public function enqueue_assets(): void {
		wp_enqueue_style(
			'hoeveel-zijn-er-nog-style',
			HOEVEEL_ZIJN_ER_NOG_PLUGIN_URL . 'assets/css/style.css',
			array(),
			HOEVEEL_ZIJN_ER_NOG_VERSION
		);
	}
}
