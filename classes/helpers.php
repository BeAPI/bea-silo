<?php namespace BEA\Silo;

class Helpers {

	/**
	 * Get the term link depending
	 * @param \WP_Term $_term
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	public static function get_term_link( \WP_Term $_term ) {
		$link = home_url( sprintf( '%s/', self::get_taxonomy_silo( $_term->taxonomy ) ) );
		if ( is_taxonomy_hierarchical( $_term->taxonomy ) ) {
			$ancestors = get_ancestors( $_term->term_id, $_term->taxonomy, 'taxonomy' );
			if ( ! empty( $ancestors ) ) {
				// Inverse array to get from highest to lowest ancestor's hierarchy
				$ancestors = array_reverse( $ancestors );
				foreach ( $ancestors as $ancestor_id ) {
					$ancestor = get_term( $ancestor_id, $_term->taxonomy );
					$link     .= sprintf( '%s/', $ancestor->slug );
				}
			}
		}

		$link .= sprintf( '%s/', $_term->slug );

		return $link;
	}

	/**
	 * Get the default silo slug for a given taxonomy
	 *
	 * @author Maxime CULEA
	 *
	 * @param $taxonomy
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	public static function get_taxonomy_silo( $taxonomy ) {
		/**
		 * Change the default taxonomy silo slug
		 *
		 * @author Maxime CULEA
		 *
		 * @since 1.1.0
		 *
		 * @param string $slug The taxonomy's default silo slug.
		 * @param string $$taxonomy The taxonomy name working on.
		 */
		return apply_filters( 'BEA\Helpers\taxonomy_silo_slug', sprintf( 'silo-%s', $taxonomy ), $taxonomy );
	}
}