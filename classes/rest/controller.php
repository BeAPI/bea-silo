<?php namespace BEA\Silo\Rest;

use BEA\Silo\Main;

class Controller extends \WP_REST_Controller {

	public $namespace = 'bea';
	public $rest_base = 'silo';

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, [
			/**
			 * Get contents
			 * AJAX check jQuery.ajax( { url :'https://yourdomain.com/wp-json/bea/silo?post_types[]=post&term_id=4', params : { 'post_types' : [ 'post', 'page' ], term_id : 34 }, method : "GET" });
			 */
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_contents' ],
				'permission_callback' => [ $this, 'get_contents_check' ],
				'args'                =>
					apply_filters( 'bea\silo\register_rest_route\args', [
						'post_types' => [
							'validate_callback' => [ $this, 'is_array' ],
							'sanitize_callback' => [ $this, 'sanitize_array' ],
							'required'          => true,
						],
						'term_id'    => [
							'validate_callback' => [ $this, 'is_numeric' ],
							'sanitize_callback' => 'absint',
							'required'          => true,
						],
					] ),
			],
		] );
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
		$args = [
			'post_type'      => (array) $request->get_param( 'post_types' ),
			'tax_query'      => [
				[
					'taxonomy' => $term->taxonomy,
					'terms'    => $term->term_id,
				],
			],
			'posts_per_page' => 3,
		];

		/**
		 * Filter the args given for the WP_Query
		 *
		 * @since 1.0.0
		 *
		 * @author Maxime CULEA
		 */
		$contents = new \WP_Query( apply_filters( 'bea\silo\content\args', $args, $request ) );
		if ( ! $contents->have_posts() ) {
			new \WP_REST_Response( [
				'code'    => 'rest_error_silo_content_empty',
				'message' => __( 'No contents matching arguments.', 'bea-silo' ),
			] );
		}

		foreach ( $contents->posts as $post ) {
			$data    = $this->prepare_item_for_response( (array) $post, $request );
			$posts[] = $this->prepare_response_for_collection( $data );
		}

		return rest_ensure_response( $posts );
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
	public function get_contents_check( \WP_REST_Request $request ) {
		$term       = \WP_Term::get_instance( (int) $request->get_param( 'term_id' ) );
		if ( is_wp_error( $term ) ) {
			return new \WP_Error( 'rest_error_silo_no_term', __( 'Term is missing.', 'bea-silo' ), [ 'status' => rest_authorization_required_code() ] );
		}

		$post_types = (array) $request->get_param( 'post_types' );
		if ( empty( $post_types ) ) {
			return new \WP_Error( 'rest_error_silo_no_post_types', __( 'Post Types are missing.', 'bea-silo' ), [ 'status' => rest_authorization_required_code() ] );
		}

		if ( ! Main::is_silotable_term( $post_types, $term ) ) {
			return new \WP_Error( 'rest_error_silo_content_args', __( 'Given args are not silotable ones.', 'bea-silo' ), [ 'status' => rest_authorization_required_code() ] );
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
		$data = [ ];
		foreach ( $param as $key => $value ) {
			$data[ absint( $key ) ] = (string) $value;
		}

		return $data;
	}

	/**
	 * Return the current namespace
	 *
	 * @author Maxime CULEA
	 * @return string
	 */
	public function get_namespace() {
		return $this->namespace;
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
	 * Prepare items for response and adding additional fields
	 *
	 * @param mixed $object
	 * @param \WP_REST_Request $request
	 *
	 * @author Maxime CULEA
	 *
	 * @return mixed
	 */
	public function prepare_item_for_response( $object, $request ) {
		$GLOBALS['post'] = $object;
		setup_postdata( $object );

		$additional_fields = $this->get_additional_fields( $object['post_type'] );

		foreach ( $additional_fields as $field_name => $field_options ) {
			if ( ! $field_options['get_callback'] ) {
				continue;
			}

			$object[ $field_name ] = call_user_func( $field_options['get_callback'], $object, $field_name, $request, $object['post_type'] );
		}

		return $object;
	}
}