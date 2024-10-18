<?php
get_header();

$current_category = get_queried_object();

$categories = get_terms(
	array(
		'taxonomy'   => 'kb_category',
		'hide_empty' => false,
	)
);
$plugin     = new WPChill\KB\Plugin();
?>

	<div class="wpchill-kb-wrapper">
		<header class="wpchill-kb-header">
			<div class="wpchill-kb-header-left">
				<h1 class="wpchill-kb-title"><?php echo esc_html( apply_filters( 'wpchill_kb_category_title', __( 'Knowledge Base', 'wpchill-kb' ) ) ); ?></h1>
			</div>
			<div class="wpchill-kb-header-right">
				<?php echo $plugin->get_search_form(); ?>
			</div>
		</header>
		<div class="wpchill-kb-page-container">
			<div class="wpchill-kb-sidebar-background"></div>
			<div class="wpchill-kb-content-container">
				<aside class="wpchill-kb-sidebar">
					<ul>
						<?php
						foreach ( $categories as $category ) :
							$category_color = get_term_meta( $category->term_id, 'category_color', true );
							$category_color = $category_color ? $category_color : '#4d4dff'; // Default color
							?>
							<li>
								<a href="<?php echo esc_url( get_term_link( $category ) ); ?>">
									<span class="wpchill-kb-icon" style="background-color: <?php echo esc_attr( $category_color ); ?>;"></span>
									<?php echo esc_html( $category->name ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</aside>
				<main class="wpchill-kb-main-content">
					<div class="wpchill-kb-category-header">
						<h2><?php echo esc_html( $current_category->name ); ?></h2>
						<p><?php echo esc_html( $current_category->description ); ?></p>
					</div>
					<ul class="wpchill-kb-article-list">
						<?php
						while ( have_posts() ) :
							the_post();
							$article_classes = apply_filters( 'wpchill_kb_article_classes', array( 'wpchill-kb-article' ), get_the_ID() );
							?>
							<li class="<?php echo esc_attr( implode( ' ', $article_classes ) ); ?>">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
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
				</main>
			</div>
		</div>
	</div>
<?php
get_footer();
?>