<?php namespace BEA\Silo;

class Blocks {
	/**
	 * Use the trait
	 */
	use Singleton;

	protected function init() {
		add_action( 'wp_footer', array( $this, 'display_underscore_tpl' ) );
	}

	/**
	 * Add both results and no-results underscore templates to wp_footer
	 * Get the underscore templates into :
	 * - child-theme
	 * - theme
	 * - plugins
	 *
	 * @author Maxime CULEA
	 *
	 * @version 1.0.0
	 *
	 * @todo : Think about post_type loop view
	 */
	public function display_underscore_tpl() {
		$post_types = Main::get_silo_taxonomies();
		if ( empty( $post_types ) ) {
			return;
		}

		foreach ( $post_types as $post_type => $taxonomies ) {
			foreach ( $taxonomies as $taxonomy ) {
				// Underscore templates for displaying results
				$templates['results'] = array(
					sprintf( 'templates/silo/%s-results-tpl.js', $taxonomy ),
					'templates/silo/results-tpl.js',
				);

				// Underscore templates for displaying empty results
				$templates['no-results'] = array(
					sprintf( 'templates/silo/%s-no-results-tpl.js', $taxonomy ),
					'templates/silo/no-results-tpl.js',
				);

				foreach ( $templates as $context => $paths ) {
					$located = locate_template( $paths, true, false );
					if ( ! $located ) {
						include sprintf( '%s/templates/%s-tpl.js', BEA_SILO_DIR, $context );
					}
				}
			}
		}
	}
}