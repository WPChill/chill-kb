<?php
$wkb_plugin = new WPChill\KB\Plugin();
$wkb_plugin->get_header();

$wkb_terms = get_terms(
	array(
		'taxonomy'   => 'kb_category',
		'hide_empty' => true,
		'parent'     => 0,
	)
);

?>
	<header class="wpchill-kb-header-wrapper">
		<div class="wpchill-kb-header">
			<?php $wkb_plugin->get_search_form(); ?>
		</div>
	</header>
	<div class="wpchill-kb-wrapper">
		<?php if ( ! empty( $wkb_terms ) && ! is_wp_error( $wkb_terms ) ) : ?>
			<div class="wpchill-kb-categories-container">
				<div class="wpchill-kb-category-wrapper">
					<?php
					foreach ( $wkb_terms as $wkb_term ) :

						$wkb_category_color = get_term_meta( $wkb_term->term_id, 'color', true );
						$wkb_category_color = $wkb_category_color ? $wkb_category_color : '#4d4dff';

						$wkb_category_icon = get_term_meta( $wkb_term->term_id, 'icon', true );
						$wkb_category_icon = $wkb_category_icon ? $wkb_category_icon : 'dashicons-category';

						$wkb_article_count = $wkb_term->count;

						// Exclude locked articles from count for non-logged-in users
						if ( ! is_user_logged_in() ) {
							$wkb_args          = array(
								'post_type'      => 'kb',
								'tax_query'      => array(
									array(
										'taxonomy' => 'kb_category',
										'field'    => 'term_id',
										'terms'    => $wkb_term->term_id,
									),
								),
								'meta_query'     => array(
									'relation' => 'OR',
									array(
										'key'     => '_wpchill_kb_locked',
										'value'   => 'on',
										'compare' => '!=',
									),
									array(
										'key'     => '_wpchill_kb_locked',
										'compare' => 'NOT EXISTS',
									),
								),
								'posts_per_page' => -1,
							);
							$wkb_query         = new WP_Query( $wkb_args );
							$wkb_article_count = $wkb_query->found_posts;
						}
						?>
						<a class="wpchill-kb-category-item wpchill-kb-<?php echo esc_attr( $wkb_term->slug ); ?>" href="<?php echo esc_url( get_term_link( $wkb_term ) ); ?>">
							<span class="wpchill-kb-icon dashicons <?php echo esc_attr( $wkb_category_icon ); ?>" style="background-color: <?php echo esc_attr( $wkb_category_color ); ?>;"></span>
							
							<h3 class="wpchill-kb-category-name">
								<?php echo esc_html( $wkb_term->name ); ?>
							</h3>
							<p class="wpchill-kb-category-description" ><?php echo esc_html( $wkb_term->description ); ?></p>
							<span class="wpchill-kb-count">
								<?php
								printf(
									// translators: %s is the number of articles.
									esc_html( _n( '%s article', '%s articles', $wkb_article_count, 'wpchill-kb' ) ),
									esc_html( number_format_i18n( $wkb_article_count ) )
								);
								?>
							</span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		<?php else : ?>
			<p><?php esc_html_e( 'No categories found.', 'wpchill-kb' ); ?></p>
		<?php endif; ?>
	</div>
<?php
$wkb_plugin->get_footer();
?>
