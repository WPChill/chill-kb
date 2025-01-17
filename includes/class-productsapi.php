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

	/**
	 * Retrieves the available products from EDD and WooCommerce, if active.
	 *
	 * @return array|false An array of product data if available, false otherwise.
	 */
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

	/**
	 * Checks if Easy Digital Downloads and EDD Software Licensing are active.
	 *
	 * @return bool True if both EDD and EDD SL are active, false otherwise.
	 */
	private function is_edd_active() {

		$is_edd_active    = class_exists( 'Easy_Digital_Downloads' );
		$is_edd_sl_active = class_exists( 'EDD_Software_Licensing' );

		return $is_edd_active && $is_edd_sl_active;
	}

	/**
	 * Checks if WooCommerce and WooCommerce Subscriptions are active.
	 *
	 * @return bool True if both WooCommerce and WooCommerce Subscriptions are active, false otherwise.
	 */
	private function is_woo_active() {

		$is_woo_active     = class_exists( 'WooCommerce' );
		$is_woo_sub_active = class_exists( 'WC_Subscriptions' );

		return $is_woo_active && $is_woo_sub_active;
	}

	/**
	 * Retrieves a list of published EDD products with pricing and slug details.
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 * @return array An array of EDD product data.
	 */
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

	/**
	 * Retrieves a list of published WooCommerce products with pricing and slug details.
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 * @return array An array of WooCommerce product data.
	 */
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

	/**
	 * Retrieves the license message for a locked post.
	 *
	 * @param int $post_id The ID of the post to check.
	 * @return mixed A modal with license information or false to show original content.
	 */
	public function get_license_message( $post_id ) {
		// If we got here it means the article is locked and the user is logged in.
		$license_messages = false;

		// Do Easy Digital Download license check
		$edd = $this->check_edd_license( $post_id );
		if ( false !== $edd ) {
			$license_messages = $edd;
		}

		// Do WooCommerce subscription check
		$woo = $this->check_woo_subscription( $post_id );
		if ( false !== $woo ) {
			if ( is_array( $license_messages ) ) {
				$license_messages = array_merge( $license_messages, $woo );
			} else {
				$license_messages = $woo;
			}
		}

		if ( false !== $license_messages ) {
			return $this->button_or_modal( $license_messages );
		}

		// Returning false will display the original content.
		return false;
	}

	/**
	 * Retrieves the license message for a locked post.
	 *
	 * @param int $post_id The ID of the post to check.
	 * @return mixed A modal with license information or false to show original content.
	 */
	public function get_license_modal_data( $post_id ) {
		// If we got here it means the article is locked and the user is logged in.
		$data = array();

		// Do Easy Digital Download license check
		$edd = $this->check_edd_license( $post_id );
		if ( false !== $edd ) {
			$data = $edd;
		}

		// Do WooCommerce subscription check
		$woo = $this->check_woo_subscription( $post_id );
		if ( false !== $woo ) {
			$data = array_merge( $data, $woo );
		}

		return $data;
	}

	/**
	 * Checks the EDD license for the current user for a specific post.
	 *
	 * @param int $post_id The ID of the post to check.
	 * @return array|false Modal data for the EDD license or false if valid.
	 */
	private function check_edd_license( $post_id ) {
		if ( ! $this->is_edd_active() ) {
			return false;
		}

		$locking_products = array_map( 'absint', $this->get_locked_downloads( $post_id ) );

		// No products to check against.
		if ( empty( $locking_products ) ) {
			return false;
		}

		$purchases  = edd_get_users_purchases( get_current_user_id(), 20, true, 'any' );
		$modal_data = array();

		// Get the cheapest product required.
		$cheapest_downloads = $this->get_cheapest_edd_products( $post_id );
		$cheapest_download  = absint( array_key_first( $cheapest_downloads ) );

		// No purchase, no license.
		if ( ! $purchases && 0 !== $cheapest_download ) {

			// Set-up purchase url.
			$url = add_query_arg(
				array(
					'edd_action'  => 'add_to_cart',
					'download_id' => $cheapest_download,
				),
				esc_url_raw( edd_get_checkout_uri() )
			);

			$modal_data[] = array(
				'type'     => __( 'Purchase', 'wpchill-kb' ),
				'download' => $cheapest_download,
				'title'    => html_entity_decode( get_the_title( $cheapest_download ), ENT_QUOTES, 'UTF-8' ),
				'url'      => $url,
			);

			return $modal_data;
		}

		foreach ( $purchases as $payment ) {
			$is_upgrade = (bool) edd_get_payment_meta( $payment->ID, '_edd_sl_upgraded_payment_id', true );
			if ( $is_upgrade ) {
				continue;
			}

			$esl = edd_software_licensing();
			if ( $esl->is_renewal( $payment->ID ) ) {
				continue;
			}

			// Get every license the user got.
			$licenses = $esl->get_licenses_of_purchase( $payment->ID );

			// Only use the main license and not child licenses if any.
			$license = is_array( $licenses ) ? $licenses[0] : $licenses;

			// Get the download asociated with the license.
			$download = $esl->get_download_id( $license->ID );
			$status   = $esl->get_license_status( $license->ID );
			$key      = $esl->get_license_key( $license->ID );

			// Is license enough?
			if ( in_array( $download, $locking_products, true ) ) {
				// Is license valid?
				if ( ( 'expired' !== $status && 'disabled' !== $status ) || $esl->is_lifetime_license( $license->ID ) ) {

					// Everything is ok. Show the content.
					return false;
				}

				if ( ! $license->can_renew() ) {
					continue;
				}

				// Renew the license
				$modal_data[] = array(
					'type'     => __( 'Renew', 'wpchill-kb' ),
					'download' => $download,
					'title'    => html_entity_decode( get_the_title( $download ), ENT_QUOTES, 'UTF-8' ),
					'key'      => $key,
					'url'      => $esl->get_renewal_url( $license->ID ),
				);
			} else {
				$upgrades = edd_sl_get_license_upgrades( $license->ID );
				foreach ( $upgrades as $upgrade_id => $upgrade ) {
					if ( isset( $upgrade['download_id'] ) && isset( $cheapest_downloads[ $upgrade['download_id'] ] ) ) {
						$modal_data[] = array(
							'type'     => __( 'Upgrade', 'wpchill-kb' ),
							'download' => $upgrade['download_id'],
							'title'    => html_entity_decode( get_the_title( $upgrade['download_id'] ), ENT_QUOTES, 'UTF-8' ),
							'key'      => $key,
							'url'      => edd_sl_get_license_upgrade_url( $license->ID, $upgrade_id ),
						);
						break;
					}
				}
			}
		}
		// No upgrades?
		if ( empty( $modal_data ) && 0 !== $cheapest_download ) {
			$url          = add_query_arg(
				array(
					'edd_action'  => 'add_to_cart',
					'download_id' => $cheapest_download,
				),
				esc_url_raw( edd_get_checkout_uri() )
			);
			$modal_data[] = array(
				'type'     => __( 'Purchase', 'wpchill-kb' ),
				'download' => $cheapest_download,
				'title'    => html_entity_decode( get_the_title( $cheapest_download ), ENT_QUOTES, 'UTF-8' ),
				'url'      => $url,
			);
		}
		return $modal_data;
	}

	/**
	 * Retrieves the cheapest EDD products ordered low to high required for a locked post.
	 *
	 * @param int $post_id The ID of the post to check.
	 * @return array An associative array of product IDs and their prices.
	 */
	private function get_cheapest_edd_products( $post_id ) {
		$download_price = array();

		foreach ( $this->get_locked_downloads( $post_id ) as $product_id ) {
			$download_price[ absint( $product_id ) ] = floor( edd_get_download_price( absint( $product_id ) ) );
		}
		asort( $download_price );
		return $download_price;
	}

	/**
	 * Retrieves the cheapest WooCommerce products ordered low to high required for a locked post.
	 *
	 * @param int $post_id The ID of the post to check.
	 * @return array An associative array of product IDs and their prices.
	 */
	private function get_cheapest_woo_products( $post_id ) {
		$download_price = array();

		foreach ( $this->get_locked_downloads( $post_id, 'woo' ) as $product_id ) {
			$product = wc_get_product( absint( $product_id ) );

			if ( ! $product ) {
				continue;
			}

			$download_price[ absint( $product_id ) ] = floor( $product->get_price() );
		}
		asort( $download_price );
		return $download_price;
	}

	/**
	 * Retrieves the list of locked products for a specific post.
	 *
	 * @param int $post_id The ID of the post to check.
	 * @param string $whos The type of products to check ('edd' or 'woo').
	 * @return array An array of locked product IDs.
	 */
	private function get_locked_downloads( $post_id, $whos = 'edd' ) {
		$locked_products = get_post_meta( $post_id, '_wpchill_kb_locked_products', true );

		if ( ! $locked_products || empty( $locked_products ) ) {
			return array();
		}

		foreach ( json_decode( $locked_products ) as $locked_product ) {
			if ( $whos === $locked_product->key ) {
				if ( ! empty( $locked_product->products ) ) {
					return $locked_product->products;
				}
			}
		}

		return array();
	}

	/**
	 * Checks the WooCommerce subscription for the current user for a specific post.
	 *
	 * @param int $post_id The ID of the post to check.
	 * @return array|false Modal data for the WooCommerce subscription or false if valid.
	 */
	private function check_woo_subscription( $post_id ) {
		$modal_data = array();

		if ( ! $this->is_woo_active() ) {
			return false;
		}

		// Get the cheapest product required.
		$cheapest_downloads = $this->get_cheapest_woo_products( $post_id );
		$cheapest_download  = absint( array_key_first( $cheapest_downloads ) );
		$locking_products   = array_map( 'absint', $this->get_locked_downloads( $post_id, 'woo' ) );
		$user_id            = get_current_user_id();
		$buy_link_set       = false;

		// Check for simple products.
		foreach ( $locking_products as $product_id ) {
			// Check if the user has purchased the product.
			if ( wc_customer_bought_product( '', $user_id, $product_id ) ) {
				$product = wc_get_product( $product_id );
				if ( 'subscription' !== $product->get_type() ) {
					return false; // User has access.
				}
			}
		}

		// Getting all user subscriptions
		$subscriptions = wcs_get_subscriptions(
			array(
				'customer_id' => $user_id,
				'post_status' => array( 'wc-active', 'wc-on-hold', 'wc-expired' ),
				'numberposts' => -1,
			)
		);

		// No purchase, no license.
		if ( ! $subscriptions || empty( $subscriptions ) ) {

			// Set-up purchase url.
			$url = add_query_arg(
				array(
					'add-to-cart' => $cheapest_download,
				),
				esc_url_raw( wc_get_checkout_url() )
			);

			$modal_data[] = array(
				'type'     => __( 'Purchase', 'wpchill-kb' ),
				'download' => $cheapest_download,
				'title'    => html_entity_decode( get_the_title( $cheapest_download ), ENT_QUOTES, 'UTF-8' ),
				'url'      => $url,
			);

			return $modal_data;
		}

		foreach ( $subscriptions as $subscription ) {
			$status = $subscription->get_status();
			foreach ( $subscription->get_items() as $subscription_item ) {
				$download = $subscription_item->get_product_id();

				if ( in_array( $download, $locking_products, true ) ) {
					// Is license valid?
					if ( 'on-hold' !== $status && 'expired' !== $status ) {

						// Everything is ok. Show the content.
						return false;
					}

					if ( wcs_can_user_resubscribe_to( $subscription, $user_id ) && false === $subscription->can_be_updated_to( 'active' ) ) {
						$modal_data[] = array(
							'type'     => __( 'Resubscribe', 'wpchill-kb' ),
							'download' => $download,
							'title'    => html_entity_decode( get_the_title( $download ), ENT_QUOTES, 'UTF-8' ),
							'url'      => wcs_get_users_resubscribe_link( $subscription ),
						);
					} elseif ( ! $buy_link_set ) {
						// Cannot renew. Show purchase link.
						$url          = add_query_arg(
							array(
								'add-to-cart' => $cheapest_download,
							),
							esc_url_raw( wc_get_checkout_url() )
						);
						$modal_data[] = array(
							'type'     => __( 'Purchase', 'wpchill-kb' ),
							'download' => $cheapest_download,
							'title'    => html_entity_decode( get_the_title( $cheapest_download ), ENT_QUOTES, 'UTF-8' ),
							'url'      => $url,
						);
						$buy_link_set = true;
					}
				} elseif ( ! $buy_link_set ) {
					// If we are here it means no download matched. Buy a new sub.
					$url = add_query_arg(
						array(
							'add-to-cart' => $cheapest_download,
						),
						esc_url_raw( wc_get_checkout_url() )
					);

					$modal_data[] = array(
						'type'     => __( 'Purchase', 'wpchill-kb' ),
						'download' => $cheapest_download,
						'title'    => html_entity_decode( get_the_title( $cheapest_download ), ENT_QUOTES, 'UTF-8' ),
						'url'      => $url,
					);

					$buy_link_set = true;
				}
			}
		}
		return $modal_data;
	}

	/**
	 * Generates a message, or modal button based on the provided license data.
	 *
	 * @param array $data An array of license data required to unlock the content.
	 *                    Each item in the array should have:
	 *                    - 'url' (string): The purchase or upgrade URL.
	 *                    - 'type' (string, optional): The type of action (e.g., "Purchase").
	 *                    - 'title' (string): The title of the license or product.
	 * @return string The HTML content for the message or react modal root element.
	 */
	private function button_or_modal( $data ) {

		if ( ! is_array( $data ) || empty( $data ) ) {
			return '<div class="wpchill-kb-locked-message">
					<h3>' . esc_html__( 'This article is locked and cannot be viewed.', 'wpchill-kb' ) . '</h3>
					<p>' . esc_html__( 'This access this article please contact the site administrator.', 'wpchill-kb' ) . '</p>
				</div>';
		}

		return sprintf(
			'<div class="wpchill-kb-locked-message">
				<h3>%s</h3>
				<p>%s</p>
				<p>%s</p>
			</div>',
			esc_html__( 'This article requires an adequate license to view.', 'wpchill-kb' ),
			esc_html__( 'To access this content, please purchase or upgrade a license.', 'wpchill-kb' ),
			1 === count( $data ) ? wp_kses_post( $this->render_button( $data[0] ) ) : $this->render_modal_root(),
		);
	}

	private function render_button( $data ) {
		return sprintf(
			'<a href="%s" class="wpchill-kb-login-button">%s %s</a>',
			esc_url( $data['url'] ),
			esc_html( $data['type'] ),
			esc_html( $data['title'] )
		);
	}

	private function render_modal_root() {
		global $post;

		if ( ! isset( $post->ID ) || 0 === $post->ID ) {
			return '';
		}

		return sprintf(
			'<div id="wpchill-kb-license-actions" data-postid="%d"></div>',
			esc_attr( $post->ID ),
		);
	}
}
