<?php
/**
 * Fast product-tag lookup for a product category tree.
 *
 * @package TanteGroen
 */

namespace TanteGroen\WooCommerce\ProductCategory;

/**
 * Queries distinct product_tag terms used by published products in a category tree.
 */
class CategoryTagsQuery {

	const TRANSIENT_PREFIX = 'tg_pcat_tags_';
	const TRANSIENT_TTL    = 12 * HOUR_IN_SECONDS;

	/**
	 * Returns product_tag terms for products in the given category and its descendants.
	 *
	 * @param int $category_id product_cat term ID.
	 * @return \WP_Term[]
	 */
	public static function get_tags_for_category( int $category_id ): array {
		if ( $category_id <= 0 ) {
			return array();
		}

		$cache_key = self::TRANSIENT_PREFIX . $category_id;
		$cached    = get_transient( $cache_key );

		if ( is_array( $cached ) ) {
			return self::hydrate_terms( $cached );
		}

		$term_ids = self::get_category_tree_ids( $category_id );
		if ( empty( $term_ids ) ) {
			set_transient( $cache_key, array(), self::TRANSIENT_TTL );
			return array();
		}

		global $wpdb;

		$placeholders = implode( ',', array_fill( 0, count( $term_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$sql = $wpdb->prepare(
			"SELECT DISTINCT t.term_id
			FROM {$wpdb->terms} AS t
			INNER JOIN {$wpdb->term_taxonomy} AS tt
				ON t.term_id = tt.term_id
				AND tt.taxonomy = 'product_tag'
			INNER JOIN {$wpdb->term_relationships} AS tr_tag
				ON tt.term_taxonomy_id = tr_tag.term_taxonomy_id
			INNER JOIN {$wpdb->posts} AS p
				ON p.ID = tr_tag.object_id
				AND p.post_type = 'product'
				AND p.post_status = 'publish'
			INNER JOIN {$wpdb->term_relationships} AS tr_cat
				ON tr_cat.object_id = p.ID
			INNER JOIN {$wpdb->term_taxonomy} AS tt_cat
				ON tt_cat.term_taxonomy_id = tr_cat.term_taxonomy_id
				AND tt_cat.taxonomy = 'product_cat'
				AND tt_cat.term_id IN ({$placeholders})
			ORDER BY t.name ASC",
			...$term_ids
		);
		// phpcs:enable

		$tag_ids = array_map( 'intval', $wpdb->get_col( $sql ) );
		set_transient( $cache_key, $tag_ids, self::TRANSIENT_TTL );

		return self::hydrate_terms( $tag_ids );
	}

	/**
	 * Current category ID plus all descendant term IDs.
	 *
	 * @param int $category_id product_cat term ID.
	 * @return int[]
	 */
	public static function get_category_tree_ids( int $category_id ): array {
		$ids = array( $category_id );

		$children = get_term_children( $category_id, 'product_cat' );
		if ( ! is_wp_error( $children ) && ! empty( $children ) ) {
			$ids = array_merge( $ids, array_map( 'intval', $children ) );
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Deletes the tags cache for one or more category term IDs.
	 *
	 * @param int|int[] $category_ids Term ID(s).
	 */
	public static function clear_cache( $category_ids ): void {
		foreach ( (array) $category_ids as $category_id ) {
			$category_id = (int) $category_id;
			if ( $category_id > 0 ) {
				delete_transient( self::TRANSIENT_PREFIX . $category_id );
			}
		}
	}

	/**
	 * Clears cache for a category and all of its ancestors (parent trees may include this term).
	 *
	 * @param int $category_id product_cat term ID.
	 */
	public static function clear_cache_for_category_and_ancestors( int $category_id ): void {
		$ids  = array( $category_id );
		$term = get_term( $category_id, 'product_cat' );

		if ( $term && ! is_wp_error( $term ) ) {
			$ancestors = get_ancestors( $category_id, 'product_cat', 'taxonomy' );
			if ( ! empty( $ancestors ) ) {
				$ids = array_merge( $ids, array_map( 'intval', $ancestors ) );
			}
		}

		self::clear_cache( $ids );
	}

	/**
	 * Clears cache for all product_cat terms assigned to a product (and their ancestors).
	 *
	 * @param int $product_id Product post ID.
	 */
	public static function clear_cache_for_product( int $product_id ): void {
		$terms = get_the_terms( $product_id, 'product_cat' );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return;
		}

		foreach ( $terms as $term ) {
			self::clear_cache_for_category_and_ancestors( (int) $term->term_id );
		}
	}

	/**
	 * @param int[] $term_ids Tag term IDs.
	 * @return \WP_Term[]
	 */
	private static function hydrate_terms( array $term_ids ): array {
		if ( empty( $term_ids ) ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'product_tag',
				'include'    => array_map( 'intval', $term_ids ),
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		return $terms;
	}
}
