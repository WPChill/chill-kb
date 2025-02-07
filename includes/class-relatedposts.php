<?php
namespace WPChill\KB;

class RelatedPosts {
	public function get_related_articles( $post_id, $limit = 5 ) {
		$related_posts = array();
		$current_post  = get_post( $post_id );

		// Get current post's categories and tags
		$categories = wp_get_post_terms( $post_id, 'kb_category', array( 'fields' => 'ids' ) );
		$tags       = wp_get_post_terms( $post_id, 'kb_tag', array( 'fields' => 'ids' ) );

		// Query arguments
		$args = array(
			'post_type'      => 'kb',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'post__not_in'   => array( $post_id ),
			'orderby'        => 'rand',
			'tax_query'      => array( //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				'relation' => 'OR',
				array(
					'taxonomy' => 'kb_category',
					'field'    => 'term_id',
					'terms'    => $categories,
				),
				array(
					'taxonomy' => 'kb_tag',
					'field'    => 'term_id',
					'terms'    => $tags,
				),
			),
		);

		// Apply filters to allow customization of the query
		$args = apply_filters( 'wpchill_kb_related_articles_args', $args, $post_id );

		$query = new \WP_Query( $args );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$related_posts[] = array(
					'id'        => get_the_ID(),
					'title'     => get_the_title(),
					'permalink' => get_permalink(),
					'excerpt'   => get_the_excerpt(),
				);
			}
			wp_reset_postdata();
		}

		return $related_posts;
	}

	public function display_related_articles( $post_id, $limit = 5 ) {
		$related_articles = $this->get_related_articles( $post_id, $limit );

		if ( ! empty( $related_articles ) ) {
			echo '<div class="wpchill-kb-related-articles">';
			echo '<h3>' . esc_html__( 'Related Articles', 'wpchill-kb' ) . '</h3>';
			echo '<ul>';
			foreach ( $related_articles as $article ) {
				echo '<li><a href="' . esc_url( $article['permalink'] ) . '">' . esc_html( $article['title'] ) . '</a></li>';
			}
			echo '</ul>';
			echo '</div>';
		}
	}
}
