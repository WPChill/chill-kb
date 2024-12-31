<?php

namespace WPChill\KB;

class Taxonomy {

	public function register() {

		$labels = array(
			'name'              => _x( 'KB Categories', 'taxonomy general name', 'wpchill-kb' ),
			'singular_name'     => _x( 'KB Category', 'taxonomy singular name', 'wpchill-kb' ),
			'search_items'      => __( 'Search KB Categories', 'wpchill-kb' ),
			'all_items'         => __( 'All KB Categories', 'wpchill-kb' ),
			'parent_item'       => __( 'Parent KB Category', 'wpchill-kb' ),
			'parent_item_colon' => __( 'Parent KB Category:', 'wpchill-kb' ),
			'edit_item'         => __( 'Edit KB Category', 'wpchill-kb' ),
			'update_item'       => __( 'Update KB Category', 'wpchill-kb' ),
			'add_new_item'      => __( 'Add New KB Category', 'wpchill-kb' ),
			'new_item_name'     => __( 'New KB Category Name', 'wpchill-kb' ),
			'menu_name'         => __( 'KB Categories', 'wpchill-kb' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'kb-cat' ),
			'show_in_rest'      => true,
		);

		$args = apply_filters( 'wpchill_kb_taxonomy_args', $args );

		register_taxonomy( 'kb_category', array( 'kb' ), $args );
		add_action( 'kb_category_add_form_fields', array( $this, 'add_term_fields' ) );
		add_action( 'kb_category_edit_form_fields', array( $this, 'edit_term_fields' ), 10, 2 );
		add_action( 'created_kb_category', array( $this, 'create_term_fields' ) );
		add_action( 'edited_kb_category', array( $this, 'update_term_fields' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );
	}

	public function add_term_fields() {
		$dashicons = $this->get_dashicons();
		?>
		<div class="form-field">
			<label for="wpchill_kb_cat_icon"><?php esc_html_e( 'Select icon', 'wpchill-kb' ); ?></label>
			<div class="wpchill-kb-dropdown">
				<div class="wpchill-kb-dropdown-selected">
					<span class="dashicons dashicons-category"></span>
					<span class="selected-label"><?php esc_html_e( 'Category', 'wpchill-kb' ); ?></span>
				</div>
				<div class="wpchill-kb-dropdown-options">
					<?php foreach ( $dashicons as $class => $label ) : ?>
						<div class="wpchill-kb-dropdown-option" data-value="<?php echo esc_attr( $class ); ?>">
							<span class="dashicons <?php echo esc_attr( $class ); ?>"></span>
							<?php echo esc_html( $label ); ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<input type="hidden" id="wpchill_kb_cat_icon" name="wpchill_kb_cat_icon" value="dashicons-category">
		</div>
		<div class="form-field">
			<label for="wpchill_kb_cat_color"><?php esc_html_e( 'Choose color', 'wpchill-kb' ); ?></label>
			<input type="text" id="wpchill_kb_cat_color" value="#3465c6" name="wpchill_kb_cat_color" class='wpchill-kb-color-picker' />
		</div>
		<?php
	}

	public function edit_term_fields( $term, $taxonomy ) {
		$dashicons = $this->get_dashicons();
		$icon      = get_term_meta( $term->term_id, 'icon', true );
		$color     = get_term_meta( $term->term_id, 'color', true );

		?>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="wpchill_kb_cat_icon"><?php esc_html_e( 'Select icon', 'wpchill-kb' ); ?></label>
			</th>
			<td>
				<div class="wpchill-kb-dropdown">
					<div class="wpchill-kb-dropdown-selected">
						<span class="dashicons <?php echo ! empty( $icon ) ? esc_attr( $icon ) : 'dashicons-category'; ?>"></span>
						<span class="selected-label"><?php echo ! empty( $icon ) && ! empty( $dashicons[ $icon ] ) ? esc_html( $dashicons[ $icon ] ) : esc_html__( 'Category', 'wpchill-kb' ); ?></span>
					</div>
					<div class="wpchill-kb-dropdown-options">
						<?php foreach ( $dashicons as $class => $label ) : ?>
							<div class="wpchill-kb-dropdown-option" data-value="<?php echo esc_attr( $class ); ?>">
								<span class="dashicons <?php echo esc_attr( $class ); ?>"></span>
								<?php echo esc_html( $label ); ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
				<input type="hidden" id="wpchill_kb_cat_icon" name="wpchill_kb_cat_icon" value="<?php echo ! empty( $icon ) ? esc_attr( $icon ) : 'dashicons-category'; ?>">
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="wpchill_kb_cat_color"><?php esc_html_e( 'Choose color', 'wpchill-kb' ); ?></label>
			</th>
			<td>
				<input type="text" id="wpchill_kb_cat_color" value="<?php echo ! empty( $color ) ? esc_attr( $color ) : '#3465c6'; ?>" name="wpchill_kb_cat_color" class='wpchill-kb-color-picker' />
			</td>
		</tr>
		<?php
	}


	public function create_term_fields( $term_id ) {
		check_ajax_referer( 'add-tag', '_wpnonce_add-tag' );

		if ( isset( $_POST['wpchill_kb_cat_color'] ) ) {
			update_term_meta(
				$term_id,
				'color',
				sanitize_text_field( wp_unslash( $_POST['wpchill_kb_cat_color'] ) )
			);
		}

		if ( isset( $_POST['wpchill_kb_cat_icon'] ) ) {
			update_term_meta(
				$term_id,
				'icon',
				sanitize_text_field( wp_unslash( $_POST['wpchill_kb_cat_icon'] ) )
			);
		}
	}

	public function update_term_fields( $term_id ) {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'update-tag_' . $term_id ) ) {
			wp_die();
		}

