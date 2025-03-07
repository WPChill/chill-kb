<?php

namespace WPChill\KB;

class Plugin {
	private $loader;
	private $post_type;
	private $taxonomy;
	private $templates;
	private $assets;
	private $search;
	private $article_rating;
	private $sidebar;
	private $article_locking;
	private $table_of_contents;
	private $rest_api;

	public function __construct() {
		global $wpchill_kb_errors;
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );

		try {
			$this->loader            = new Loader();
			$this->post_type         = new PostType();
			$this->taxonomy          = new Taxonomy();
			$this->templates         = new Templates();
			$this->assets            = new Assets();
			$this->search            = new Search();
			$this->article_rating    = ArticleRating::get_instance();
			$this->sidebar           = new Sidebar();
			$this->article_locking   = ArticleLocking::get_instance();
			$this->table_of_contents = new TableOfContents();
			$this->rest_api          = new RestAPI();

			// Register the shortcode using a closure
			add_shortcode(
				'kb_table_of_contents',
				function ( $atts ) {
					return $this->table_of_contents->generate_table_of_contents( $atts );
				}
			);
		} catch ( Exception $e ) {
			$wpchill_kb_errors[] = 'Error in Plugin constructor: ' . $e->getMessage();
		}
	}

	public function run() {
		global $wpchill_kb_errors;

		try {
			$this->loader->add_action( 'init', $this->post_type, 'register' );
			$this->loader->add_action( 'init', $this->taxonomy, 'register' );
			$this->loader->add_filter( 'template_include', $this->templates, 'include_template' );
			$this->loader->add_action( 'wp_enqueue_scripts', $this->assets, 'enqueue_styles' );
			$this->loader->add_action( 'rest_api_init', $this->rest_api, 'register_routes' );

			$this->loader->run();
		} catch ( Exception $e ) {
			$wpchill_kb_errors[] = 'Error in Plugin run method: ' . $e->getMessage();
		}
	}

	public function register_widgets() {
		if ( class_exists( 'WPChill\KB\KBCategoriesWidget' ) ) {
			register_widget( 'WPChill\KB\KBCategoriesWidget' );
		}
	}

	public function get_search_form() {
		return $this->search->get_search_form();
	}


	/**
	 * Renders the header for FSE or non FSE themes.
	 * for the FSE themes along with the header block display we have to render wp_head aswell.
	*/
	public function get_header() {
		if ( current_theme_supports( 'block-templates' ) ) {
			?>
			<html <?php language_attributes(); ?>>
			<head>
				<?php wp_head(); ?>
			</head>
			<body <?php body_class(); ?>>
			<?php
			block_template_part( 'header' );
		} else {
			get_header();
		}
	}

	/**
	 * Renders the footer for FSE or non FSE themes.
	 * for the FSE themes along with the footer block display we have to render wp_footer aswell.
	*/
	public function get_footer() {

		if ( current_theme_supports( 'block-templates' ) ) {
			?>
				<footer class="wp-block-template-part">
				<?php
				block_template_part( 'footer' );
				wp_footer();
				?>
				</footer>
				<?php
		} else {
			get_footer();
		}
	}
}
