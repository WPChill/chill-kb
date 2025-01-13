<?php

namespace WPChill\KB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ProductsAPI {

	/**
	 * Holds the class object
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The ProductsAPI object.
	 * @since 1.0.0
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof ProductsAPI ) ) {
			self::$instance = new ProductsAPI();
		}

		return self::$instance;
	}

	public function get_products_if_available() {
		$response = array();

		if ( $this->is_edd_active() ) {
			$response[] = array(
				'name'     => __( 'EDD Products', 'wpchill-kb' ),
				'slug'     => 'edd',
				'products' => $this->get_edd_products(),
			);
		}

		if ( $this->is_woo_active() ) {
			$response[] = array(
				'name'     => __( 'WooCommerce Products', 'wpchill-kb' ),
				'slug'     => 'woo',
				'products' => $this->get_woo_products(),
			);
		}

		if ( empty( $response ) ) {
			return false;
		}

		return $response;
	}

	public function is_edd_active() {

		$is_edd_active    = class_exists( 'Easy_Digital_Downloads' );
		$is_edd_sl_active = class_exists( 'EDD_Software_Licensing' );

		return $is_edd_active && $is_edd_sl_active;
	}

	public function is_woo_active() {

		$is_woo_active     = class_exists( 'WooCommerce' );
		$is_woo_sub_active = class_exists( 'WC_Subscriptions' );

		return $is_woo_active && $is_woo_sub_active;
	}


	public function get_edd_products() {
		global $wpdb;

		$results = $wpdb->get_results(
			"
			SELECT p.ID AS value, p.post_title AS label, p.post_name AS slug, pm_price.meta_value AS price
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm_enabled
				ON p.ID = pm_enabled.post_id
			INNER JOIN {$wpdb->postmeta} pm_price
				ON p.ID = pm_price.post_id
			WHERE p.post_type = 'download'
				AND p.post_status = 'publish'
				AND pm_enabled.meta_key = '_edd_sl_enabled'
				AND pm_enabled.meta_value = '1'
				AND pm_price.meta_key = 'edd_price'
			",
			ARRAY_A
		);

		return $results;
	}

	public function get_woo_products() {
		global $wpdb;

		$results = $wpdb->get_results(
			"
			SELECT 
				p.ID AS value, 
				p.post_title AS label, 
				p.post_name AS slug, 
				pm_price.meta_value AS price
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm_price
				ON p.ID = pm_price.post_id
			WHERE p.post_type = 'product'
			  AND p.post_status = 'publish'
			  AND pm_price.meta_key = '_price'
			GROUP BY p.ID
			",
			ARRAY_A
		);

		return $results;
	}
}
