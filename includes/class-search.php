<?php
namespace WPChill\KB;

class Search {

	private $honeypot_field_name = 'kb_hp_check';
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_wpchill_kb_search', array( $this, 'ajax_search' ) );
		add_action( 'wp_ajax_nopriv_wpchill_kb_search', array( $this, 'ajax_search' ) );
		add_action( 'pre_get_posts', array( $this, 'filter_search_query' ) );
	}

	public function enqueue_scripts() {

		// Only load on KB category and article pages.
		if ( ! is_post_type_archive( 'kb' ) && ! is_singular( 'kb' ) && ! is_tax( 'kb_category' ) ) {
			return;
		}

		wp_enqueue_script( 'jquery-ui-autocomplete' );
		wp_enqueue_script(
			'wpchill-kb-search',
			WPCHILL_KB_PLUGIN_URL . 'assets/js/wpchill-kb-search.js',
			array( 'jquery', 'jquery-ui-autocomplete' ),
			WPCHILL_KB_VERSION,
			true
		);

		wp_localize_script(
			'wpchill-kb-search',
			'wpchill_kb_search',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'security' => wp_create_nonce( 'wpchill_kb_search_nonce' ),
			)
		);
	}

	public function ajax_search() {
		try {
			// Check nonce
			if ( ! check_ajax_referer( 'wpchill_kb_search_nonce', 'security', false ) ) {
				throw new \Exception( __( 'Security check failed', 'wpchill-kb' ) );
			}

			if ( ! empty( $_POST[ $this->honeypot_field_name ] ) ) {
				throw new \Exception( __( 'Invalid request', 'wpchill-kb' ) );
			}

			// Ensure search term is provided
			if ( empty( $_POST['search'] ) ) {
				throw new \Exception( __( 'No search term provided', 'wpchill-kb' ) );
			}

			$search_term = sanitize_text_field( wp_unslash( $_POST['search'] ) );

			// Check for spam using Akismet
			if ( $this->is_spam( $search_term, $_POST ) ) {
				throw new \Exception( __( 'This search query has been identified as potential spam.', 'wpchill-kb' ) );
			}

			$args = array(
				'post_type'      => 'kb',
				'post_status'    => 'publish',
				's'              => $search_term,
				'posts_per_page' => 5,
			);

			$query = new \WP_Query( $args );

			$results = array();

			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();
					$results[] = array(
						'title' => get_the_title(),
						'url'   => get_permalink(),
					);
				}
				wp_reset_postdata();
			}

			wp_send_json_success( $results );
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
					'code'    => $e->getCode(),
				)
			);
		}
	}

	private function is_spam( $search_term, $postdata ) {
		if ( $this->is_akismet_active() ) {
			global $akismet_api_host, $akismet_api_port;

			$akismet_data = $this->get_akismet_data( $search_term, $postdata );

			$query_string = http_build_query( $akismet_data );
			$request      = "POST /1.1/comment-check HTTP/1.0\r\n" .
						'Host: ' . $akismet_api_host . "\r\n" .
						"Content-Type: application/x-www-form-urlencoded\r\n" .
						'Content-Length: ' . strlen( $query_string ) . "\r\n" .
						'User-Agent: ' . $akismet_data['user_agent'] . "\r\n" .
						"\r\n" .
						$query_string;
			$response     = '';
			$fs           = @fsockopen( $akismet_api_host, $akismet_api_port, $errno, $errstr, 10 ); //phpcs:ignore
			if ( false !== $fs ) {
				fwrite( $fs, $request ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
				while ( ! feof( $fs ) ) {
					$response .= fgets( $fs, 1160 );
				}
				fclose( $fs ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
				$response = explode( "\r\n\r\n", $response, 2 );
				return true === $response[1];
			} else {
				return false;
			}
		}
		return false; // If Akismet is not available, don't block any searches
	}

	private function get_akismet_data( $search_term, $postdata ) {

		$user_ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$referrer   = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';

		$data = array(
			'blog'                     => get_option( 'home' ),
			'user_ip'                  => $user_ip,
			'user_agent'               => $user_agent,
			'referrer'                 => $referrer,
			'comment_type'             => 'message',
			'comment_author'           => '',
			'comment_author_email'     => '',
			'comment_author_url'       => '',
			'comment_content'          => $search_term,
			'blog_lang'                => get_locale(),
			'blog_charset'             => get_option( 'blog_charset' ),
			'permalink'                => get_site_url(),
			'honeypot_field_name'      => $this->honeypot_field_name,
			$this->honeypot_field_name => isset( $postdata[ $this->honeypot_field_name ] ) ? sanitize_text_field( wp_unslash( $postdata[ $this->honeypot_field_name ] ) ) : '',
		);

		// Add comment context
		$comment_context = $this->get_comment_context();
		foreach ( $comment_context as $context ) {
			$data['comment_context[]'] = $context;
		}

		return $data;
	}

	private function get_comment_context() {
		$context = array();

		// Get all KB categories
		$categories = get_terms(
			array(
				'taxonomy'   => 'kb_category',
				'fields'     => 'names',
				'hide_empty' => false,
			)
		);

		if ( ! is_wp_error( $categories ) ) {
			$context = array_merge( $context, $categories );
		}

		// Get all KB tags
		$tags = get_terms(
			array(
				'taxonomy'   => 'kb_tag',
				'fields'     => 'names',
				'hide_empty' => false,
			)
		);

		if ( ! is_wp_error( $tags ) ) {
			$context = array_merge( $context, $tags );
		}

		return $context;
	}
	private function is_akismet_active() {
		return is_callable( array( 'Akismet', 'get_api_key' ) ) && (bool) \Akismet::get_api_key();
	}

	public function get_search_form() {
		?>
		<form role="search" method="get" class="wpchill-kb-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label for="wpchill-kb-search-input" class="screen-reader-text"><?php esc_html_e( 'Search Knowledge Base:', 'wpchill-kb' ); ?></label>
			<input type="search" id="wpchill-kb-search-input" class="wpchill-kb-search-field" placeholder="<?php esc_attr_e( 'Search Knowledge Base...', 'wpchill-kb' ); ?>" value="<?php echo get_search_query(); ?>" name="s" />
			<input type="hidden" name="post_type" value="wpchill_kb" />
			<input type="text" name="<?php echo esc_attr( $this->honeypot_field_name ); ?>" value="" style="display:none !important; visibility:hidden !important; height:0 !important; width:0 !important; opacity:0 !important; pointer-events:none !important;" tabindex="-1" autocomplete="off">
			<button type="submit" class="wpchill-kb-search-submit"><?php esc_html_e( 'Search', 'wpchill-kb' ); ?></button>
		</form>
		<?php
	}

	public function filter_search_query( $query ) {
		if ( $query->is_search() && ! is_admin() && isset( $_GET['post_type'] ) && 'wpchill_kb' === $_GET['post_type'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$query->set( 'post_type', 'kb' );
		}
	}
}
