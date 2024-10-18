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
		$title          = ! empty( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'] ) : '';
		$show_count     = ! empty( $instance['show_count'] );
		$show_hierarchy = ! empty( $instance['show_hierarchy'] );
		$orderby        = ! empty( $instance['orderby'] ) ? $instance['orderby'] : 'name';
		$order          = ! empty( $instance['order'] ) ? $instance['order'] : 'ASC';

		echo wp_kses_post( $args['before_widget'] );

		if ( $title ) {
			echo wp_kses_post( $args['before_title'] ) . esc_html( $title ) . wp_kses_post( $args['after_title'] );
		}

		$categories = $this->get_kb_categories( $orderby, $order, $show_hierarchy );

		if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
			$this->display_categories( $categories, $show_count, $show_hierarchy );
		} else {
			echo '<p>' . esc_html__( 'No categories found.', 'wpchill-kb' ) . '</p>';
		}

		echo wp_kses_post( $args['after_widget'] );
	}

	public function form( $instance ) {
		$title          = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$show_count     = isset( $instance['show_count'] ) ? (bool) $instance['show_count'] : false;
		$show_hierarchy = isset( $instance['show_hierarchy'] ) ? (bool) $instance['show_hierarchy'] : false;
		$orderby        = isset( $instance['orderby'] ) ? $instance['orderby'] : 'name';
		$order          = isset( $instance['order'] ) ? $instance['order'] : 'ASC';

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
			<input type="checkbox" class="checkbox"
					id="<?php echo esc_attr( $this->get_field_id( 'show_hierarchy' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'show_hierarchy' ) ); ?>"<?php checked( $show_hierarchy ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_hierarchy' ) ); ?>"><?php esc_html_e( 'Show hierarchy', 'wpchill-kb' ); ?></label>
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
		$instance                   = array();
		$instance['title']          = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['show_count']     = isset( $new_instance['show_count'] ) ? (bool) $new_instance['show_count'] : false;
		$instance['show_hierarchy'] = isset( $new_instance['show_hierarchy'] ) ? (bool) $new_instance['show_hierarchy'] : false;
		$instance['orderby']        = ( ! empty( $new_instance['orderby'] ) ) ? sanitize_key( $new_instance['orderby'] ) : 'name';
		$instance['order']          = ( ! empty( $new_instance['order'] ) ) ? sanitize_key( $new_instance['order'] ) : 'ASC';

		return $instance;
	}

	private function get_kb_categories( $orderby, $order, $show_hierarchy ) {
		$args = array(
			'taxonomy'   => 'kb_category',
			'orderby'    => sanitize_key( $orderby ),
			'order'      => sanitize_key( $order ),
			'hide_empty' => false,
		);

		if ( $show_hierarchy ) {
			$args['parent'] = 0;
		}

		return get_terms( $args );
	}

	private function display_categories( $categories, $show_count, $show_hierarchy, $depth = 0 ) {
		$indent = str_repeat( '&nbsp;&nbsp;&nbsp;', $depth );

		echo '<ul class="wpchill-kb-categories-widget">';
		foreach ( $categories as $category ) {
			$category_color = get_term_meta( $category->term_id, 'category_color', true );
			$category_color = $category_color ? sanitize_hex_color( $category_color ) : '#4d4dff'; // Default color

			echo '<li>';
			echo wp_kses_post( $indent );
			echo '<a href="' . esc_url( get_term_link( $category ) ) . '">';
			echo '<span class="wpchill-kb-icon" style="background-color: ' . esc_attr( $category_color ) . ';"></span>';
			echo esc_html( $category->name );
			if ( $show_count ) {
				echo ' <span class="wpchill-kb-count">(' . absint( $category->count ) . ')</span>';
			}
			echo '</a>';

			if ( $show_hierarchy ) {
				$child_categories = get_terms(
					array(
						'taxonomy'   => 'kb_category',
						'parent'     => $category->term_id,
						'hide_empty' => false,
					)
				);

				if ( ! empty( $child_categories ) && ! is_wp_error( $child_categories ) ) {
					$this->display_categories( $child_categories, $show_count, $show_hierarchy, $depth + 1 );
				}
			}

			echo '</li>';
		}
		echo '</ul>';
	}
}