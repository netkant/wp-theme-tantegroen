<?php
/**
 * Mega menus for the main navigation.
 *
 * @package TanteGroen
 */

namespace TanteGroen;

use Urlund\WordPress\Feature;
use WP_Query;
use WP_Term;

/**
 * Renders `tg_mega_menu` posts under top-level items in the main menu.
 *
 * A menu item gets a mega menu through the ACF field `mega_menu`. Only
 * `wp_nav_menu()` calls with `'bem_block' => 'main-nav'` (desktop panel) or
 * `'bem_block' => 'drawer-nav'` (mobile drill-down levels) render it.
 */
class MegaMenu extends Feature {

	const POST_TYPE = 'tg_mega_menu';

	const NAV_BLOCK = 'main-nav';

	const DRAWER_BLOCK = 'drawer-nav';

	const CACHE_VERSION_OPTION = 'tg_mega_menu_cache_version';

	const DEFAULT_LIMIT = 4;

	/**
	 * @var array<int|string, mixed>
	 */
	protected $actions = array(
		'save_post_tg_mega_menu' => 'flush_cache',
		'save_post_product'      => 'flush_cache',
		'edited_term'            => 'flush_term_cache',
		'created_term'           => 'flush_term_cache',
		'delete_term'            => 'flush_term_cache',
	);

	/**
	 * @var array<int|string, mixed>
	 */
	protected $filters = array(
		'nav_menu_css_class'                                => array( 'item_classes', 20 ),
		'nav_menu_link_attributes'                          => array( 'link_attributes', 20 ),
		'walker_nav_menu_start_el'                          => 'append_panel',
		'acf/load_field/key=field_tg_mm_col_attribute_term' => 'load_attribute_term_choices',
	);

	/**
	 * Mega menu post ID per menu item ID.
	 *
	 * @var array<int, int>
	 */
	private static $item_mega_menus = array();

	/**
	 * Published mega menu ID attached to a menu item, or 0.
	 *
	 * @param \WP_Post $menu_item Menu item.
	 * @return int
	 */
	public static function get_item_mega_menu_id( $menu_item ): int {
		$item_id = (int) $menu_item->ID;

		if ( ! isset( self::$item_mega_menus[ $item_id ] ) ) {
			$mega_menu_id = function_exists( 'get_field' ) ? (int) get_field( 'mega_menu', $item_id ) : 0;

			self::$item_mega_menus[ $item_id ] = $mega_menu_id && 'publish' === get_post_status( $mega_menu_id ) ? $mega_menu_id : 0;
		}

		return self::$item_mega_menus[ $item_id ];
	}

	/**
	 * BEM block of a top-level main-nav or drawer-nav item with a mega menu, or empty string.
	 *
	 * @param \WP_Post $menu_item Menu item.
	 * @param object   $args      wp_nav_menu() args.
	 * @param int      $depth     Item depth.
	 * @return string
	 */
	protected function get_mega_menu_block( $menu_item, $args, $depth ): string {
		$args  = (object) $args;
		$block = $args->bem_block ?? '';

		if ( 0 !== (int) $depth || ! in_array( $block, array( self::NAV_BLOCK, self::DRAWER_BLOCK ), true ) ) {
			return '';
		}

		return self::get_item_mega_menu_id( $menu_item ) ? $block : '';
	}

	/**
	 * Panel element ID for a menu item.
	 *
	 * @param \WP_Post $menu_item Menu item.
	 * @return string
	 */
	public static function get_panel_id( $menu_item ): string {
		return 'tg-mega-menu-' . (int) $menu_item->ID;
	}

	/**
	 * Drawer level element ID for a menu item.
	 *
	 * @param \WP_Post $menu_item Menu item.
	 * @return string
	 */
	public static function get_drawer_level_id( $menu_item ): string {
		return 'tg-drawer-level-' . (int) $menu_item->ID;
	}

	/**
	 * Adds the has-mega (main-nav) or has-children (drawer-nav) modifier.
	 *
	 * @param string[] $classes   Item classes.
	 * @param \WP_Post $menu_item Menu item.
	 * @param object   $args      wp_nav_menu() args.
	 * @param int      $depth     Item depth.
	 * @return string[]
	 */
	public function item_classes( $classes, $menu_item, $args, $depth ) {
		$block = $this->get_mega_menu_block( $menu_item, $args, $depth );
		if ( '' === $block ) {
			return $classes;
		}

		$classes   = (array) $classes;
		$classes[] = self::NAV_BLOCK === $block ? $block . '__item--has-mega' : $block . '__item--has-children';

		return array_unique( $classes );
	}

