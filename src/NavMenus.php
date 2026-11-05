<?php
/**
 * Nav menu locations and BEM markup.
 *
 * @package TanteGroen
 */

namespace TanteGroen;

use Urlund\WordPress\Feature;

/**
 * Registers menu locations and swaps default menu classes for BEM classes.
 *
 * BEM classes are only applied when `wp_nav_menu()` receives a `bem_block` arg,
 * e.g. `'bem_block' => 'main-nav'` gives `main-nav__item`, `main-nav__link`
 * and `main-nav__submenu`.
 */
class NavMenus extends Feature {

	/**
	 * @var array<int|string, mixed>
	 */
	protected $actions = array(
		'after_setup_theme' => 'register_menus',
	);

	/**
	 * @var array<int|string, mixed>
	 */
	protected $filters = array(
		'nav_menu_css_class'         => 'item_classes',
		'nav_menu_submenu_css_class' => 'submenu_classes',
		'nav_menu_link_attributes'   => 'link_attributes',
		'nav_menu_item_id'           => 'item_id',
	);

	/**
	 * Registers theme menu locations.
	 */
	public function register_menus(): void {
		register_nav_menus(
			array(
				'primary'   => __( 'Main menu', 'tg' ),
				'secondary' => __( 'Top bar menu', 'tg' ),
			)
		);
	}

	/**
	 * BEM block name passed to wp_nav_menu(), or empty string.
	 *
	 * @param object|array $args wp_nav_menu() args.
	 * @return string
	 */
	protected function get_block( $args ): string {
		$args = (object) $args;

		return isset( $args->bem_block ) ? sanitize_html_class( (string) $args->bem_block ) : '';
	}

	/**
	 * Replaces menu item classes with BEM classes.
	 *
	 * @param string[] $classes   Default classes.
	 * @param \WP_Post $menu_item Menu item.
	 * @param object   $args      wp_nav_menu() args.
	 * @param int      $depth     Item depth.
	 * @return string[]
	 */
	public function item_classes( $classes, $menu_item, $args, $depth ) {
		$block = $this->get_block( $args );
		if ( '' === $block ) {
			return $classes;
		}

		$classes = (array) $classes;
		$bem     = array( $block . '__item' );

		$current_flags = array( 'current-menu-item', 'current-menu-ancestor', 'current-menu-parent' );
		if ( array_intersect( $current_flags, $classes ) ) {
			$bem[] = $block . '__item--current';
		}

		if ( in_array( 'menu-item-has-children', $classes, true ) ) {
			$bem[] = $block . '__item--has-children';
		}

		return $bem;
	}

	/**
	 * Replaces submenu classes with BEM classes.
	 *
	 * @param string[] $classes Default classes.
	 * @param object   $args    wp_nav_menu() args.
	 * @param int      $depth   Submenu depth.
	 * @return string[]
	 */
	public function submenu_classes( $classes, $args, $depth ) {
		$block = $this->get_block( $args );
		if ( '' === $block ) {
			return $classes;
		}

		return array( $block . '__submenu' );
	}

	/**
	 * Adds the BEM link class.
	 *
	 * @param array<string, string> $atts      Link attributes.
	 * @param \WP_Post              $menu_item Menu item.
	 * @param object                $args      wp_nav_menu() args.
	 * @param int                   $depth     Item depth.
	 * @return array<string, string>
	 */
	public function link_attributes( $atts, $menu_item, $args, $depth ) {
		$block = $this->get_block( $args );
		if ( '' === $block ) {
			return $atts;
		}

		$atts['class'] = trim( ( $atts['class'] ?? '' ) . ' ' . $block . '__link' );

		return $atts;
	}

	/**
	 * Drops the default `menu-item-{ID}` id, since the same menu can render twice.
	 *
	 * @param string   $id        Item id.
	 * @param \WP_Post $menu_item Menu item.
	 * @param object   $args      wp_nav_menu() args.
	 * @param int      $depth     Item depth.
	 * @return string
	 */
	public function item_id( $id, $menu_item, $args, $depth ) {
		return '' === $this->get_block( $args ) ? $id : '';
	}
}
