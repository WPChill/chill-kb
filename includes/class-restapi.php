<?php

namespace WPChill\KB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RestAPI {

	public function register_routes() {

		register_rest_route(
			'wpchill-kb/v1',
			'/restrictions/(?P<post_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_products_if_available' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			)
		);

		register_rest_route(
			'wpchill-kb/v1',
			'/modal-licenses/(?P<post_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_modal_licenses_data' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function get_products_if_available() {

		$products_api = ProductsAPI::get_instance();
		return rest_ensure_response( $products_api->get_products_if_available() );
	}

	public function get_modal_licenses_data( $request ) {
		$post_id = intval( $request['post_id'] );
		if ( 0 === $post_id ) {
			return rest_ensure_response( array() );
		}

		$products_api = ProductsAPI::get_instance();
		return rest_ensure_response( $products_api->get_license_modal_data( $post_id ) );
	}

	public function permissions_check( $request ) {

		$post_id = intval( $request['post_id'] );
		return current_user_can( 'edit_post', $post_id );
	}
}
