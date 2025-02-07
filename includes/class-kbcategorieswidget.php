<?php

namespace WPChill\KB;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class KBCategoriesWidget extends \WP_Widget {
	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'   => 'wpchill_kb_categories_widget',
			'description' => __( 'Displays a list of KB categories', 'wpchill-kb' ),
		);
		parent::__construct( 'wpchill_kb_categories_widget', __( 'WPChill KB Categories', 'wpchill-kb' ), $widget_ops );
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		$title      = ! empty( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'] ) : '';
		$show_count = ! empty( $instance['show_count'] );
		$orderby    = ! empty( $instance['orderby'] ) ? $instance['orderby'] : 'name';
		$order      = ! empty( $instance['order'] ) ? $instance['order'] : 'ASC';

		echo wp_kses_post( $args['before_widget'] );

		if ( $title ) {
			echo wp_kses_post( $args['before_title'] ) . esc_html( $title ) . wp_kses_post( $args['after_title'] );
		}

		$categories = $this->get_kb_categories( $orderby, $order );

		if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
			$this->display_categories( $categories, $show_count );
		} else {
			echo '<p>' . esc_html__( 'No categories found.', 'wpchill-kb' ) . '</p>';
		}

		echo wp_kses_post( $args['after_widget'] );
	}

	public function form( $instance ) {
		$title      = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$show_count = isset( $instance['show_count'] ) ? (bool) $instance['show_count'] : false;
		$orderby    = isset( $instance['orderby'] ) ? $instance['orderby'] : 'name';
		$order      = isset( $instance['order'] ) ? $instance['order'] : 'ASC';

		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'wpchill-kb' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
					value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_count' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'show_count' ) ); ?>"<?php checked( $show_count ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_count' ) ); ?>"><?php esc_html_e( 'Display post count', 'wpchill-kb' ); ?></label>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'orderby' ) ); ?>"><?php esc_html_e( 'Order by:', 'wpchill-kb' ); ?></label>
			<select id="<?php echo esc_attr( $this->get_field_id( 'orderby' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'orderby' ) ); ?>">
				<option value="name" <?php selected( $orderby, 'name' ); ?>><?php esc_html_e( 'Name', 'wpchill-kb' ); ?></option>
				<option value="count" <?php selected( $orderby, 'count' ); ?>><?php esc_html_e( 'Count', 'wpchill-kb' ); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'order' ) ); ?>"><?php esc_html_e( 'Order:', 'wpchill-kb' ); ?></label>
			<select id="<?php echo esc_attr( $this->get_field_id( 'order' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'order' ) ); ?>">
				<option value="ASC" <?php selected( $order, 'ASC' ); ?>><?php esc_html_e( 'Ascending', 'wpchill-kb' ); ?></option>
				<option value="DESC" <?php selected( $order, 'DESC' ); ?>><?php esc_html_e( 'Descending', 'wpchill-kb' ); ?></option>
			</select>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance               = array();
		$instance['title']      = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['show_count'] = isset( $new_instance['show_count'] ) ? (bool) $new_instance['show_count'] : false;
		$instance['orderby']    = ( ! empty( $new_instance['orderby'] ) ) ? sanitize_key( $new_instance['orderby'] ) : 'name';
		$instance['order']      = ( ! empty( $new_instance['order'] ) ) ? sanitize_key( $new_instance['order'] ) : 'ASC';

		return $instance;
	}

	private function get_kb_categories( $orderby, $order ) {
		$args = array(
			'taxonomy'   => 'kb_category',
			'orderby'    => sanitize_key( $orderby ),
			'order'      => sanitize_key( $order ),
			'hide_empty' => true,
			'parent'     => 0,
		);

		return get_terms( $args );
	}

	private function display_categories( $categories, $show_count ) {

		$current_category    = get_queried_object();
		$current_category_id = 0;

		if ( $current_category && isset( $current_category->term_id ) ) {
			$current_category_id = $current_category->term_id;
		}

		echo '<ul>';
		foreach ( $categories as $category ) {
			$this->render_category_with_children( $category, $current_category_id, $show_count );
		}
		echo '</ul>';
	}

	private function render_category_with_children( $category, $current_category_id, $show_count ) {

		$category_color = get_term_meta( $category->term_id, 'color', true );
		$category_color = $category_color ? $category_color : '#4d4dff';

		$category_icon = get_term_meta( $category->term_id, 'icon', true );
		$category_icon = $category_icon ? $category_icon : 'dashicons-category';

		$subcategories = get_terms(
			array(
				'taxonomy'   => 'kb_category',
				'hide_empty' => true,
				'parent'     => $category->term_id,
			)
		);

		$posts = get_posts(
			array(
				'post_type'      => 'kb',
				'posts_per_page' => -1,
				'tax_query'      => array( //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy'         => 'kb_category',
						'field'            => 'term_id',
						'terms'            => $category->term_id,
						'include_children' => false,
					),
				),
			)
		);

		$is_active =
			$current_category_id === $category->term_id ||
			$this->is_category_in_tree( $current_category_id, $category->term_id ) ||
			$this->is_post_in_category_tree( get_the_ID(), $category->term_id ) ?
			'active' : '';

		?>
		<li class="wpchill-kb-category <?php echo esc_attr( $is_active ); ?>">
			<div class="wpchill-kb-dropdown">
				<a href="<?php echo esc_url( get_term_link( $category ) ); ?>" class="wpchill-kb-category-name">
					<span class="dashicons dashicons-arrow-down-alt2 toggle-icon"></span>
					<span class="wpchill-kb-icon dashicons <?php echo esc_attr( $category_icon ); ?>" style="background-color: <?php echo esc_attr( $category_color ); ?>;"></span>
					<span class="wpchill-kb-category-text"><?php echo esc_html( $category->name ); ?> <?php echo $show_count ? '(' . esc_html( count( $posts ) ) . ')' : ''; ?></span>
				</a>
			</div>
			<ul class="wpchill-kb-articles-list <?php echo esc_attr( $is_active ); ?>">
				<?php if ( ! empty( $subcategories ) ) : ?>
					<?php foreach ( $subcategories as $subcategory ) : ?>
						<?php $this->render_category_with_children( $subcategory, $current_category_id, $show_count ); ?>
					<?php endforeach; ?>
				<?php endif; ?>
	
				<?php if ( ! empty( $posts ) ) : ?>
					<?php foreach ( $posts as $post ) : ?>
						<li>
							<a class="wpchill-kb-articles-link<?php echo get_the_ID() === $post->ID && is_single() ? ' active' : ''; ?>" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
								<?php echo esc_html( $post->post_title ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				<?php endif; ?>
	
				<?php if ( empty( $posts ) && empty( $subcategories ) ) : ?>
					<li><?php esc_html_e( 'No articles found.', 'wpchill-kb' ); ?></li>
				<?php endif; ?>
			</ul>
		</li>
		<?php
	}


	private function is_category_in_tree( $child_id, $parent_id ) {
		$term = get_term( $child_id );
		while ( $term && isset( $term->parent ) ) {
			if ( (int) $term->parent === (int) $parent_id ) {
				return true;
			}
			$term = get_term( $term->parent );
		}
		return false;
	}

	private function is_post_in_category_tree( $post_id, $category_id ) {
		$terms = get_the_terms( $post_id, 'kb_category' );

		if ( ! $terms || is_wp_error( $terms ) ) {
			return false;
		}

		foreach ( $terms as $term ) {
			if ( (int) $term->term_id === (int) $category_id || $this->is_category_in_tree( $term->term_id, $category_id ) ) {
				return true;
			}
		}

		return false;
	}
}