	/**
	 * Adds ARIA attributes linking the top-level main-nav link to its panel.
	 *
	 * @param array<string, string> $atts      Link attributes.
	 * @param \WP_Post              $menu_item Menu item.
	 * @param object                $args      wp_nav_menu() args.
	 * @param int                   $depth     Item depth.
	 * @return array<string, string>
	 */
	public function link_attributes( $atts, $menu_item, $args, $depth ) {
		if ( self::NAV_BLOCK === $this->get_mega_menu_block( $menu_item, $args, $depth ) ) {
			$atts['aria-haspopup'] = 'true';
			$atts['aria-expanded'] = 'false';
			$atts['aria-controls'] = self::get_panel_id( $menu_item );
		}

		return $atts;
	}

	/**
	 * Appends the desktop panel after the main-nav link, or replaces the
	 * drawer-nav link with a toggle and its drill-down level.
	 *
	 * @param string   $item_output Item markup.
	 * @param \WP_Post $menu_item   Menu item.
	 * @param int      $depth       Item depth.
	 * @param object   $args        wp_nav_menu() args.
	 * @return string
	 */
	public function append_panel( $item_output, $menu_item, $depth, $args ) {
		$block = $this->get_mega_menu_block( $menu_item, $args, $depth );

		if ( self::NAV_BLOCK === $block ) {
			return $item_output . self::render(
				'template-parts/header/mega-menu',
				array(
					'mega_menu_id' => self::get_item_mega_menu_id( $menu_item ),
					'panel_id'     => self::get_panel_id( $menu_item ),
				)
			);
		}

		if ( self::DRAWER_BLOCK === $block ) {
			return self::render(
				'template-parts/header/drawer-mega-menu',
				array(
					'mega_menu_id' => self::get_item_mega_menu_id( $menu_item ),
					'level_id'     => self::get_drawer_level_id( $menu_item ),
					'title'        => apply_filters( 'the_title', $menu_item->title, $menu_item->ID ),
					'url'          => $menu_item->url,
				)
			);
		}

		return $item_output;
	}

	/**
	 * Rendered template markup, cached until mega menus, products or product terms change.
	 *
	 * @param string               $template Template part slug.
	 * @param array<string, mixed> $args     Template args; part of the cache key.
	 * @return string
	 */
	public static function render( string $template, array $args ): string {
		$cache_key = sprintf(
			'tg_mega_menu_%d_%s_%d',
			(int) ( $args['mega_menu_id'] ?? 0 ),
			md5( $template . wp_json_encode( $args ) ),
			(int) get_option( self::CACHE_VERSION_OPTION, 0 )
		);
		$html      = get_transient( $cache_key );

		if ( false === $html ) {
			ob_start();
			get_template_part( $template, null, $args );
			$html = (string) ob_get_clean();

			set_transient( $cache_key, $html, DAY_IN_SECONDS );
		}

		return $html;
	}

	/**
	 * Invalidates all cached panels.
	 */
	public function flush_cache() {
		update_option( self::CACHE_VERSION_OPTION, (int) get_option( self::CACHE_VERSION_OPTION, 0 ) + 1, false );
	}

	/**
	 * Invalidates cached panels when a product taxonomy term changes.
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 */
	public function flush_term_cache( $term_id, $tt_id, $taxonomy ) {
		if ( in_array( $taxonomy, array( 'product_cat', 'product_tag' ), true ) || 0 === strpos( (string) $taxonomy, 'pa_' ) ) {
			$this->flush_cache();
		}
	}

