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
	public function is_edd_active() {

		$is_edd_active    = class_exists( 'Easy_Digital_Downloads' );
		$is_edd_sl_active = class_exists( 'EDD_Software_Licensing' );

		return $is_edd_active && $is_edd_sl_active;
	}

	/**
	 * Checks if WooCommerce and WooCommerce Subscriptions are active.
	 *
	 * @return bool True if both WooCommerce and WooCommerce Subscriptions are active, false otherwise.
	 */
	public function is_woo_active() {

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

		$results = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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

		$results = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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
		if ( false !== $edd && ! empty( $edd['modal'] ) ) {
			$license_messages = $edd;
		}

		// Do WooCommerce subscription check
		$woo = $this->check_woo_subscription( $post_id );
		if ( false !== $woo && ! empty( $woo['modal'] ) ) {
			if ( is_array( $license_messages ) ) {
				$license_messages = array_merge( $license_messages, $woo );
			} else {
				$license_messages = $woo;
			}
		}

		if ( false !== $license_messages ) {
			return $this->render_lock_screen( $license_messages );
		}

		// Returning false will display the original content.
		return false;
	}

	/**
	 * Retrieves unlocking options data for a locked post.
	 *
	 * @param int $post_id The ID of the post to check.
	 * @return array
	 */
	public function get_license_modal_data( $post_id ) {
		// If we got here it means the article is locked and the user is logged in.
		$data = array();

		// Do Easy Digital Download license check
		$edd = $this->check_edd_license( $post_id );
		if ( false !== $edd && ! empty( $edd['modal'] ) ) {
			$data = $edd;
		}

		// Do WooCommerce subscription check
		$woo = $this->check_woo_subscription( $post_id );
		if ( false !== $woo && ! empty( $woo['modal'] ) ) {
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

		$purchases = edd_get_users_purchases( get_current_user_id(), 20, true, 'any' );
		$lock_data = array();

		// Get the cheapest product required.
		$cheapest_downloads = $this->get_cheapest_edd_products( $post_id );
		$cheapest_download  = absint( array_key_first( $cheapest_downloads ) );

		$lock_data['products'] = $this->get_edd_package_products( $cheapest_download );

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

			$lock_data['modal'][] = array(
				'type'     => __( 'Purchase', 'wpchill-kb' ),
				'download' => $cheapest_download,
				'title'    => html_entity_decode( get_the_title( $cheapest_download ), ENT_QUOTES, 'UTF-8' ),
				'least'    => html_entity_decode( get_the_title( $cheapest_download ), ENT_QUOTES, 'UTF-8' ),
				'price'    => $this->get_edd_formatted_price( edd_get_download_price( $cheapest_download ) ),
				'url'      => $url,
			);

			return $lock_data;
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
				$lock_data['modal'][] = array(
					'type'     => __( 'Renew', 'wpchill-kb' ),
					'download' => $download,
					'title'    => html_entity_decode( get_the_title( $download ), ENT_QUOTES, 'UTF-8' ),
					'least'    => html_entity_decode( get_the_title( $cheapest_download ), ENT_QUOTES, 'UTF-8' ),
					'price'    => html_entity_decode( $this->get_edd_formatted_price( edd_get_download_price( $cheapest_download ) ) ),
					'key'      => $key,
					'url'      => $esl->get_renewal_url( $license->ID ),
				);
			} else {
				$upgrades = edd_sl_get_license_upgrades( $license->ID );
				foreach ( $upgrades as $upgrade_id => $upgrade ) {
					if ( isset( $upgrade['download_id'] ) && isset( $cheapest_downloads[ $upgrade['download_id'] ] ) ) {
						$upgrade_price        = absint( edd_sl_get_license_upgrade_cost( $license->ID, $upgrade_id ) );
						$lock_data['modal'][] = array(
							'type'     => __( 'Upgrade', 'wpchill-kb' ),
							'download' => $upgrade['download_id'],
							'title'    => html_entity_decode( get_the_title( $download ), ENT_QUOTES, 'UTF-8' ),
							'least'    => html_entity_decode( get_the_title( $cheapest_download ), ENT_QUOTES, 'UTF-8' ),
							'price'    => html_entity_decode( $this->get_edd_formatted_price( $upgrade_price ) ),
							'key'      => $key,
							'url'      => edd_sl_get_license_upgrade_url( $license->ID, $upgrade_id ),
						);
						break;
					}
				}
			}
		}
		// No upgrades?
		if ( empty( $lock_data['modal'] ) && 0 !== $cheapest_download ) {
			$url                  = add_query_arg(
				array(
					'edd_action'  => 'add_to_cart',
					'download_id' => $cheapest_download,
				),
				esc_url_raw( edd_get_checkout_uri() )
			);
			$lock_data['modal'][] = array(
				'type'     => __( 'Purchase', 'wpchill-kb' ),
				'download' => $cheapest_download,
				'title'    => html_entity_decode( get_the_title( $cheapest_download ), ENT_QUOTES, 'UTF-8' ),
				'least'    => html_entity_decode( get_the_title( $cheapest_download ), ENT_QUOTES, 'UTF-8' ),
				'price'    => $this->get_edd_formatted_price( edd_get_download_price( $cheapest_download ) ),
				'url'      => $url,
			);
		}
		return $lock_data;
	}

	/**
	 * Retrieves the cheapest EDD products ordered low to high required for a locked post.
	 *
	 * @param int $post_id The ID of the post to check.
	 * @return array An associative array of product IDs and their prices.
	 */
	public function get_cheapest_edd_products( $post_id, $first = false ) {
		$download_price = array();

		foreach ( $this->get_locked_downloads( $post_id ) as $product_id ) {
			$download_price[ absint( $product_id ) ] = floor( edd_get_download_price( absint( $product_id ) ) );
		}

		asort( $download_price );

		if ( $first ) {
			return absint( array_key_first( $download_price ) );
		}

		return $download_price;
	}

	/**
	 * Retrieves the cheapest WooCommerce products ordered low to high required for a locked post.
	 *
	 * @param int $post_id The ID of the post to check.
	 * @return array An associative array of product IDs and their prices.
	 */
	public function get_cheapest_woo_products( $post_id, $first = false ) {
		$download_price = array();

		foreach ( $this->get_locked_downloads( $post_id, 'woo' ) as $product_id ) {
			$product = wc_get_product( absint( $product_id ) );

			if ( ! $product ) {
				continue;
			}

			$download_price[ absint( $product_id ) ] = floor( $product->get_price() );
		}
		asort( $download_price );

		if ( $first ) {
			return absint( array_key_first( $download_price ) );
		}

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
		$lock_data = array();

		if ( ! $this->is_woo_active() ) {
			return false;
		}

		// Get the cheapest product required.
		$cheapest_downloads = $this->get_cheapest_woo_products( $post_id );

		if ( empty( $cheapest_downloads ) ) {
			return false;
		}

		$cheapest_download = absint( array_key_first( $cheapest_downloads ) );
		$locking_products  = array_map( 'absint', $this->get_locked_downloads( $post_id, 'woo' ) );
		$user_id           = get_current_user_id();
		$buy_link_set      = false;
		$price             = 0;

		$product = wc_get_product( absint( $cheapest_download ) );

		$price = $product->get_price();

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

			$lock_data['modal'][] = array(
				'type'     => __( 'Purchase', 'wpchill-kb' ),
				'download' => $cheapest_download,
				'title'    => html_entity_decode( get_the_title( $cheapest_download ), ENT_QUOTES, 'UTF-8' ),
				'least'    => html_entity_decode( get_the_title( $cheapest_download ), ENT_QUOTES, 'UTF-8' ),
				'price'    => $this->get_woo_formatted_price( $product->get_price() ),
				'url'      => $url,
			);

			return $lock_data;
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
						$product              = wc_get_product( $download );
						$lock_data['modal'][] = array(
							'type'     => __( 'Resubscribe', 'wpchill-kb' ),
							'download' => $download,
							'title'    => html_entity_decode( get_the_title( $download ), ENT_QUOTES, 'UTF-8' ),
							'price'    => $this->get_woo_formatted_price( $price ),
							'url'      => html_entity_decode( wcs_get_users_resubscribe_link( $subscription ), ENT_QUOTES, 'UTF-8' ),
						);
					} elseif ( ! $buy_link_set ) {
						// Cannot renew. Show purchase link.
						$url                  = add_query_arg(
							array(
								'add-to-cart' => $cheapest_download,
							),
							esc_url_raw( wc_get_checkout_url() )
						);
						$lock_data['modal'][] = array(
							'type'     => __( 'Purchase', 'wpchill-kb' ),
							'download' => $cheapest_download,
							'title'    => html_entity_decode( get_the_title( $cheapest_download ), ENT_QUOTES, 'UTF-8' ),
							'least'    => html_entity_decode( get_the_title( $cheapest_download ), ENT_QUOTES, 'UTF-8' ),
							'price'    => $this->get_woo_formatted_price( $price ),
							'url'      => $url,
						);
						$buy_link_set         = true;
					}
				} elseif ( ! $buy_link_set ) {
					// If we are here it means no download matched. Buy a new sub.
					$url = add_query_arg(
						array(
							'add-to-cart' => $cheapest_download,
						),
						esc_url_raw( wc_get_checkout_url() )
					);

					$lock_data['modal'][] = array(
						'type'     => __( 'Purchase', 'wpchill-kb' ),
						'download' => $cheapest_download,
						'title'    => html_entity_decode( get_the_title( $cheapest_download ), ENT_QUOTES, 'UTF-8' ),
						'least'    => html_entity_decode( get_the_title( $cheapest_download ), ENT_QUOTES, 'UTF-8' ),
						'price'    => $this->get_woo_formatted_price( $price ),
						'url'      => $url,
					);

					$buy_link_set = true;
				}
			}
		}
		return $lock_data;
	}

	private function render_lock_screen( $data ) {
		$html  = '<div class="wpchill-kb-locked-message">';
		$html .= ! empty( $data['modal'] ) ? $this->button_or_modal( $data['modal'] ) : $this->button_or_modal();
		$html .= ! empty( $data['products'] ) ? $this->products_badges( $data['products'] ) : '';
		$html .= '</div>';

		return $html;
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
	private function button_or_modal( $data = false ) {

		if ( ! is_array( $data ) || empty( $data ) ) {
			return '<h3>' . esc_html__( 'This article is locked and cannot be viewed.', 'wpchill-kb' ) . '</h3>
					<p>' . esc_html__( 'This access this article please contact the site administrator.', 'wpchill-kb' ) . '</p>';
		}

		return sprintf(
			'<h2 style="font-size:30px;">%s</h2>
				<p class="wpchill-kb-sub-req-text">%s</p>
				<p>%s</p>',
			esc_html__( 'You need a subscription to read this article.', 'wpchill-kb' ),
			/* translators: 1$s: opening strong tag, 2$s: required subscription level name, 3$s: closing strong tag */
			sprintf( esc_html__( 'To see this article, you must have an active subscription of at least %1$s %2$s %3$s', 'wpchill-kb' ), '<strong>', isset( $data[0]['least'] ) ? $data[0]['least'] : $data[0]['title'], '</strong>', ),
			1 === count( $data ) ? wp_kses_post( $this->render_button( $data[0] ) ) : $this->render_modal_root(),
		);
	}

	/**
	 * Renders a buy/upgrade/renew button if only one options is available to the customer.
	 *
	 * @param array button data.
	 * @return array
	 */
	private function render_button( $data ) {
		return sprintf(
			'<a href="%s" class="wpchill-kb-login-button">%s %s %s</a>',
			esc_url( $data['url'] ),
			isset( $data['price'] ) ? wp_kses_post( $data['price'] ) : '',
			esc_html( $data['type'] ),
			esc_html( $data['title'] )
		);
	}

	/**
	 * Renders the root element for the license actions modal.
	 *
	 * @return string The HTML for the root element, or an empty string if the post ID is not available.
	 */
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

	private function products_badges( $products ) {
		$html  = '<p class="wpchill-kb-badges-info">' . esc_html__( 'The subscription grants you access to the following addons:', 'wpchill-kb' ) . '</p>';
		$html .= '<ul class="wpchill-kb-addons-list">';

		if ( ! empty( $products ) && is_array( $products ) ) {
			foreach ( $products as $product ) {
				$name = isset( $product['name'] ) ? esc_html( $product['name'] ) : '';

				$html .= '<li class="wpchill-kb-addon">
							<span class="wpchill-kb-checkmark dashicons dashicons-yes-alt"></span>
							<span class="wpchill-kb-addon-name">' . $name . '</span>
						  </li>';
			}
		}

		$html .= '</ul>';

		return $html;
	}


	public function get_edd_formatted_price( $price ) {
		$price = number_format( $price, 2, '.', '' );
		return sprintf( '<span wpchill-kb-price-wrap><sup>%s</sup><span wpchill-kb-price>%s</span></span>', edd_currency_symbol( edd_get_currency() ), $price );
	}

	public function get_woo_formatted_price( $price ) {
		$price = number_format( $price, 2, '.', '' );
		return sprintf( '<span wpchill-kb-price-wrap><sup>%s</sup><span wpchill-kb-price>%s</span></span>', get_woocommerce_currency_symbol(), $price );
	}

	private function get_edd_package_products( $package_id ) {

		$bundled_addons = get_post_meta( $package_id, '_edd_bundled_products', true );

		$products = array();

		if ( ! empty( $bundled_addons ) ) {
			foreach ( $bundled_addons as $ba ) {
				$id = intval( $ba );

				$bundle        = get_post( $id );
				$slug          = get_post_field( 'post_name', $bundle );
				$attachment_id = get_post_thumbnail_id( $id );

				if ( $attachment_id ) {
					$icon = wp_get_attachment_url( $attachment_id );
				}

				$addon = array(
					'name'        => get_post_field( 'post_title', $bundle ),
					'slug'        => get_post_field( 'post_name', $bundle ),
					'description' => get_post_field( 'post_excerpt', $bundle ),
				);

				$products[ $slug ] = $addon;
			}
		}

		return $products;
	}

	private function get_woo_package_products() {
		$args = array(
			'post_type'      => 'api_product',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		);

		$posts = get_posts( $args );

		foreach ( $posts as $post ) {
			$slug  = get_post_field( 'post_name', $post->ID );
			$addon = array(
				'name'        => get_post_field( 'post_title', $post->ID ),
				'slug'        => $slug,
				'description' => get_post_field( 'post_excerpt', $post->ID ),
				'changelog'   => get_post_meta( $post->ID, '_changelog', true ),
				'icon'        => $this->get_featured_image_url_by_slug( $slug ),
			);

			$this->addons[ $slug ] = $addon;
		}
	}
}
