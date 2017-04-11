<?php namespace BEA\Silo;

use BEA\Silo\Rest\Main as Rest;

class Main {
	/**
	 * Use the trait
	 */
	use Singleton;

	protected function init() {
		add_action( 'wp', array( $this, 'register_silo_script' ) );
		add_action( 'wp_footer', array( $this, 'enqueue_silo_script' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
	}

	public function register_silo_script() {
		wp_register_script( 'bea-silo', BEA_SILO_URL . '/assets/js/silo.js', [ 'jquery' ], BEA_SILO_VERSION );
	}

	public function enqueue_silo_script() {
		wp_enqueue_script( 'bea-silo' );
	}

	/**
	 * Localize post type's taxonomies' terms for silo use
	 *
	 * @author Maxime CULEA
	 *
	 * @version 1.0.0
	 */
	public function wp_enqueue_scripts() {
		$post_types = $this->get_silo_taxonomies();
		if ( empty( $post_types ) ) {
			return;
		}

		foreach ( $post_types as $post_type => $taxonomies ) {
			foreach ( $taxonomies as $taxonomy ) {
				if ( ! empty( $silo[ $taxonomy ] ) ) {
					array_push( $silo[ $taxonomy ]['post_types'], $post_type );
					continue;
				}

				$terms = $this->get_silo_terms( $taxonomy );
				if ( empty( $terms ) ) {
					$silo[ $taxonomy ] = array();
					continue;
				}

				$silo[ $taxonomy ] = array(
					'terms'      => $terms,
					'post_types' => (array) $post_type,
					'rest_url'   => esc_url( Rest::get_silo_rest_url() ),
				);
			}
		}

		if ( empty( $silo ) ) {
			return;
		}

		wp_localize_script( 'scripts', 'bea_silo', array(
			'objects'          => $silo,
			'read_more_label'  => __( 'Read more', 'bea-silo' ),
			'no_results_label' => __( 'No results.', 'bea-silo' ),
		) );
	}

	/**
	 * Manage to get the terms for a given taxonomy in purpose to localize them
	 *
	 * @author Maxime CULEA
	 *
	 * @version 1.0.0
	 *
	 * @param $taxonomy
	 *
	 * @return array
	 */
	private function get_silo_terms( $taxonomy ) {
		$args = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		);

		/**
		 * Filter the arguments to retrieve given taxonomy's terms.
		 *
		 * @author Maxime CULEA
		 *
		 * @since 1.0.0
		 *
		 * @param array $args Arguments for the WP_Term_Query or the get_terms();
		 * @param string $taxonomy The taxonomy name as context.
		 */
		$args = apply_filters( 'bea\silo\term_query\args', $args, $taxonomy );

		global $wp_version;
		if ( version_compare( '4.6', $wp_version, '<=' ) ) {
			$terms = new \WP_Term_Query( $args );
			$terms = $terms->get_terms();
		} else {
			$terms = get_terms( $args );
		}

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$data = array();
		foreach ( $terms as $_term ) {
			$data[] = $this->prepare_item_for_response( $_term );
		}

		return $data;
	}

	/**
	 * Prepare the term response
	 *
	 * @param \WP_Term $_term
	 *
	 * @author Maxime CULEA
	 *
	 * @return array
	 */
	private function prepare_item_for_response( $_term ) {
		// Use magic to_array func
		$new_item = (array) $_term;

		$new_item['has_children'] = $this->has_tax_children( $_term->term_id, $_term->taxonomy );
		$new_item['level']        = $this->get_tax_level( $_term->term_id, $_term->taxonomy );

		/**
		 * Filter term object to add / delete some attributes.
		 *
		 * @author Maxime CULEA
		 *
		 * @since 1.0.0
		 *
		 * @param array $new_item The formatted term object for response.
		 * @param \WP_Term $_term The term object.
		 * @param string $taxonomy The taxonomy name as context.
		 */
		return apply_filters( 'bea\silo\term_object', $new_item, $_term, $_term->taxonomy );
	}

	/**
	 * Count the number or ancestors to determine the level
	 *
	 * @param int $term_id The term ID.
	 * @param string $tax The taxonomy name.
	 *
	 * @author Maxime CULEA
	 *
	 * @return int
	 */
	private function get_tax_level( $term_id, $tax ) {
		return count( get_ancestors( $term_id, $tax ) );
	}

	/**
	 * Determine if the given term has children
	 *
	 * @param int $term_id The term ID.
	 * @param string $tax The taxonomy name.
	 *
	 * @author Maxime CULEA
	 *
	 * @return bool
	 */
	private function has_tax_children( $term_id, $tax ) {
		$child = new \WP_Term_Query( [
			'taxonomy' => $tax,
			'parent'   => $term_id,
			'fields'   => 'ids',
		] );

		return ! empty( $child->get_terms() );
	}

	/**
	 * Get all the post types marked with the silo post type support
	 * Use @see add_post_type_support() to add the post type support and also specify which taxonomy to register against for silo use
	 *
	 * @author Maxime CULEA
	 *
	 * @version 1.0.0
	 *
	 * @return array
	 */
	public static function get_silo_taxonomies() {
		$post_types = get_post_types_by_support( 'silo' );
		if ( empty( $post_types ) ) {
			return array();
		}

		$to_localize_taxonomies = array();
		foreach ( $post_types as $post_type ) {
			$post_type_supports = get_all_post_type_supports( $post_type );
			$silo_taxonomies    = $post_type_supports['silo'];
			if ( empty( $silo_taxonomies ) ) {
				continue;
			}

			foreach ( $silo_taxonomies as $taxonomy ) {
				/**
				 * Check custom conditions to check if to work on current taxonomy against current post type.
				 *
				 * @author Maxime CULEA
				 *
				 * @since 1.0.0
				 *
				 * @param bool $localize_taxonomy Whatever to localize terms against given taxonomy.
				 * @param string $taxonomy The taxonomy name as context.
				 * @param string $post_type The Post Type name as context.
				 */
				if ( ! apply_filters( 'bea\silo\localize_terms', false, $taxonomy, $post_type ) && ! REST_REQUEST ) {
					// If condition(s) not match and not doing rest request
					continue;
				}

				$to_localize_taxonomies[ $post_type ][] = $taxonomy;
			}
		}

		return $to_localize_taxonomies;
	}

	/**
	 * Check if the given term is from a silotable taxonomy and register against given post type(s)
	 *
	 * @author Maxime CULEA
	 *
	 * @param $post_types
	 * @param \WP_Term $term
	 *
	 * @return bool
	 */
	public static function is_silotable( $post_types, \WP_Term $term ) {
		if ( empty( $post_types ) || empty( $term ) ) {
			return false;
		}

		$silo_taxonomies = self::get_silo_taxonomies();
		if ( empty( $silo_taxonomies ) ) {
			return false;
		}

		foreach ( $post_types as $post_type ) {
			if ( ! isset( $silo_taxonomies[ $post_type ][ $term->taxonomy ] ) ) {
				return false;
			}
		}

		return true;
	}
}