<?php namespace BEA\Silo\Rest;

use BEA\Silo\Singleton;

class Main {
	/**
	 * Use the trait
	 */
	use Singleton;

	protected function init() {
		add_action( 'wp_head', array( $this, 'enqueue_rest_script' ) );
		add_action( 'rest_api_init', array( $this, 'add_routes_silo' ) );
	}

	/**
	 * Enqueue the Rest API script
	 */
	public function enqueue_rest_script() {
		wp_enqueue_script( 'wp-api' );
	}

	/**
	 * Register the Silo Rest API Controller
	 */
	public function add_routes_silo() {
		$controller = new Controller();
		$controller->register_routes();
	}

	/**
	 * Get the silo REST Api url
	 *
	 * @author Maxime CULEA
	 *
	 * @return string
	 */
	public static function get_silo_rest_url() {
		$controller = new Controller();
		return rest_url( sprintf( '/%s/%s/', $controller->get_namespace(), $controller->get_rest_base() ) );
	}
}