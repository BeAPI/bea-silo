<?php namespace BEA\Silo;

use BEA\Silo\Rest\Main as Rest;

class Main {
	/**
	 * Use the trait
	 */
	use Singleton;

	protected function init() {
		add_action( 'wp', array( $this, 'register_silo_script' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );

		add_filter( 'bea\silo\localize_terms', array( $this, 'where_to_localize_terms_by_default' ), PHP_INT_MAX, 3 );
		add_filter( 'template_include', array( $this, 'template_include_default' ) );
		add_filter( 'wp_title', array( $this, 'customize_silo_wp_title' ), 10, 3 );
	}

	public function register_silo_script() {
		wp_register_script( 'bea-silo', BEA_SILO_URL . 'assets/js/silo.js', [ 'jquery' ], BEA_SILO_VERSION );
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
					// Don't get one more time contents, just add the related post type to it
					array_push( $silo[ $taxonomy ]['post_types'], $post_type );
					continue;
				}

				$terms = $this->get_silo_terms( $taxonomy );
				if ( empty( $terms ) ) {
					$silo[ $taxonomy ] = array();
					continue;
				}

				$data = array(
					'terms'      => $terms,
					'post_types' => (array) $post_type,
					'rest_url'   => esc_url( Rest::get_silo_rest_url() ),
					'base_slug'  => Helpers::get_taxonomy_silo( $taxonomy ),
				);

				/**
				 * Filter the data for each taxonomy
				 *
				 * @author Romain DORR
				 *
				 * @since 1.1.1
				 *
				 * @param array $data
				 * @param string $taxonomy The taxonomy name as context.
				 * @param string $post_type The post type name as context.
				 */
				$silo[ $taxonomy ] = apply_filters( 'bea\silo\taxonomy\data', $data, $taxonomy, $post_type );
			}
		}

		if ( empty( $silo ) ) {
			return;
		}

		wp_enqueue_script( 'bea-silo' );
		$localize = array(
			'objects'          => $silo,
			'read_more_label'  => __( 'Read more', 'bea-silo' ),
			'no_results_label' => __( 'No results.', 'bea-silo' ),
		);

		/**
		 * Filter localized data
		 *
		 * @author Romain DORR
		 *
		 * @since 1.1.1
		 *
		 * @param array $localize Data to be localized.
		 */
		wp_localize_script( 'bea-silo', 'bea_silo', apply_filters( 'bea\silo\localize_data', $localize ) );
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
	private function prepare_item_for_response( \WP_Term $_term ) {
		// Use magic to_array func
		$new_item = (array) $_term;

		$new_item['childrens'] = $this->has_tax_children( $_term->term_id, $_term->taxonomy );
		$new_item['level']        = $this->get_tax_level( $_term->term_id, $_term->taxonomy );
		$new_item['term_link']    = Helpers::get_term_link( $_term );

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
	 * @return array
	 */
	private function has_tax_children( $term_id, $tax ) {
		$child = new \WP_Term_Query( [
			'taxonomy' => $tax,
			'parent'   => $term_id,
			'fields'   => 'ids',
		] );

		return empty( $child->get_terms() ) ? [] : $child->get_terms();
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
				if ( ! apply_filters( 'bea\silo\localize_terms', false, $taxonomy, $post_type ) && ! defined( 'REST_REQUEST' ) && ! REST_REQUEST ) {
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
	 * @param array $post_types
	 * @param \WP_Term $term
	 *
	 * @return bool
	 */
	public static function is_silotable_term( $post_types, \WP_Term $term ) {
		if ( empty( $post_types ) || empty( $term ) ) {
			return false;
		}

		$silo_taxonomies = self::get_silo_taxonomies();
		if ( empty( $silo_taxonomies ) ) {
			return false;
		}

		foreach ( $post_types as $post_type ) {
			if ( isset( $silo_taxonomies[ $post_type ] ) && in_array( $term->taxonomy, $silo_taxonomies[ $post_type ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if the given taxonomy is silotable for the given post type(s)
	 *
	 * @author Maxime CULEA
	 *
	 * @param array $post_types
	 * @param string $taxonomy
	 *
	 * @return bool
	 */
	public static function is_silotable_taxonomy( $post_types, $taxonomy ) {
		if ( empty( $post_types ) || empty( $taxonomy ) ) {
			return false;
		}

		$silo_taxonomies = self::get_silo_taxonomies();
		if ( empty( $silo_taxonomies ) ) {
			return false;
		}

		foreach ( $post_types as $post_type ) {
			if ( isset( $silo_taxonomies[ $post_type ] ) && in_array( $taxonomy, $silo_taxonomies[ $post_type ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * By default add a rule ( bea-silo-{taxonomy_name} ) for terms localization
	 *
	 * @author Maxime CULEA
	 *
	 * @param $hide_or_display
	 * @param $taxonomy
	 * @param $post_type
	 *
	 * @since 1.1.0
	 *
	 * @return bool
	 */
	public function where_to_localize_terms_by_default( $hide_or_display, $taxonomy, $post_type ) {
		if ( $hide_or_display ) {
			return $hide_or_display;
		}

		return $this->is_current_default_view( $taxonomy );
	}

	/**
	 * Check if on a default silo view
	 * /!\ Ensure the $_SERVER is using REQUEST_URI parameter
	 *
	 * @author Maxime CULEA
	 *
	 * @param $taxonomy_name
	 *
	 * @since 1.1.0
	 *
	 * @return bool
	 */
	public function is_current_default_view( $taxonomy_name ) {
		$taxonomy_silo = Helpers::get_taxonomy_silo( $taxonomy_name );

		if ( empty( $taxonomy_silo ) ) {
			return false;
		}

		return isset( $_SERVER['REQUEST_URI'] ) && false !== strpos( $_SERVER['REQUEST_URI'], $taxonomy_silo );
	}

	/**
	 * Change on the fly the default silo view for a taxonomy
	 * It uses bea-silo-{taxonomy_name}.php into your theme
	 *
	 * @author Maxime CULEA
	 *
	 * @param $path
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	public function template_include_default( $path ) {
		foreach ( self::get_silo_taxonomies() as $pt ) {
			foreach ( $pt as $tax ) {
				if ( $this->is_current_default_view( $tax ) ) {
					$template_path = sprintf( '%s/%s.php', get_template_directory(), Helpers::get_taxonomy_silo( $tax ) );

					return is_file( $template_path ) ? $template_path : $path;
				}
			}
		}

		return $path;
	}

	/**
	 * @author Maxime CULEA
	 *
	 * @param $title
	 * @param $sep
	 * @param $seplocation
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	public function customize_silo_wp_title( $title, $sep, $seplocation ) {
		foreach ( self::get_silo_taxonomies() as $pt ) {
			foreach ( $pt as $tax ) {
				if ( $this->is_current_default_view( $tax ) ) {
					return sprintf( 'Silo %s %s', $sep, ucfirst( $tax ) );
				}
			}
		}

		return $title;
	}
}
