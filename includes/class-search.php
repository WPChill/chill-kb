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
			$this->log_debug( 'AJAX search started' );

			// Check nonce
			if ( ! check_ajax_referer( 'wpchill_kb_search_nonce', 'security', false ) ) {
				throw new \Exception( __( 'Security check failed', 'wpchill-kb' ) );
			}

			$this->log_debug( 'Nonce check passed' );

			if ( ! empty( $_POST[ $this->honeypot_field_name ] ) ) {
				$this->log_debug( 'Honeypot field filled - potential spam' );
				throw new \Exception( __( 'Invalid request', 'wpchill-kb' ) );
			}

			// Ensure search term is provided
			if ( empty( $_POST['search'] ) ) {
				throw new \Exception( __( 'No search term provided', 'wpchill-kb' ) );
			}

			$search_term = sanitize_text_field( wp_unslash( $_POST['search'] ) );
			$this->log_debug( 'Search term: ' . $search_term );

			// Check for spam using Akismet
			if ( $this->is_spam( $search_term ) ) {
				throw new \Exception( __( 'This search query has been identified as potential spam.', 'wpchill-kb' ) );
			}

			$this->log_debug( 'Spam check passed' );

			$args = array(
				'post_type'      => 'kb',
				'post_status'    => 'publish',
				's'              => $search_term,
				'posts_per_page' => 5,
			);

			$query = new \WP_Query( $args );
			$this->log_debug( 'WP_Query executed' );

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

			$this->log_debug( 'Search results: ' . print_r( $results, true ) );

			wp_send_json_success( $results );
		} catch ( \Exception $e ) {
			$this->log_debug( 'Exception caught: ' . $e->getMessage() );
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
					'code'    => $e->getCode(),
				)
			);
		}
	}

	private function is_spam( $search_term ) {
		$this->log_debug( 'Checking for spam' );
		if ( $this->is_akismet_active() ) {
			global $akismet_api_host, $akismet_api_port;

			$akismet_data = $this->get_akismet_data( $search_term );

			$query_string = http_build_query( $akismet_data );
			$request      = "POST /1.1/comment-check HTTP/1.0\r\n" .
						'Host: ' . $akismet_api_host . "\r\n" .
						"Content-Type: application/x-www-form-urlencoded\r\n" .
						'Content-Length: ' . strlen( $query_string ) . "\r\n" .
						'User-Agent: ' . $akismet_data['user_agent'] . "\r\n" .
						"\r\n" .
						$query_string;
			$response     = '';
			$fs = @fsockopen( $akismet_api_host, $akismet_api_port, $errno, $errstr, 10 );
			if ( false !== $fs ) {
				fwrite( $fs, $request );
				while ( ! feof( $fs ) ) {
					$response .= fgets( $fs, 1160 );
				}
				fclose( $fs );
				$response = explode( "\r\n\r\n", $response, 2 );
				$this->log_debug( 'Akismet raw response: ' . $response[1] );
				return true === $response[1];
			} else {
				$this->log_debug( 'Failed to connect to Akismet' );
				return false;
			}
		}

		$this->log_debug( 'Akismet not available or not active' );
		return false; // If Akismet is not available, don't block any searches
	}

	private function get_akismet_data( $search_term ) {
		return array(
			'blog'                     => get_option( 'home' ),
			'user_ip'                  => $_SERVER['REMOTE_ADDR'],
			'user_agent'               => $_SERVER['HTTP_USER_AGENT'],
			'referrer'                 => $_SERVER['HTTP_REFERER'],
			'comment_type'             => 'message',
			'comment_author'           => '',
			'comment_author_email'     => '',
			'comment_author_url'       => '',
			'comment_content'          => $search_term,
			'blog_lang'                => get_locale(),
			'blog_charset'             => get_option( 'blog_charset' ),
			'permalink'                => get_site_url(),
			'honeypot_field_name'      => $this->honeypot_field_name,
			$this->honeypot_field_name => $_POST[ $this->honeypot_field_name ],
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

	private function log_debug( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
			error_log( '[WPChill KB Search] ' . $message );
		}
	}

	public function get_search_form() {
		ob_start();
		?>
		<form role="search" method="get" class="wpchill-kb-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label for="wpchill-kb-search-input" class="screen-reader-text"><?php esc_html_e( 'Search Knowledge Base:', 'wpchill-kb' ); ?></label>
			<input type="search" id="wpchill-kb-search-input" class="wpchill-kb-search-field" placeholder="<?php esc_attr_e( 'Search Knowledge Base...', 'wpchill-kb' ); ?>" value="<?php echo get_search_query(); ?>" name="s" />
			<input type="hidden" name="post_type" value="wpchill_kb" />
			<input type="text" name="<?php echo esc_attr( $this->honeypot_field_name ); ?>" value="" style="display:none !important; visibility:hidden !important; height:0 !important; width:0 !important; opacity:0 !important; pointer-events:none !important;" tabindex="-1" autocomplete="off">
			<button type="submit" class="wpchill-kb-search-submit"><?php esc_html_e( 'Search', 'wpchill-kb' ); ?></button>
		</form>
		<?php
		return ob_get_clean();
	}

	public function filter_search_query( $query ) {
		if ( $query->is_search() && ! is_admin() && isset( $_GET['post_type'] ) && 'wpchill_kb' === $_GET['post_type'] ) {
			$query->set( 'post_type', 'kb' );
		}
	}
}
