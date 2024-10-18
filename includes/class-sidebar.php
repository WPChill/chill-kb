<?php

namespace WPChill\KB;

class Sidebar {
	public function __construct() {
		add_action( 'widgets_init', array( $this, 'register_sidebar' ) );
	}

	public function register_sidebar() {
		register_sidebar(
			array(
				'name'          => __( 'KB Sidebar', 'wpchill-kb' ),
				'id'            => 'kb-sidebar',
				'description'   => __( 'Widgets in this area will be shown on KB pages.', 'wpchill-kb' ),
				'before_widget' => '<div id="%1$s" class="widget %2$s">',
				'after_widget'  => '</div>',
				'before_title'  => '<h2 class="widgettitle">',
				'after_title'   => '</h2>',
			)
		);
	}
}
