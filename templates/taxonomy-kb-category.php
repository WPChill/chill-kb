<?php
$wkb_plugin = new WPChill\KB\Plugin();
$wkb_plugin->get_header();

$wkb_current_category = get_queried_object();

$wkb_args = array(
	'post_type'      => 'kb',
	'posts_per_page' => -1,
	'tax_query'      => array(
		array(
			'taxonomy'         => 'kb_category',
			'field'            => 'id',
			'terms'            => $wkb_current_category->term_id,
			'include_children' => false,
		),
	),
);

$wkb_articles_query = new WP_Query( $wkb_args );

$wkb_child_categories = get_terms(
	array(
		'taxonomy'   => 'kb_category',
		'parent'     => $wkb_current_category->term_id,
		'hide_empty' => false,
	)
);

?>
	<header class="wpchill-kb-header-wrapper">
		<div class="wpchill-kb-header">
			<?php echo $wkb_plugin->get_search_form(); ?>
		</div>
	</header>
	<div class="wpchill-kb-wrapper">
		<div class="wpchill-kb-page-container">
			<div class="wpchill-kb-sidebar-background"></div>
			<div class="wpchill-kb-content-container">
				<aside class="wpchill-kb-sidebar">
					<?php
					if ( is_active_sidebar( 'kb-sidebar' ) ) {
						dynamic_sidebar( 'kb-sidebar' );
					}
					?>
				</aside>
				<main class="wpchill-kb-main-content">
					<div class="wpchill-kb-main-content-wrap">
						<div class="wpchill-kb-category-header">
							<h2><?php echo esc_html( $wkb_current_category->name ); ?></h2>
							<p><?php echo esc_html( $wkb_current_category->description ); ?></p>
						</div>
						<ul class="wpchill-kb-article-list">
							<?php foreach ( $wkb_child_categories as $wkb_child_category ) : ?>
								<?php
								$wkb_category_color = get_term_meta( $wkb_child_category->term_id, 'color', true );
								$wkb_category_color = $wkb_category_color ? $wkb_category_color : '#4d4dff';

								$wkb_category_icon = get_term_meta( $wkb_child_category->term_id, 'icon', true );
								$wkb_category_icon = $wkb_category_icon ? $wkb_category_icon : 'dashicons-category';
								
								?>
								<li class="wpchill-kb-category">
									<a href="<?php echo esc_url( get_term_link( $wkb_child_category ) ); ?>">
										<span class="wpchill-kb-icon dashicons <?php echo esc_attr( $wkb_category_icon ); ?>" style="background-color: <?php echo esc_attr( $wkb_category_color ); ?>;"></span>
										<span><?php echo esc_html( $wkb_child_category->name ); ?></span>
									</a>
								</li>
							<?php endforeach; ?>
							<?php
							while ( $wkb_articles_query->have_posts() ) :
								$wkb_articles_query->the_post();
								$wkb_article_classes = apply_filters( 'wpchill_kb_article_classes', array( 'wpchill-kb-article' ), get_the_ID() );
								?>
								<li class="<?php echo esc_attr( implode( ' ', $wkb_article_classes ) ); ?>">
									<a href="<?php the_permalink(); ?>"><span class="wpchill-kb-icon dashicons dashicons-media-document"></span>
										<span><?php the_title(); ?></span>
									</a>
								</li>
								<?php
							endwhile;
							?>
						</ul>
						<?php
						the_posts_pagination(
							array(
								'prev_text' => __( 'Previous page', 'wpchill-kb' ),
								'next_text' => __( 'Next page', 'wpchill-kb' ),
							)
						);
						?>
					</div>
				</main>
				<aside class="wpchill-kb-sidebar">
					<?php
					if ( is_active_sidebar( 'kb-sidebar-right' ) ) {
						dynamic_sidebar( 'kb-sidebar-right' );
					}
					?>
				</aside>
			</div>
		</div>
	</div>
<?php
wp_reset_postdata();
$wkb_plugin->get_footer();
?>
