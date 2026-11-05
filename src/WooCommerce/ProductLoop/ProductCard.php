<?php
/**
 * Product card customizations for the shop loop.
 *
 * @package TanteGroen
 */

namespace TanteGroen\WooCommerce\ProductLoop;

use Urlund\WordPress\Feature;

/**
 * Removes loop add-to-cart and outputs product brand under the title.
 */
class ProductCard extends Feature {

	/**
	 * @var array<int|string, mixed>
	 */
	protected $actions = array(
		'wp'                               => 'remove_loop_add_to_cart',
		'woocommerce_shop_loop_item_title' => array( 'render_brand', 15 ),
	);

	/**
	 * Removes the default add-to-cart button from product cards.
	 */
	public function remove_loop_add_to_cart(): void {
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
	}

	/**
	 * Prints the product brand under the loop title.
	 */
	public function render_brand(): void {
		global $product;

		if ( ! $product || ! is_a( $product, \WC_Product::class ) ) {
			return;
		}

		$brands = get_the_terms( $product->get_id(), 'product_brand' );
		if ( empty( $brands ) || is_wp_error( $brands ) ) {
			return;
		}

		$brand = $brands[0];

		get_template_part(
			'template-parts/woocommerce/product-card/brand',
			null,
			array(
				'brand' => $brand,
			)
		);
	}
}