	/**
	 * Fills the attribute term select with all `pa_*` terms, grouped by attribute.
	 *
	 * @param array $field ACF field.
	 * @return array
	 */
	public function load_attribute_term_choices( $field ) {
		if ( ! function_exists( 'wc_get_attribute_taxonomies' ) || ( function_exists( 'acf_is_screen' ) && acf_is_screen( 'acf-field-group' ) ) ) {
			return $field;
		}

		$choices = array();

		foreach ( wc_get_attribute_taxonomies() as $attribute ) {
			$taxonomy = wc_attribute_taxonomy_name( $attribute->attribute_name );
			$terms    = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
				)
			);

			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}

			foreach ( $terms as $term ) {
				$choices[ $attribute->attribute_label ][ $taxonomy . ':' . $term->term_id ] = $term->name;
			}
		}

		$field['choices'] = $choices;

		return $field;
	}

	/**
	 * Term selected in a product column (category, attribute or tag).
	 *
	 * @param array $column Column repeater row.
	 * @return WP_Term|null
	 */
	public static function get_column_term( array $column ): ?WP_Term {
		$type = $column['type'] ?? '';
		$term = null;

		if ( 'category' === $type || 'tag' === $type ) {
			$value    = $column[ $type ] ?? null;
			$taxonomy = 'category' === $type ? 'product_cat' : 'product_tag';
			$term     = $value instanceof WP_Term ? $value : ( $value ? get_term( (int) $value, $taxonomy ) : null );
		} elseif ( 'attribute' === $type && ! empty( $column['attribute_term'] ) ) {
			list( $taxonomy, $term_id ) = array_pad( explode( ':', (string) $column['attribute_term'], 2 ), 2, 0 );

			$term = get_term( (int) $term_id, sanitize_key( $taxonomy ) );
		}

		return $term instanceof WP_Term ? $term : null;
	}

	/**
	 * Title and URL for a column, without products: the term archive for
	 * product columns, the card link for image columns.
	 *
	 * @param array $column Column repeater row.
	 * @return array{title: string, url: string}|null
	 */
	public static function get_column_link( array $column ): ?array {
		if ( 'image' === ( $column['type'] ?? '' ) ) {
			$link  = $column['link'] ?: array();
			$title = ( $column['text'] ?? '' ) ?: ( $link['title'] ?? '' );

			return $title && ! empty( $link['url'] ) ? array(
				'title' => $title,
				'url'   => $link['url'],
			) : null;
		}

		$term = self::get_column_term( $column );
		if ( ! $term ) {
			return null;
		}

		return array(
			'title' => ( $column['title'] ?? '' ) ?: $term->name,
			'url'   => self::get_term_archive_url( $term ),
		);
	}

	/**
	 * IDs of visible, published products in a term.
	 *
	 * @param WP_Term $term  Product taxonomy term.
	 * @param int     $limit Max number of products.
	 * @return int[]
	 */
	public static function get_column_products( WP_Term $term, int $limit = self::DEFAULT_LIMIT ): array {
		$tax_query = array(
			array(
				'taxonomy' => $term->taxonomy,
				'field'    => 'term_id',
				'terms'    => $term->term_id,
			),
		);

		if ( function_exists( 'wc_get_product_visibility_term_ids' ) ) {
			$visibility = wc_get_product_visibility_term_ids();
			$excluded   = array( $visibility['exclude-from-catalog'] ?? 0 );

			if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
				$excluded[] = $visibility['outofstock'] ?? 0;
			}

			$tax_query[] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => array_filter( $excluded ),
				'operator' => 'NOT IN',
			);
		}

		$query = new WP_Query(
			array(
				'post_type'           => 'product',
				'post_status'         => 'publish',
				'posts_per_page'      => max( 1, $limit ),
				'fields'              => 'ids',
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
				'orderby'             => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
				'tax_query'           => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			)
		);

		return array_map( 'intval', $query->posts );
	}

	/**
	 * Archive URL for a product term.
	 *
	 * Attributes without archives link to the shop filtered by the term.
	 *
	 * @param WP_Term $term Product taxonomy term.
	 * @return string
	 */
	public static function get_term_archive_url( WP_Term $term ): string {
		if ( 0 === strpos( $term->taxonomy, 'pa_' ) && function_exists( 'wc_attribute_taxonomy_id_by_name' ) ) {
			$attribute = wc_get_attribute( wc_attribute_taxonomy_id_by_name( $term->taxonomy ) );

			if ( ! $attribute || ! $attribute->has_archives ) {
				return add_query_arg(
					'filter_' . wc_attribute_taxonomy_slug( $term->taxonomy ),
					$term->slug,
					wc_get_page_permalink( 'shop' )
				);
			}
		}

		$url = get_term_link( $term );

		return is_wp_error( $url ) ? '' : $url;
	}
}
