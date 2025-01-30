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
	const TYPE_META_KEY = '_wpchill_kb_locked_type';

	/**
	 * Meta key used to store the locked status of an article.
	 *
	 * @var string
	 */
	const PRODUCTS_META_KEY = '_wpchill_kb_locked_products';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes_kb', array( $this, 'add_lock_meta_box' ) );
		add_action( 'save_post_kb', array( $this, 'save_lock_meta_box' ) );
		add_filter( 'the_content', array( $this, 'filter_locked_content' ), 999 );
		add_filter( 'wpchill_kb_article_classes', array( $this, 'add_locked_class' ), 10, 2 );
		add_filter( 'wpchill_kb_search_args', array( $this, 'modify_search_args' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );
	}

	/**
	 * Add the lock meta box to the KB post editor.
	 *
	 * @param \WP_Post $post The post object.
	 * @return void
	 */
	public function add_lock_meta_box() {
		add_meta_box(
			'wpchill_kb_lock_article',
			__( 'Article Access', 'wpchill-kb' ),
			array( $this, 'render_lock_meta_box' ),
			'kb',
			'side',
			'high',
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

		$type      = get_post_meta( $post->ID, self::TYPE_META_KEY, true );
		$selection = get_post_meta( $post->ID, self::PRODUCTS_META_KEY, true );

		?>
			<div id="lock-article-metabox" 
				data-postId="<?php echo esc_attr( $post->ID ); ?>"
				data-type="<?php echo ! empty( $type ) ? esc_attr( $type ) : 'not_locked'; ?>"
				data-selected="<?php echo ! empty( $selection ) ? esc_attr( $selection ) : '[]'; ?>">
			</div>
		<?php
	}

	/**
	 * Save the lock status meta box for a post.
	 *
	 * @param int      $post_id The ID of the post being saved.
	 * @param \WP_Post $post    The post object being saved.
	 * @return void
	 */
	public function save_lock_meta_box( $post_id ) {
		if ( ! isset( $_POST['wpchill_kb_lock_article_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['wpchill_kb_lock_article_nonce'] ), 'wpchill_kb_lock_article' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! empty( $_POST['wpchill_kb_access_products'] ) ) {
			update_post_meta( $post_id, self::PRODUCTS_META_KEY, sanitize_text_field( wp_unslash( $_POST['wpchill_kb_access_products'] ) ) );
		}

		if ( ! empty( $_POST['wpchill_kb_access_type'] ) ) {
			update_post_meta( $post_id, self::TYPE_META_KEY, sanitize_text_field( wp_unslash( $_POST['wpchill_kb_access_type'] ) ) );
		}
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

		if ( $this->is_article_locked( $post_id ) ) {
			if ( ! is_user_logged_in() ) {
				return $this->get_locked_message( $post_id );
			}

			if ( 'active_subscription' === get_post_meta( $post_id, self::TYPE_META_KEY, true ) ) {
				$show_license_message = ProductsAPI::get_instance()->get_license_message( $post_id );
				if ( $show_license_message ) {
					remove_action( 'wpchill_kb_rating', array( ArticleRating::get_instance(), 'display_rating' ) );
					return $show_license_message;
				}
			}
		}

		return do_shortcode( $content );
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
	 * Modify search arguments to exclude locked articles for non-logged-in users.
	 *
	 * @param array $args The original search arguments.
	 * @return array Modified search arguments.
	 */
	public function modify_search_args( $args ) {
		if ( ! is_user_logged_in() ) {
			$args['meta_query']   = isset( $args['meta_query'] ) ? $args['meta_query'] : array();
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
		return 'not_locked' !== get_post_meta( $post_id, self::TYPE_META_KEY, true );
	}

	/**
	 * Get the locked message HTML.
	 *
	 * @param int $post_id The post ID.
	 * @return string The HTML for the locked message.
	 */
	private function get_locked_message( $post_id ) {
		$login_url = wp_login_url( get_permalink( $post_id ) );

		$title   = esc_html__( 'Log in to read this article.', 'wpchill-kb' );
		$message = wp_kses_post( __( '<p class="wpchill-kb-login-text">To access this content, log in or create an account by making a purchase.', 'wpchill-kb' ) );
		$buttons = sprintf( '<a href="/pricing" class="wpchill-kb-login-button">%s</a><a href="%s" class="wpchill-kb-login-button">%s</a>', esc_html__( 'See pricing', 'wpchill-kb' ), esc_url( $login_url ), esc_html__( 'Log in', 'wpchill-kb' ) );

		if ( 'active_subscription' === get_post_meta( $post_id, self::TYPE_META_KEY, true ) ) {
			$products_api      = ProductsAPI::get_instance();
			$cheapest_download = 0;
			$purchase_url      = '';
			if ( $products_api->is_edd_active() ) {
				$cheapest_download = $products_api->get_cheapest_edd_products( $post_id, true );
				$purchase_url      = add_query_arg(
					array(
						'edd_action'  => 'add_to_cart',
						'download_id' => $cheapest_download,
					),
					esc_url_raw( edd_get_checkout_uri() )
				);
			} elseif ( $products_api->is_woo_active() ) {
				$cheapest_download = $products_api->get_cheapest_woo_products( $post_id, true );
				$purchase_url      = add_query_arg(
					array(
						'add-to-cart' => $cheapest_download,
					),
					esc_url_raw( wc_get_checkout_url() )
				);
			}
			if ( ! empty( $cheapest_download ) && 0 !== $cheapest_download ) {
				$title    = esc_html__( 'You need a subscription to read this article.', 'wpchill-kb' );
				$message  = sprintf( wp_kses_post( __( '<p class="wpchill-kb-sub-req-text">To see this article, you must have an active subscription of at least <strong>%s</strong></p>', 'wpchill-kb' ) ), html_entity_decode( get_the_title( $cheapest_download ), ENT_QUOTES, 'UTF-8' ) );
				$buttons  = sprintf( '<a href="%s" class="wpchill-kb-login-button">%s</a>', esc_url( $login_url ), esc_html__( 'Log in', 'wpchill-kb' ) );
				$buttons .= sprintf( '<a href="%s" class="wpchill-kb-login-button">%s</a>', esc_url( $purchase_url ), esc_html__( 'Purchase plan', 'wpchill-kb' ) );
			}
		}

		return sprintf(
			'<div class="wpchill-kb-locked-message">
				<h2 style="font-size:30px;">%s</h2>
				%s
				<p>%s</p>
			</div>',
			$title,
			$message,
			$buttons
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
				'key'     => self::TYPE_META_KEY,
				'value'   => 'not_locked',
				'compare' => '==',
			),
			array(
				'key'     => self::TYPE_META_KEY,
				'compare' => 'NOT EXISTS',
			),
		);
	}

	/**
	 * Registers the admin react scripts for article access selector.
	 *
	 * @return void
	 */
	public function register_admin_scripts() {

		$screen = get_current_screen();
		// Only load in KB article edit screen
		if ( ! isset( $screen->post_type ) || ! isset( $screen->base ) || 'kb' !== $screen->post_type || 'post' !== $screen->base ) {
			return;
		}

		$asset_file = require WPCHILL_KB_PLUGIN_DIR . '/assets/lock-select/index.asset.php';
		$enqueue    = array(
			'handle'       => 'wpchill-kb-lock-select',
			'dependencies' => $asset_file['dependencies'],
			'version'      => $asset_file['version'],
			'script'       => WPCHILL_KB_PLUGIN_URL . '/assets/lock-select/index.js',
			'style'        => WPCHILL_KB_PLUGIN_URL . '/assets/lock-select/index.css',
		);

		wp_enqueue_script(
			$enqueue['handle'],
			$enqueue['script'],
			$enqueue['dependencies'],
			$enqueue['version'],
			true
		);

		wp_enqueue_style(
			$enqueue['handle'],
			$enqueue['style'],
			array( 'wp-components' ),
			$enqueue['version']
		);
	}

	/**
	 * Registers the frontend react scripts for licenses/subscriptions renews and purchases from locked KB articles.
	 *
	 * @return void
	 */
	public function register_scripts() {
		if ( ! is_singular( 'kb' ) ) {
			return;
		}

		$asset_file = require WPCHILL_KB_PLUGIN_DIR . '/assets/license-modal/index.asset.php';
		$enqueue    = array(
			'handle'       => 'wpchill-kb-license-modal',
			'dependencies' => $asset_file['dependencies'],
			'version'      => $asset_file['version'],
			'script'       => WPCHILL_KB_PLUGIN_URL . '/assets/license-modal/index.js',
			'style'        => WPCHILL_KB_PLUGIN_URL . '/assets/license-modal/index.css',
		);

		wp_enqueue_script(
			$enqueue['handle'],
			$enqueue['script'],
			$enqueue['dependencies'],
			$enqueue['version'],
			true
		);

		wp_enqueue_style(
			$enqueue['handle'],
			$enqueue['style'],
			array( 'wp-components' ),
			$enqueue['version']
		);
	}
}
