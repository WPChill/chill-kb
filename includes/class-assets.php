<?php

namespace WPChill\KB;

class Assets {

	public function enqueue_styles() {
		if ( is_post_type_archive( 'kb' ) || is_singular( 'kb' ) || is_tax( 'kb_category' ) ) {
			wp_enqueue_style( 'wpchill-kb-styles', WPCHILL_KB_PLUGIN_URL . 'assets/css/wpchill-kb-styles.css', array( 'dashicons' ), WPCHILL_KB_VERSION );
			wp_enqueue_script( 'wpchill-kb-scripts', WPCHILL_KB_PLUGIN_URL . 'assets/js/wpchill-kb-scripts.js', array(), WPCHILL_KB_VERSION, true );

			// Blocks styles css
			$block_styles_css = '.wp-container-core-navigation-is-layout-1 {
								justify-content: space-between;
							}
							.wp-container-core-group-is-layout-1 {
								justify-content: space-between;
							}
							.wp-container-core-navigation-is-layout-2 {
								justify-content: space-between;
							}
							.wp-container-core-group-is-layout-3 {
								justify-content: space-between;
							}
							.wp-container-core-columns-is-layout-1 {
								flex-wrap: nowrap;
							}
							.wp-container-core-columns-is-layout-2 {
								flex-wrap: nowrap;
							}
							.wp-container-core-group-is-layout-12 {
								justify-content: space-between;
							}
							';

			wp_add_inline_style( 'wp-block-library', $block_styles_css );
		}
	}
}
