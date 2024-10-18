<?php
get_header();

$terms  = get_terms(
	array(
		'taxonomy'   => 'kb_category',
		'hide_empty' => false,
	)
);
$plugin = new WPChill\KB\Plugin();

?>
	<div class="wpchill-kb-wrapper">
		<header class="wpchill-kb-header">
			<div class="wpchill-kb-header-left">
				<h1 class="wpchill-kb-title"><?php echo esc_html( apply_filters( 'wpchill_kb_archive_title', __( 'Knowledge Base', 'wpchill-kb' ) ) ); ?></h1>
			</div>
			<div class="wpchill-kb-header-right">
				<?php echo $plugin->get_search_form(); ?>
			</div>
		</header>

		<?php if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) : ?>
			<ul class="wpchill-kb-category-wrapper">
				<?php
				foreach ( $terms as $term ) :
					$term_color    = get_term_meta( $term->term_id, 'category_color', true );
					$term_color    = $term_color ? $term_color : '#4d4dff'; // Default color
					$article_count = $term->count;

					// Exclude locked articles from count for non-logged-in users
					if ( ! is_user_logged_in() ) {
						$args          = array(
							'post_type'      => 'kb',
							'tax_query'      => array(
								array(
									'taxonomy' => 'kb_category',
									'field'    => 'term_id',
									'terms'    => $term->term_id,
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
						$query         = new WP_Query( $args );
						$article_count = $query->found_posts;
					}
					?>
					<li class="wpchill-kb-<?php echo esc_attr( $term->slug ); ?>">
						<div class="wpchill-kb-category-icon" style="background-color: <?php echo esc_attr( $term_color ); ?>"></div>
						<h3>
							<a href="<?php echo esc_url( get_term_link( $term ) ); ?>">
								<?php echo esc_html( $term->name ); ?>
							</a>
						</h3>
						<p><?php echo esc_html( $term->description ); ?></p>
						<span class="wpchill-kb-count">
						<?php
						printf(
							esc_html( _n( '%s article', '%s articles', $article_count, 'wpchill-kb' ) ),
							number_format_i18n( $article_count )
						);
						?>
					</span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p><?php esc_html_e( 'No categories found.', 'wpchill-kb' ); ?></p>
		<?php endif; ?>
	</div>
<?php
get_footer();
?>