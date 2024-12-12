<?php

namespace WPChill\KB;

class PostType {

	public function register() {
		$labels = array(
			'name'               => _x( 'KB Articles', 'post type general name', 'wpchill-kb' ),
			'singular_name'      => _x( 'KB Article', 'post type singular name', 'wpchill-kb' ),
			'menu_name'          => _x( 'KB Articles', 'admin menu', 'wpchill-kb' ),
			'name_admin_bar'     => _x( 'KB Article', 'add new on admin bar', 'wpchill-kb' ),
			'add_new'            => _x( 'Add New', 'kb article', 'wpchill-kb' ),
			'add_new_item'       => __( 'Add New Article', 'wpchill-kb' ),
			'new_item'           => __( 'New Article', 'wpchill-kb' ),
			'edit_item'          => __( 'Edit Article', 'wpchill-kb' ),
			'view_item'          => __( 'View Article', 'wpchill-kb' ),
			'all_items'          => __( 'All Articles', 'wpchill-kb' ),
			'search_items'       => __( 'Search Articles', 'wpchill-kb' ),
			'parent_item_colon'  => __( 'Parent Articles:', 'wpchill-kb' ),
			'not_found'          => __( 'No articles found.', 'wpchill-kb' ),
			'not_found_in_trash' => __( 'No articles found in Trash.', 'wpchill-kb' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'kb' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'show_in_rest'       => true,
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' ),
			'taxonomies'         => array( 'kb_category' ),
		);

		$args = apply_filters( 'wpchill_kb_post_type_args', $args );

		register_post_type( 'kb', $args );
	}
}