		if ( isset( $_POST['wpchill_kb_cat_color'] ) ) {
			update_term_meta(
				$term_id,
				'color',
				sanitize_text_field( wp_unslash( $_POST['wpchill_kb_cat_color'] ) )
			);
		}

		if ( isset( $_POST['wpchill_kb_cat_icon'] ) ) {
			update_term_meta(
				$term_id,
				'icon',
				sanitize_text_field( wp_unslash( $_POST['wpchill_kb_cat_icon'] ) )
			);
		}
	}

	public function register_scripts() {

		$screen = get_current_screen();
		// Only load in KB category edit screen
		if ( ! isset( $screen->post_type ) || ! isset( $screen->taxonomy ) || 'kb' !== $screen->post_type || 'kb_category' !== $screen->taxonomy ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'wpchill-kb-styles-admin', WPCHILL_KB_PLUGIN_URL . 'assets/css/admin/wpchill-kb-styles-admin.css', array(), WPCHILL_KB_VERSION );
		wp_enqueue_script( 'modula-category-scripts', WPCHILL_KB_PLUGIN_URL . 'assets/js/admin/wpchill-kb-category.js', array( 'jquery', 'wp-color-picker' ), WPCHILL_KB_VERSION, true );
	}

	private function get_dashicons() {
		return array(
			'dashicons-category'             => __( 'Category', 'wpchill-kb' ),
			'dashicons-dashboard'            => __( 'Dashboard', 'wpchill-kb' ),
			'dashicons-admin-post'           => __( 'Admin Post', 'wpchill-kb' ),
			'dashicons-admin-media'          => __( 'Admin Media', 'wpchill-kb' ),
			'dashicons-admin-links'          => __( 'Admin Links', 'wpchill-kb' ),
			'dashicons-admin-page'           => __( 'Admin Page', 'wpchill-kb' ),
			'dashicons-admin-comments'       => __( 'Admin Comments', 'wpchill-kb' ),
			'dashicons-admin-appearance'     => __( 'Admin Appearance', 'wpchill-kb' ),
			'dashicons-admin-users'          => __( 'Admin Users', 'wpchill-kb' ),
			'dashicons-admin-tools'          => __( 'Admin Tools', 'wpchill-kb' ),
			'dashicons-admin-settings'       => __( 'Admin Settings', 'wpchill-kb' ),
			'dashicons-admin-network'        => __( 'Admin Network', 'wpchill-kb' ),
			'dashicons-admin-generic'        => __( 'Admin Generic', 'wpchill-kb' ),
			'dashicons-admin-home'           => __( 'Admin Home', 'wpchill-kb' ),
			'dashicons-admin-site'           => __( 'Admin Site', 'wpchill-kb' ),
			'dashicons-format-standard'      => __( 'Standard Format', 'wpchill-kb' ),
			'dashicons-format-image'         => __( 'Image Format', 'wpchill-kb' ),
			'dashicons-format-gallery'       => __( 'Gallery Format', 'wpchill-kb' ),
			'dashicons-format-video'         => __( 'Video Format', 'wpchill-kb' ),
			'dashicons-format-audio'         => __( 'Audio Format', 'wpchill-kb' ),
			'dashicons-format-chat'          => __( 'Chat Format', 'wpchill-kb' ),
			'dashicons-format-status'        => __( 'Status Format', 'wpchill-kb' ),
			'dashicons-format-aside'         => __( 'Aside Format', 'wpchill-kb' ),
			'dashicons-format-quote'         => __( 'Quote Format', 'wpchill-kb' ),
			'dashicons-format-links'         => __( 'Links Format', 'wpchill-kb' ),
			'dashicons-media-archive'        => __( 'Media Archive', 'wpchill-kb' ),
			'dashicons-media-audio'          => __( 'Media Audio', 'wpchill-kb' ),
			'dashicons-media-code'           => __( 'Media Code', 'wpchill-kb' ),
			'dashicons-media-default'        => __( 'Media Default', 'wpchill-kb' ),
			'dashicons-media-document'       => __( 'Media Document', 'wpchill-kb' ),
			'dashicons-media-interactive'    => __( 'Media Interactive', 'wpchill-kb' ),
			'dashicons-media-spreadsheet'    => __( 'Media Spreadsheet', 'wpchill-kb' ),
			'dashicons-media-text'           => __( 'Media Text', 'wpchill-kb' ),
			'dashicons-media-video'          => __( 'Media Video', 'wpchill-kb' ),
			'dashicons-feedback'             => __( 'Feedback', 'wpchill-kb' ),
			'dashicons-welcome-learn-more'   => __( 'Learn More', 'wpchill-kb' ),
			'dashicons-welcome-write-blog'   => __( 'Write Blog', 'wpchill-kb' ),
			'dashicons-search'               => __( 'Search', 'wpchill-kb' ),
			'dashicons-book'                 => __( 'Book', 'wpchill-kb' ),
			'dashicons-tag'                  => __( 'Tag', 'wpchill-kb' ),
			'dashicons-lightbulb'            => __( 'Lightbulb', 'wpchill-kb' ),
			'dashicons-clipboard'            => __( 'Clipboard', 'wpchill-kb' ),
			'dashicons-visibility'           => __( 'Visibility', 'wpchill-kb' ),
			'dashicons-star-filled'          => __( 'Star Filled', 'wpchill-kb' ),
			'dashicons-star-half'            => __( 'Star Half', 'wpchill-kb' ),
			'dashicons-star-empty'           => __( 'Star Empty', 'wpchill-kb' ),
			'dashicons-heart'                => __( 'Heart', 'wpchill-kb' ),
			'dashicons-megaphone'            => __( 'Megaphone', 'wpchill-kb' ),
			'dashicons-universal-access'     => __( 'Universal Access', 'wpchill-kb' ),
			'dashicons-universal-access-alt' => __( 'Universal Access Alt', 'wpchill-kb' ),
			'dashicons-editor-help'          => __( 'Editor Help', 'wpchill-kb' ),
			'dashicons-editor-ul'            => __( 'Editor UL', 'wpchill-kb' ),
			'dashicons-editor-ol'            => __( 'Editor OL', 'wpchill-kb' ),
			'dashicons-editor-quote'         => __( 'Editor Quote', 'wpchill-kb' ),
			'dashicons-editor-code'          => __( 'Editor Code', 'wpchill-kb' ),
			'dashicons-chart-line'           => __( 'Chart Line', 'wpchill-kb' ),
			'dashicons-chart-pie'            => __( 'Chart Pie', 'wpchill-kb' ),
			'dashicons-chart-bar'            => __( 'Chart Bar', 'wpchill-kb' ),
			'dashicons-analytics'            => __( 'Analytics', 'wpchill-kb' ),
			'dashicons-rest-api'             => __( 'REST API', 'wpchill-kb' ),
			'dashicons-editor-table'         => __( 'Editor Table', 'wpchill-kb' ),
			'dashicons-performance'          => __( 'Performance', 'wpchill-kb' ),
			'dashicons-awards'               => __( 'Awards', 'wpchill-kb' ),
			'dashicons-tickets'              => __( 'Tickets', 'wpchill-kb' ),
			'dashicons-flag'                 => __( 'Flag', 'wpchill-kb' ),
			'dashicons-networking'           => __( 'Networking', 'wpchill-kb' ),
			'dashicons-location'             => __( 'Location', 'wpchill-kb' ),
			'dashicons-groups'               => __( 'Groups', 'wpchill-kb' ),
			'dashicons-cloud'                => __( 'Cloud', 'wpchill-kb' ),
			'dashicons-cloud-upload'         => __( 'Cloud Upload', 'wpchill-kb' ),
			'dashicons-cloud-download'       => __( 'Cloud Download', 'wpchill-kb' ),
		);
	}
}
