<?php

/**
 * Class ArticleRating
 *
 * Handles the rating functionality for KB articles including enqueueing scripts,
 * rendering rating forms, processing AJAX requests, and managing custom columns in the admin area.
 */

namespace WPChill\KB;

class ArticleRating {
	private static $rating_displayed = false;
	private static $instance         = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wpchill_kb_rating', array( $this, 'display_rating' ) );
		add_action( 'wp_ajax_wpchill_kb_rate_article', array( $this, 'rate_article' ) );
		add_action( 'wp_ajax_nopriv_wpchill_kb_rate_article', array( $this, 'rate_article' ) );
		add_action( 'wp_footer', array( $this, 'reset_rating_flag' ) );

		// Admin-related hooks
		add_filter( 'manage_kb_posts_columns', array( $this, 'add_kb_columns' ) );
		add_action( 'manage_kb_posts_custom_column', array( $this, 'display_kb_columns' ), 10, 2 );
		add_filter( 'manage_edit-kb_sortable_columns', array( $this, 'make_kb_columns_sortable' ) );
		add_action( 'pre_get_posts', array( $this, 'kb_custom_orderby' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	public function display_rating() {
		global $post;

		if ( self::$rating_displayed || ! $post || 'kb' !== $post->post_type ) {
			return;
		}

		$article_locking = new ArticleLocking();
		if ( $article_locking->is_article_locked( $post->ID ) && ! is_user_logged_in() ) {
			return;
		}

		$likes    = get_post_meta( $post->ID, 'wpchill_kb_likes', true );
		$likes    = ( $likes && ! empty( $likes ) ) ? $likes : 0;
		$dislikes = get_post_meta( $post->ID, 'wpchill_kb_dislikes', true );
		$dislikes = ( $dislikes && ! empty( $dislikes ) ) ? $dislikes : 0;

		?>
		<div class="wpchill-kb-rating" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
			<div class="wpchill-kb-rating-left">
				<span class="wpchill-kb-rating-question">Was this article helpful?</span>
				<div class="wpchill-kb-rating-buttons">
					<button class="wpchill-kb-rating-button wpchill-kb-like" data-rating="like">👍 Yes</button>
					<button class="wpchill-kb-rating-button wpchill-kb-dislike" data-rating="dislike">👎 No</button>
				</div>
			</div>
			<div class="wpchill-kb-rating-right">
				<span class="wpchill-kb-likes"><?php echo esc_html( $likes ); ?> Yes</span>
				<span class="wpchill-kb-dislikes"><?php echo esc_html( $dislikes ); ?> No</span>
			</div>
		</div>
		<?php

		self::$rating_displayed = true;
	}

	public function enqueue_scripts() {
		if ( is_singular( 'kb' ) ) {
			wp_enqueue_script( 'wpchill-kb-rating', WPCHILL_KB_PLUGIN_URL . 'assets/js/wpchill-kb-rating.js', array( 'jquery' ), WPCHILL_KB_VERSION, true );
			wp_localize_script(
				'wpchill-kb-rating',
				'wpchillKbRating',
				array(
					'ajax_url'  => admin_url( 'admin-ajax.php' ),
					'nonce'     => wp_create_nonce( 'wpchill_kb_rating' ), // Changed to match the check_ajax_referer
					'thank_you' => esc_html__( 'Thank you for your feedback!', 'wpchill-kb' ),
				)
			);
		}
	}



	public function reset_rating_flag() {
		self::$rating_displayed = false;
	}

	public function add_kb_columns( $columns ) {
		$columns['likes']    = esc_html__( 'Likes', 'wpchill-kb' );
		$columns['dislikes'] = esc_html__( 'Dislikes', 'wpchill-kb' );
		return $columns;
	}

	public function display_kb_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'likes':
				$likes = get_post_meta( $post_id, 'wpchill_kb_likes', true );
				echo esc_html( $likes ? $likes : '0' );
				break;
			case 'dislikes':
				$dislikes = get_post_meta( $post_id, 'wpchill_kb_dislikes', true );
				echo esc_html( $dislikes ? $dislikes : '0' );
				break;
		}
	}

	public function make_kb_columns_sortable( $columns ) {
		$columns['likes']    = 'likes';
		$columns['dislikes'] = 'dislikes';
		return $columns;
	}

	public function kb_custom_orderby( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( 'likes' === $orderby ) {
			$query->set( 'meta_key', 'wpchill_kb_likes' );
			$query->set( 'orderby', 'meta_value_num' );
		}

		if ( 'dislikes' === $orderby ) {
			$query->set( 'meta_key', 'wpchill_kb_dislikes' );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}

	public function enqueue_admin_scripts( $hook ) {
		if ( 'edit.php' !== $hook || ! isset( $_GET['post_type'] ) || 'kb' !== $_GET['post_type'] ) {
			return;
		}
		wp_enqueue_style( 'wpchill-kb-admin-styles', WPCHILL_KB_PLUGIN_URL . 'assets/css/admin-styles.css', array(), WPCHILL_KB_VERSION );
	}

	public function rate_article() {
		check_ajax_referer( 'wpchill_kb_rating', 'security' );

		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		$rating  = isset( $_POST['rating'] ) ? sanitize_text_field( wp_unslash( $_POST['rating'] ) ) : '';

		if ( ! $post_id || ! in_array( $rating, array( 'like', 'dislike' ), true ) ) {
			wp_send_json_error( 'Invalid data' );
		}

		$likes    = (int) get_post_meta( $post_id, 'wpchill_kb_likes', true );
		$dislikes = (int) get_post_meta( $post_id, 'wpchill_kb_dislikes', true );

		if ( 'like' === $rating ) {
			++$likes;
		} else {
			++$dislikes;
		}

		update_post_meta( $post_id, 'wpchill_kb_likes', $likes );
		update_post_meta( $post_id, 'wpchill_kb_dislikes', $dislikes );

		$total_votes     = $likes + $dislikes;
		$like_percentage = $total_votes > 0 ? round( ( $likes / $total_votes ) * 100, 1 ) : 0;

		wp_send_json_success(
			array(
				'likes'           => $likes,
				'dislikes'        => $dislikes,
				'total_votes'     => $total_votes,
				'like_percentage' => $like_percentage,
			)
		);
	}
}
