<?php namespace BEA\Silo;

class Plugin {

	use Singleton;

	function __construct() {
		add_action( 'init', array( $this, 'init_translations' ) );
		add_action( 'wp', array( $this, 'register_assets' ) );
		add_action( 'wp_head', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Load the plugin translation
	 */
	public function init_translations() {
		// Load translations
		load_plugin_textdomain( 'bea-silo', false, basename( rtrim( BEA_SILO_DIR, '/' ) ) . '/languages' );
	}

	/**
	 * Register assets
	 * @author Maxime CULEA
	 */
	public static function register_assets() {
		wp_register_script( 'bea-silo-script', BEA_SILO_URL . 'assets/js/silo.js', [ 'jquery' ], BEA_SILO_VERSION );
	}

	/**
	 * Enqueue assets
	 * @author Maxime CULEA
	 */
	public static function enqueue_assets() {
		wp_enqueue_script( 'bea-silo-script' );
	}
}