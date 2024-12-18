<?php
/**
 * Table of Contents functionality for WPChill KB.
 *
 * @package WPChill\KB
 */

namespace WPChill\KB;

/**
 * Class TableOfContents
 */
class TableOfContents {

	/**
	 * TableOfContents constructor.
	 */
	public function __construct() {
		add_filter( 'block_editor_settings_all', array( $this, 'enable_automatic_heading_ids_gutenberg' ), 10, 2 );
	}

	/**
	 * Enable automatic heading IDs in Gutenberg.
	 * Note: This used to be an experimental feature.
	 *
	 * @param array $editor_settings The current editor settings.
	 * @return array Modified editor settings.
	 */
	public function enable_automatic_heading_ids_gutenberg( $editor_settings ) {
		$editor_settings['generateAnchors'] = true;
		return $editor_settings;
	}

	/**
	 * Generate table of contents.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Generated table of contents HTML.
	 */
	public function generate_table_of_contents( $atts ) {

		if ( ! is_singular() ) {
			return '';
		}

		$atts = shortcode_atts(
			array(
				'depth' => 6,
				'title' => 'Table of Contents',
			),
			$atts
		);

		$post = get_post();

		if ( ! $post ) {
			return '';
		}

		$blocks = parse_blocks( $post->post_content );

		$headings = $this->extract_headings_from_blocks( $blocks, $atts['depth'] );

		if ( empty( $headings ) ) {
			return '';
		}

		$toc  = '<div class="wpchill-kb-toc">';
		$toc .= '<h3>' . esc_html( $atts['title'] ) . '</h3>';
		$toc .= '<ul>';

		foreach ( $headings as $heading ) {
			$toc .= '<li><span class="dashicons dashicons-arrow-right-alt2"></span><a href="#' . esc_attr( $heading['id'] ) . '">' . esc_html( $heading['text'] ) . '</a></li>';
		}

		$toc .= '</ul></div>';

		return $toc;
	}

	/**
	 * Extract headings from blocks.
	 *
	 * @param array $blocks    Array of blocks.
	 * @param int   $max_depth Maximum heading depth to include.
	 * @return array Extracted headings.
	 */
	private function extract_headings_from_blocks( $blocks, $max_depth ) {
		$headings = array();
		foreach ( $blocks as $block ) {
			if ( 'core/heading' === $block['blockName'] ) {
				$level = isset( $block['attrs']['level'] ) ? $block['attrs']['level'] : 2;
				if ( $level <= $max_depth ) {
					$id = isset( $block['attrs']['anchor'] )
						? $block['attrs']['anchor']
						: 'h-' . sanitize_title( wp_strip_all_tags( $block['innerHTML'] ) );

					$headings[] = array(
						'level' => $level,
						'text'  => wp_strip_all_tags( $block['innerHTML'] ),
						'id'    => $id,
					);
				}
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$headings = array_merge( $headings, $this->extract_headings_from_blocks( $block['innerBlocks'], $max_depth ) );
			}
		}
		return $headings;
	}
}
