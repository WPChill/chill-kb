<?php
$wkb_plugin          = new WPChill\KB\Plugin();
$wkb_article_locking = new WPChill\KB\ArticleLocking();

$wkb_plugin->get_header();

?>
	<header class="wpchill-kb-header-wrapper">
		<div class="wpchill-kb-header">
			<?php $wkb_plugin->get_search_form(); ?>
		</div>
	</header>
	<div class="wpchill-kb-wrapper">
		<div class="wpchill-kb-page-container">
			<div class="wpchill-kb-sidebar-background"></div>
			<div class="wpchill-kb-content-container">
				<aside class="wpchill-kb-sidebar">
					<div class="wpchill-sidebar-content">
						<?php
						if ( is_active_sidebar( 'kb-sidebar' ) ) {
							dynamic_sidebar( 'kb-sidebar' );
						}
						?>
					</div>
					<div class="wpchill-sidebar-toggle"><span class="dashicons dashicons-menu"></span></div>
				</aside>
				<main class="wpchill-kb-main-content">
					<div class="wpchill-kb-main-content-wrap">
						<?php
						if ( defined( 'THE_SEO_FRAMEWORK_VERSION' ) ) {
							echo do_shortcode( '[tsf_breadcrumb class="wpchill-kb-breadcrumb"]' );
						}
						?>

						<h1 class="wpchill-kb-title"><?php the_title(); ?></h1>
						<?php
						while ( have_posts() ) :
							the_post();

							$post_classes = apply_filters( 'wpchill_kb_article_classes', array( 'wpchill-kb-article' ), get_the_ID() );
							?>
							<article id="post-<?php the_ID(); ?>" <?php post_class( $post_classes ); ?>>
								<div class="wpchill-kb-entry-content">
									<?php
									// Use the filter_locked_content method to handle content display
									echo $wkb_article_locking->filter_locked_content( get_the_content() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, escaped in the rendering methods of ProductsAPI.
									?>
								</div>
								<?php
								// The rating action will be displayed only if the content is not locked or the user is logged in
								do_action( 'wpchill_kb_rating' );
								?>
							</article>
							<?php
						endwhile;

						?>
					</div>
				</main>
				<aside class="wpchill-kb-sidebar wpchill-kb-sidebar-right">
					<div class="wpchill-sidebar-content">
						<?php
						if ( is_active_sidebar( 'kb-sidebar-right' ) ) {
							dynamic_sidebar( 'kb-sidebar-right' );
						}
						?>
					</div>
				</aside>
			</div>
		</div>
	</div>

<?php
$wkb_plugin->get_footer();
?>
