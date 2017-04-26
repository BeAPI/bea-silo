<?php namespace BEA\Silo;

class Blocks {

	// TODO : Think about post_type loop view

	/**
	 * Use the trait
	 */
	use Singleton;

	protected function init() {
		add_action( 'bea\silo\display', array( $this, 'display_silo' ), 10, 2 );
	}

	/**
	 * Action in purpose to display silo's underscores and html templates depending on the given post types and taxonomy.
	 * Underscore templates :
	 * - button
	 * - results
	 * - no results
	 *
	 * @author Maxime CULEA
	 *
	 * @since 1.0.1
	 *
	 * @param array $post_types : array of wanted post type names
	 * @param string $taxonomy : the taxonomy name
	 */
	public function display_silo( $post_types, $taxonomy ) {
		if ( ! Main::is_silotable_taxonomy( $post_types, $taxonomy ) ) {
			return;
		}

		$this->locate_template( 'blocks', [ 'silo' ], $taxonomy );
		$this->locate_template( 'templates', [ 'button-tpl', 'results-tpl', 'no-results-tpl' ], $taxonomy );
	}

	/**
	 * Locate template silo templates into :
	 * - child-theme
	 * - theme
	 * - plugin
	 *
	 * @author Maxime CULEA
	 *
	 * @since 1.0.1
	 *
	 * @param $type
	 * @param $views
	 * @param $taxonomy
	 */
	private function locate_template( $type, $views, $taxonomy ) {
		foreach ( $views as $view ) {
			$file_name = sprintf( '%s.%s', $view, false !== strpos( $view, 'tpl' ) ? 'js' : 'php' );

			$templates   = [ ];
			$templates[] = sprintf( 'silo/%s/%s-%s', $type, $taxonomy, $file_name );
			$templates[] = sprintf( 'silo/%s/%s', $type, $file_name );

			$located = locate_template( $templates, true, false );
			if ( ! empty( $located ) ) {
				continue;
			}

			include sprintf( '%s%s/%s', BEA_SILO_DIR, $type, $file_name );
		}
	}
}