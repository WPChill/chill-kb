<?php

namespace WPChill\KB;

class Templates {

	public function include_template( $template ) {
		if ( is_post_type_archive( 'kb' ) ) {
			$archive_template = WPCHILL_KB_PLUGIN_DIR . 'templates/archive-kb.php';
			if ( file_exists( $archive_template ) ) {
				return $archive_template;
			}
		}

		if ( is_singular( 'kb' ) ) {
			$single_template = WPCHILL_KB_PLUGIN_DIR . 'templates/single-kb.php';
			if ( file_exists( $single_template ) ) {
				return $single_template;
			}
		}

		if ( is_tax( 'kb_category' ) ) {
			$taxonomy_template = WPCHILL_KB_PLUGIN_DIR . 'templates/taxonomy-kb-category.php';
			if ( file_exists( $taxonomy_template ) ) {
				return $taxonomy_template;
			}
		}

		return $template;
	}
}
