<?php
/**
 * Site header helpers.
 *
 * @package TanteGroen
 */

namespace TanteGroen;

use Urlund\WordPress\Feature;

/**
 * Header icons and WooCommerce cart count fragment.
 */
class Header extends Feature {

	/**
	 * @var array<int|string, mixed>
	 */
	protected $filters = array(
		'woocommerce_add_to_cart_fragments' => 'cart_count_fragment',
	);

	/**
	 * Loaded SVG markup keyed by icon name.
	 *
	 * @var array<string, string>
	 */
	private static $icons = array();

	/**
	 * Returns inline SVG markup for an icon in assets/images/icons/.
	 *
	 * @param string $name  Icon file name without extension.
	 * @param string $class Optional class for the svg element.
	 * @return string
	 */
	public static function get_icon( string $name, string $class = '' ): string {
		$name = sanitize_key( $name );

		if ( ! isset( self::$icons[ $name ] ) ) {
			$path                 = self::get_theme_dir() . 'assets/images/icons/' . $name . '.svg';
			self::$icons[ $name ] = file_exists( $path ) ? trim( (string) file_get_contents( $path ) ) : '';
		}

		$svg = self::$icons[ $name ];
		if ( '' === $svg || '' === $class ) {
			return $svg;
		}

		return preg_replace( '/^<svg /', '<svg class="' . esc_attr( $class ) . '" ', $svg, 1 );
	}

	/**
	 * Prints an inline SVG icon.
	 *
	 * @param string $name  Icon file name without extension.
	 * @param string $class Optional class for the svg element.
	 */
	public static function icon( string $name, string $class = '' ): void {
		echo self::get_icon( $name, $class ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme SVG file.
	}

	/**
	 * Number of items in the WooCommerce cart.
	 *
	 * @return int
	 */
	public static function get_cart_count(): int {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return 0;
		}

		return (int) WC()->cart->get_cart_contents_count();
	}

	/**
	 * Cart count badge markup. Always rendered so cart fragments can replace it.
	 *
	 * @return string
	 */
	public static function get_cart_count_markup(): string {
		$count = self::get_cart_count();

		return sprintf(
			'<span class="header-actions__count"%s>%s</span>',
			$count > 0 ? '' : ' hidden',
			esc_html( (string) $count )
		);
	}

	/**
	 * Refreshes the cart count badge after AJAX add-to-cart.
	 *
	 * @param array<string, string> $fragments Cart fragments.
	 * @return array<string, string>
	 */
	public function cart_count_fragment( $fragments ) {
		$fragments['.header-actions__count'] = self::get_cart_count_markup();

		return $fragments;
	}
}
