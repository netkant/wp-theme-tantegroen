<?php
/**
 * Product category archive header.
 *
 * @package TanteGroen
 */

namespace TanteGroen\WooCommerce\ProductCategory;

use Urlund\WordPress\Feature;
use WP_Query;
use WP_Term;

/**
 * Renders category archive header via template parts and filters by product_tag.
 */
class CategoryArchiveHeader extends Feature {

	/**
	 * Query string key for tag filtering.
	 */
	const TAG_QUERY_VAR = 'product_tag';

	/**
	 * @var array<int|string, mixed>
	 */
	protected $actions = array(
		'wp'                              => 'remove_default_archive_chrome',
		'woocommerce_before_main_content' => array( 'render_header', 15 ),
		'woocommerce_product_query'       => 'filter_products_by_tag',
		'set_object_terms'                => 'on_set_object_terms',
		'save_post_product'               => 'on_save_product',
		'created_product_cat'             => 'on_product_cat_changed',
		'edited_product_cat'              => 'on_product_cat_changed',
		'delete_product_cat'              => 'on_product_cat_changed',
		'created_product_tag'             => 'on_product_tag_changed',
		'edited_product_tag'              => 'on_product_tag_changed',
		'delete_product_tag'              => 'on_product_tag_changed',
	);

	/**
	 * Removes default WooCommerce archive title/breadcrumb/description on product categories.
	 */
	public function remove_default_archive_chrome(): void {
		if ( ! $this->is_product_category_archive() ) {
			return;
		}

		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
		remove_action( 'woocommerce_shop_loop_header', 'woocommerce_product_taxonomy_archive_header', 10 );
		remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
		remove_action( 'woocommerce_archive_description', 'woocommerce_product_archive_description', 10 );
	}

	/**
	 * Prints the custom category header template part.
	 */
	public function render_header(): void {
		if ( ! $this->is_product_category_archive() ) {
			return;
		}

		$context = $this->get_context();
		if ( empty( $context ) ) {
			return;
		}

		get_template_part(
			'template-parts/woocommerce/product-category/header',
			null,
			$context
		);
	}

	/**
	 * Adds product_tag tax_query when ?product_tag=slug is present.
	 *
	 * Runs on woocommerce_product_query so it applies after WC builds the category tax_query.
	 *
	 * @param WP_Query $query Query instance.
	 */
	public function filter_products_by_tag( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( ! $query->is_tax( 'product_cat' ) ) {
			return;
		}

		$slug = $this->get_requested_tag_slug();
		if ( '' === $slug ) {
			return;
		}

		$tag = get_term_by( 'slug', $slug, 'product_tag' );
		if ( ! $tag || is_wp_error( $tag ) ) {
			return;
		}

		$tax_query = $query->get( 'tax_query' );
		if ( ! is_array( $tax_query ) ) {
			$tax_query = array();
		}

		$tax_query[] = array(
			'taxonomy' => 'product_tag',
			'field'    => 'slug',
			'terms'    => array( $slug ),
		);

		$query->set( 'tax_query', $tax_query );
	}

	/**
	 * Invalidates tag caches when product terms change.
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

		if ( ! in_array( $taxonomy, array( 'product_cat', 'product_tag' ), true ) ) {
			return;
		}

		if ( 'product' !== get_post_type( (int) $object_id ) ) {
			return;
		}

		CategoryTagsQuery::clear_cache_for_product( (int) $object_id );
	}

	/**
	 * Invalidates tag caches when a product is saved.
	 *
	 * @param int $post_id Product ID.
	 */
	public function on_save_product( int $post_id ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		CategoryTagsQuery::clear_cache_for_product( $post_id );
	}

	/**
	 * Invalidates cache for a changed product category (and ancestors).
	 *
	 * @param int $term_id Term ID.
	 */
	public function on_product_cat_changed( int $term_id ): void {
		CategoryTagsQuery::clear_cache_for_category_and_ancestors( $term_id );
	}

	/**
	 * Broad invalidation when a product tag term changes (name/slug/delete).
	 *
	 * Clears caches for all product categories that currently use the tag via products.
	 * Falls back to deleting known category transients for assigned products when possible.
	 *
	 * @param int $term_id Term ID.
	 */
	public function on_product_tag_changed( int $term_id ): void {
		unset( $term_id );

		// Tag edits can affect any category tree; clear all known category tag caches.
		$categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);

		if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
			CategoryTagsQuery::clear_cache( $categories );
		}
	}

	/**
	 * Builds template context for the current product category.
	 *
	 * @return array<string, mixed>
	 */
	public function get_context(): array {
		$term = get_queried_object();
		if ( ! $term instanceof WP_Term || 'product_cat' !== $term->taxonomy ) {
			return array();
		}

		$parent      = $term->parent ? get_term( (int) $term->parent, 'product_cat' ) : null;
		$is_child    = $parent instanceof WP_Term && ! is_wp_error( $parent );
		$nav_parent  = $is_child ? $parent : $term;
		$active_slug = $this->get_requested_tag_slug();

		return array(
			'term'            => $term,
			'parent'          => $is_child ? $parent : null,
			'is_child'        => $is_child,
			'breadcrumbs'     => $this->get_breadcrumbs( $term ),
			'title_primary'   => $is_child ? $parent->name : $term->name,
			'title_secondary' => $is_child ? sprintf(
				/* translators: %s: subcategory name */
				__( 'Til %s', 'tg' ),
				$term->name
			) : '',
			'description'     => term_description( $term->term_id, 'product_cat' ),
			'subcategories'   => $this->get_subcategories( $nav_parent ),
			'see_all_url'     => $is_child ? get_term_link( $parent ) : '',
			'tags'            => CategoryTagsQuery::get_tags_for_category( (int) $term->term_id ),
			'active_tag'      => $active_slug,
			'base_url'        => get_term_link( $term ),
		);
	}

	/**
	 * @param WP_Term $term Current category.
	 * @return array<int, array{label: string, url: string}>
	 */
	private function get_breadcrumbs( WP_Term $term ): array {
		$crumbs = array(
			array(
				'label' => __( 'Forside', 'tg' ),
				'url'   => home_url( '/' ),
			),
		);

		$ancestors = array_reverse( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) );
		foreach ( $ancestors as $ancestor_id ) {
			$ancestor = get_term( (int) $ancestor_id, 'product_cat' );
			if ( ! $ancestor || is_wp_error( $ancestor ) ) {
				continue;
			}

			$link = get_term_link( $ancestor );
			$crumbs[] = array(
				'label' => $ancestor->name,
				'url'   => is_wp_error( $link ) ? '' : $link,
			);
		}

		$crumbs[] = array(
			'label' => $term->name,
			'url'   => '',
		);

		return $crumbs;
	}

	/**
	 * Direct children of the navigation parent term.
	 *
	 * @param WP_Term $parent Parent (or current top-level) term.
	 * @return WP_Term[]
	 */
	private function get_subcategories( WP_Term $parent ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => (int) $parent->term_id,
				'hide_empty' => true,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		return $terms;
	}

	/**
	 * @return string
	 */
	private function get_requested_tag_slug(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET[ self::TAG_QUERY_VAR ] ) ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return sanitize_title( wp_unslash( (string) $_GET[ self::TAG_QUERY_VAR ] ) );
	}

	/**
	 * @return bool
	 */
	private function is_product_category_archive(): bool {
		return function_exists( 'is_product_category' ) && is_product_category();
	}
}
