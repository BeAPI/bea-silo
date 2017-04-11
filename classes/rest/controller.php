<?php namespace BEA\Silo\Rest;

use BEA\Silo\Main;

class Controller extends \WP_REST_Controller {

	public $silo_base = 'silo';

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/' . $this->silo_base, array(
			/**
			 * Get contents
			 * AJAX check jQuery.ajax( { url :'https://ipsen.beapi.space/wp/wp-json/wp/v2/silo, headers : { 'post_types' : [ 'post', 'page' ], term_id : 34 }, method : "GET" });
			 */
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_contents' ),
				'permission_callback' => array( $this, 'get_contents_check' ),
				'args'                => array(
					'post_types' => array(
						'validate_callback' => array( $this, 'is_array' ),
						'sanitize_callback' => array( $this, 'sanitize_array' ),
						'required'          => true,
					),
					'term_id'    => array(
						'validate_callback' => array( $this, 'is_numeric' ),
						'sanitize_callback' => 'absint',
						'required'          => true,
					),
				),
			),
		) );
	}

	/**
	 * Get post types contents for the given term id.
	 *
	 * @author Maxime CULEA
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_contents( $request ) {
		$term = \WP_Term::get_instance( (int) $request->get_param( 'term_id' ) );
		$args = array(
			'post_type'      => (array) $request->get_param( 'post_types' ),
			'tax_query'      => array(
				'taxonomy' => $term->taxonomy,
				'terms'    => $term->term_id,

			),
			'posts_per_page' => apply_filters( 'bea\silo\content\posts_per_page', 3 ),
		);

		/**
		 * Filter the args given for the WP_Query
		 *
		 * @since 1.0.0
		 *
		 * @author Maxime CULEA
		 */
		$contents = new \WP_Query( apply_filters( 'bea\silo\content\args', $args ) );

		return new \WP_REST_Response( ! $contents->have_posts() ? [
			'code'    => 'rest_error_silo_content_empty',
			'message' => __( 'No contents matching arguments.', 'bea-silo' ),
		] : $contents->posts, 200 );
	}

	/**
	 * Check for the content get.
	 *
	 * @author Maxime CULEA
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|true
	 */
	public function get_content_check( \WP_REST_Request $request ) {
		$term       = \WP_Term::get_instance( (int) $request->get_param( 'term_id' ) );
		$post_types = (array) $request->get_param( 'post_types' );

		if ( ! Main::is_silotable( $post_types, $term ) ) {
			return new \WP_Error( 'rest_error_silo_content_args', __( 'Given args are not silotable ones.', 'bea-silo' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check is the param is a numeric value
	 *
	 * @param $param
	 * @param $request
	 * @param $key
	 *
	 * @return bool
	 */
	public function is_numeric( $param, $request, $key ) {
		return is_numeric( $param );
	}

	/**
	 * Check is the param is an array value
	 *
	 * @param $param
	 * @param $request
	 * @param $key
	 *
	 * @return bool
	 */
	public function is_array( $param, $request, $key ) {
		return is_array( $param );
	}

	/**
	 * Check is the param is an array value
	 *
	 * @param $param
	 * @param $request
	 * @param $key
	 *
	 * @return array
	 */
	public function sanitize_array( $param, $request, $key ) {
		$data = array();
		foreach ( $param as $key => $value ) {
			$data[ absint( $key ) ] = (string) $value;
		}

		return $data;
	}

	/**
	 * Return the current rest base
	 *
	 * @author Maxime CULEA
	 * @return string
	 */
	public function get_rest_base() {
		return $this->rest_base;
	}

	/**
	 * Return the current rest base
	 *
	 * @author Maxime CULEA
	 * @return string
	 */
	public function get_silo_base() {
		return $this->silo_base;
	}
}