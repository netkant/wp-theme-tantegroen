<?php
/**
 * Product category grid header (counter, attribute filters, sorting).
 *
 * @package TanteGroen
 */

namespace TanteGroen\WooCommerce\ProductCategory;

use Urlund\WordPress\Feature;
use WP_Term;

/**
 * Renders the shop-loop toolbar via template parts.
 */
class CategoryGridHeader extends Feature {

	/**
	 * @var array<int|string, mixed>
	 */
	protected $actions = array(
		'wp'                             => 'remove_default_loop_chrome',
		'woocommerce_before_shop_loop'   => array( 'render_grid_header', 15 ),
		'woocommerce_no_products_found'  => array( 'render_grid_header', 5 ),
		'set_object_terms'               => 'on_set_object_terms',
		'save_post_product'              => 'on_save_product',
		'created_product_cat'            => 'on_product_cat_changed',
		'edited_product_cat'             => 'on_product_cat_changed',
		'delete_product_cat'             => 'on_product_cat_changed',
		'created_term'                   => 'on_term_changed',
		'edited_term'                    => 'on_term_changed',
		'delete_term'                    => 'on_term_changed',
	);

	/**
	 * Removes default WC result count and catalog ordering on product categories.
	 */
	public function remove_default_loop_chrome(): void {
		if ( ! $this->is_product_category_archive() ) {
			return;
		}

		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
	}

	/**
	 * Prints the grid header template part.
	 */
	public function render_grid_header(): void {
		if ( ! $this->is_product_category_archive() ) {
			return;
		}

		$context = $this->get_context();
		if ( empty( $context ) ) {
			return;
		}

		get_template_part(
			'template-parts/woocommerce/product-category/grid-header',
			null,
			$context
		);
	}

	/**
	 * Invalidates attribute/count caches when product terms change.
	 *
	 * @param int    $object_id  Object ID.
	 * @param array  $terms      Term IDs or slugs.
	 * @param array  $tt_ids     Term taxonomy IDs.
	 * @param string $taxonomy   Taxonomy slug.
	 * @param bool   $append     Whether terms were appended.
	 * @param array  $old_tt_ids Previous term taxonomy IDs.
	 */
	public function on_set_object_terms( $object_id, $terms, $tt_ids, $taxonomy, $append = false, $old_tt_ids = array() ): void {
		unset( $terms, $tt_ids, $append, $old_tt_ids );

		$is_product_cat = ( 'product_cat' === $taxonomy );
		$is_attribute   = is_string( $taxonomy ) && 0 === strpos( $taxonomy, 'pa_' );

		if ( ! $is_product_cat && ! $is_attribute ) {
			return;
		}

		if ( 'product' !== get_post_type( (int) $object_id ) ) {
			return;
		}

		CategoryAttributesQuery::clear_cache_for_product( (int) $object_id );
	}

	/**
	 * @param int $post_id Product ID.
	 */
	public function on_save_product( int $post_id ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		CategoryAttributesQuery::clear_cache_for_product( $post_id );
	}

	/**
	 * @param int $term_id Term ID.
	 */
	public function on_product_cat_changed( int $term_id ): void {
		CategoryAttributesQuery::clear_cache_for_category_and_ancestors( $term_id );
	}

	/**
	 * Broad invalidation when an attribute term changes.
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 */
	public function on_term_changed( $term_id, $tt_id = 0, $taxonomy = '' ): void {
		unset( $term_id, $tt_id );

		if ( ! is_string( $taxonomy ) || 0 !== strpos( $taxonomy, 'pa_' ) ) {
			return;
		}

		CategoryAttributesQuery::clear_all_category_caches();
	}

	/**
	 * Builds template context for the grid header.
	 *
	 * @return array<string, mixed>
	 */
	public function get_context(): array {
		$term = get_queried_object();
		if ( ! $term instanceof WP_Term || 'product_cat' !== $term->taxonomy ) {
			return array();
		}

		global $wp_query;

		$base_url    = get_term_link( $term );
		$attributes  = CategoryAttributesQuery::get_attributes_for_category( (int) $term->term_id );
		$active      = $this->get_active_attribute_filters( $attributes );

		return array(
			'term'             => $term,
			'base_url'         => is_wp_error( $base_url ) ? '' : $base_url,
			'shown_count'      => isset( $wp_query->post_count ) ? (int) $wp_query->post_count : 0,
			'total_count'      => CategoryAttributesQuery::get_product_count_for_category( (int) $term->term_id ),
			'attributes'       => $attributes,
			'active_filters'   => $active,
			'preserved_query'  => $this->get_preserved_query_args( $attributes ),
		);
	}

	/**
	 * Active single-select filter values keyed by attribute name.
	 *
	 * @param array<int, array{name: string}> $attributes Attribute list.
	 * @return array<string, string>
	 */
	private function get_active_attribute_filters( array $attributes ): array {
		$active = array();

		foreach ( $attributes as $attribute ) {
			$name = isset( $attribute['name'] ) ? (string) $attribute['name'] : '';
			if ( '' === $name ) {
				continue;
			}

			$key = 'filter_' . $name;
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( empty( $_GET[ $key ] ) ) {
				continue;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$raw = wp_unslash( (string) $_GET[ $key ] );
			// Single-select: take first slug if a comma list was passed.
			$parts = array_filter( array_map( 'sanitize_title', explode( ',', $raw ) ) );
			if ( ! empty( $parts ) ) {
				$active[ $name ] = (string) reset( $parts );
			}
		}

		return $active;
	}

	/**
	 * Query args to preserve in the attribute filter form (hidden inputs).
	 *
	 * @param array<int, array{name: string}> $attributes Attribute list.
	 * @return array<string, string>
	 */
	private function get_preserved_query_args( array $attributes ): array {
		$skip = array( 'paged', 'product-page' );

		foreach ( $attributes as $attribute ) {
			if ( ! empty( $attribute['name'] ) ) {
				$skip[] = 'filter_' . $attribute['name'];
				$skip[] = 'query_type_' . $attribute['name'];
			}
		}

		$preserved = array();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		foreach ( (array) wp_unslash( $_GET ) as $key => $value ) {
			$key = (string) $key;
			if ( in_array( $key, $skip, true ) || ! is_scalar( $value ) ) {
				continue;
			}

			$preserved[ $key ] = sanitize_text_field( (string) $value );
		}

		return $preserved;
	}

	/**
	 * @return bool
	 */
	private function is_product_category_archive(): bool {
		return function_exists( 'is_product_category' ) && is_product_category();
	}
}
