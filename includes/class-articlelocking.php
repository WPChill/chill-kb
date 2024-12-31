<?php
/**
 * Article Locking functionality for WPChill KB.
 *
 * @package WPChill\KB
 */

namespace WPChill\KB;

/**
 * Class ArticleLocking
 *
 * Handles the locking and unlocking of KB articles.
 */
class ArticleLocking {

	/**
	 * Meta key used to store the locked status of an article.
	 *
	 * @var string
	 */
	const META_KEY = '_wpchill_kb_locked';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes_kb', array( $this, 'add_lock_meta_box' ) );
		add_action( 'save_post_kb', array( $this, 'save_lock_meta_box' ), 10, 2 );
		add_filter( 'the_content', array( $this, 'filter_locked_content' ), 999 );
		add_filter( 'wpchill_kb_article_classes', array( $this, 'add_locked_class' ), 10, 2 );
		add_action( 'wpchill_kb_before_article_content', array( $this, 'display_locked_message' ) );
		add_filter( 'wpchill_kb_search_args', array( $this, 'modify_search_args' ) );
	}



	/**
	 * Add the lock meta box to the KB post editor.
	 *
	 * @param \WP_Post $post The post object.
	 * @return void
	 */
	public function add_lock_meta_box( $post ) {
		add_meta_box(
			'wpchill_kb_lock_article',
			__( 'Article Access', 'wpchill-kb' ),
			array( $this, 'render_lock_meta_box' ),
			'kb',
			'side',
			'default'
		);
	}

	/**
	 * Render the lock meta box in the post editor.
	 *
	 * @param \WP_Post $post The post object.
	 * @return void
	 */
	public function render_lock_meta_box( $post ) {
		wp_nonce_field( 'wpchill_kb_lock_article', 'wpchill_kb_lock_article_nonce' );
		$is_locked = get_post_meta( $post->ID, self::META_KEY, true );
		?>
        <p>
            <input type="checkbox" id="wpchill_kb_lock_article" name="wpchill_kb_lock_article" <?php checked( $is_locked, 'on' ); ?>>
            <label for="wpchill_kb_lock_article"><?php esc_html_e( 'Lock this article (only visible to logged-in users)', 'wpchill-kb' ); ?></label>
        </p>
		<?php
	}

	/**
	 * Save the lock status meta box for a post.
	 *
	 * @param int      $post_id The ID of the post being saved.
	 * @param \WP_Post $post    The post object being saved.
	 * @return void
	 */
	public function save_lock_meta_box( $post_id, $post ) {
		if ( ! isset( $_POST['wpchill_kb_lock_article_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['wpchill_kb_lock_article_nonce'] ), 'wpchill_kb_lock_article' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$is_locked = isset( $_POST['wpchill_kb_lock_article'] ) ? 'on' : 'off';
		update_post_meta( $post_id, self::META_KEY, $is_locked );
	}

	/**
	 * Filter the content to display a locked message for non-logged-in users.
	 *
	 * @param string $content The original content of the post.
	 * @return string The modified content.
	 */
	public function filter_locked_content( $content ) {
		if ( ! is_singular( 'kb' ) ) {
			return $content;
		}

		$post_id = get_the_ID();
		if ( $this->is_article_locked( $post_id ) && ! is_user_logged_in() ) {
			return $this->get_locked_message( $post_id );
		}

		return do_shortcode($content);
	}


	/**
	 * Add 'locked' class to article if it's locked.
	 *
	 * @param array $classes An array of post classes.
	 * @param int   $post_id The post ID.
	 * @return array Modified array of post classes.
	 */
	public function add_locked_class( $classes, $post_id ) {
		if ( $this->is_article_locked( $post_id ) ) {
			$classes[] = 'wpchill-kb-locked';
		}
		return $classes;
	}

	/**
	 * Display a locked message before the article content if it's locked.
	 *
	 * @param int $post_id The post ID.
	 */
	public function display_locked_message( $post_id ) {
		if ( $this->is_article_locked( $post_id ) && ! is_user_logged_in() ) {
			echo $this->get_locked_message( $post_id );
		}
	}

	/**
	 * Modify search arguments to exclude locked articles for non-logged-in users.
	 *
	 * @param array $args The original search arguments.
	 * @return array Modified search arguments.
	 */
	public function modify_search_args( $args ) {
		if ( ! is_user_logged_in() ) {
			$args['meta_query'] = isset( $args['meta_query'] ) ? $args['meta_query'] : array();
			$args['meta_query'][] = $this->get_unlocked_meta_query();
		}
		return $args;
	}

	/**
	 * Check if an article is locked.
	 *
	 * @param int $post_id The post ID to check.
	 * @return bool True if the article is locked, false otherwise.
	 */
	public function is_article_locked( $post_id ) {
		return 'on' === get_post_meta( $post_id, self::META_KEY, true );
	}

	/**
	 * Get the locked message HTML.
	 *
	 * @param int $post_id The post ID.
	 * @return string The HTML for the locked message.
	 */
	private function get_locked_message( $post_id ) {
		$login_url = wp_login_url( get_permalink( $post_id ) );
		return sprintf(
			'<div class="wpchill-kb-locked-message">
				<h2>%s</h2>
				<p>%s</p>
				<p><a href="%s" class="wpchill-kb-login-button">%s</a></p>
			</div>',
			esc_html__( 'This article is for logged-in users only', 'wpchill-kb' ),
			esc_html__( 'To access this content, please log in or create an account.', 'wpchill-kb' ),
			esc_url( $login_url ),
			esc_html__( 'Log In', 'wpchill-kb' )
		);
	}

	/**
	 * Get the meta query for unlocked articles.
	 *
	 * @return array The meta query array.
	 */
	private function get_unlocked_meta_query() {
		return array(
			'relation' => 'OR',
			array(
				'key'     => self::META_KEY,
				'value'   => 'on',
				'compare' => '!=',
			),
			array(
				'key'     => self::META_KEY,
				'compare' => 'NOT EXISTS',
			),
		);
	}
}
