<?php
/**
 * Fast attribute + product-count lookup for a product category tree.
 *
 * @package TanteGroen
 */

namespace TanteGroen\WooCommerce\ProductCategory;

/**
 * Queries attributes/terms and unfiltered product counts for a category tree.
 */
class CategoryAttributesQuery {

	const ATTRS_TRANSIENT_PREFIX = 'tg_pcat_attrs_';
	const COUNT_TRANSIENT_PREFIX = 'tg_pcat_count_';
	const TRANSIENT_TTL          = 12 * HOUR_IN_SECONDS;

	/**
	 * Attributes used by published products in the category tree.
	 *
	 * Each item:
	 * - name: attribute name without pa_ (e.g. color)
	 * - label: human label
	 * - taxonomy: pa_color
	 * - terms: WP_Term[]
	 *
	 * @param int $category_id product_cat term ID.
	 * @return array<int, array{name: string, label: string, taxonomy: string, terms: \WP_Term[]}>
	 */
	public static function get_attributes_for_category( int $category_id ): array {
		if ( $category_id <= 0 ) {
			return array();
		}

		$cache_key = self::ATTRS_TRANSIENT_PREFIX . $category_id;
		$cached    = get_transient( $cache_key );

		if ( is_array( $cached ) ) {
			return self::hydrate_attributes( $cached );
		}

		$attribute_taxonomies = self::get_attribute_taxonomy_slugs();
		if ( empty( $attribute_taxonomies ) ) {
			set_transient( $cache_key, array(), self::TRANSIENT_TTL );
			return array();
		}

		$term_ids = CategoryTagsQuery::get_category_tree_ids( $category_id );
		if ( empty( $term_ids ) ) {
			set_transient( $cache_key, array(), self::TRANSIENT_TTL );
			return array();
		}

		global $wpdb;

		$cat_placeholders  = implode( ',', array_fill( 0, count( $term_ids ), '%d' ) );
		$tax_placeholders  = implode( ',', array_fill( 0, count( $attribute_taxonomies ), '%s' ) );
		$prepare_args      = array_merge( $attribute_taxonomies, $term_ids );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$sql = $wpdb->prepare(
			"SELECT DISTINCT tt.taxonomy, t.term_id
			FROM {$wpdb->terms} AS t
			INNER JOIN {$wpdb->term_taxonomy} AS tt
				ON t.term_id = tt.term_id
				AND tt.taxonomy IN ({$tax_placeholders})
			INNER JOIN {$wpdb->term_relationships} AS tr_attr
				ON tt.term_taxonomy_id = tr_attr.term_taxonomy_id
			INNER JOIN {$wpdb->posts} AS p
				ON p.ID = tr_attr.object_id
				AND p.post_type = 'product'
				AND p.post_status = 'publish'
			INNER JOIN {$wpdb->term_relationships} AS tr_cat
				ON tr_cat.object_id = p.ID
			INNER JOIN {$wpdb->term_taxonomy} AS tt_cat
				ON tt_cat.term_taxonomy_id = tr_cat.term_taxonomy_id
				AND tt_cat.taxonomy = 'product_cat'
				AND tt_cat.term_id IN ({$cat_placeholders})
			ORDER BY tt.taxonomy ASC, t.name ASC",
			...$prepare_args
		);
		// phpcs:enable

		$rows = $wpdb->get_results( $sql );
		$map  = array();

		if ( ! empty( $rows ) ) {
			foreach ( $rows as $row ) {
				$taxonomy = (string) $row->taxonomy;
				if ( ! isset( $map[ $taxonomy ] ) ) {
					$map[ $taxonomy ] = array();
				}
				$map[ $taxonomy ][] = (int) $row->term_id;
			}
		}

		set_transient( $cache_key, $map, self::TRANSIENT_TTL );

		return self::hydrate_attributes( $map );
	}

	/**
	 * Unfiltered published product count for a category tree (ignores tag/attribute filters).
	 *
	 * @param int $category_id product_cat term ID.
	 * @return int
	 */
	public static function get_product_count_for_category( int $category_id ): int {
		if ( $category_id <= 0 ) {
			return 0;
		}

		$cache_key = self::COUNT_TRANSIENT_PREFIX . $category_id;
		$cached    = get_transient( $cache_key );

		if ( false !== $cached && is_numeric( $cached ) ) {
			return (int) $cached;
		}

		$term_ids = CategoryTagsQuery::get_category_tree_ids( $category_id );
		if ( empty( $term_ids ) ) {
			set_transient( $cache_key, 0, self::TRANSIENT_TTL );
			return 0;
		}

		global $wpdb;

		$placeholders = implode( ',', array_fill( 0, count( $term_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$sql = $wpdb->prepare(
			"SELECT COUNT(DISTINCT p.ID)
			FROM {$wpdb->posts} AS p
			INNER JOIN {$wpdb->term_relationships} AS tr_cat
				ON tr_cat.object_id = p.ID
			INNER JOIN {$wpdb->term_taxonomy} AS tt_cat
				ON tt_cat.term_taxonomy_id = tr_cat.term_taxonomy_id
				AND tt_cat.taxonomy = 'product_cat'
				AND tt_cat.term_id IN ({$placeholders})
			WHERE p.post_type = 'product'
				AND p.post_status = 'publish'",
			...$term_ids
		);
		// phpcs:enable

		$count = (int) $wpdb->get_var( $sql );
		set_transient( $cache_key, $count, self::TRANSIENT_TTL );

		return $count;
	}

	/**
	 * @param int|int[] $category_ids Term ID(s).
	 */
	public static function clear_cache( $category_ids ): void {
		foreach ( (array) $category_ids as $category_id ) {
			$category_id = (int) $category_id;
			if ( $category_id > 0 ) {
				delete_transient( self::ATTRS_TRANSIENT_PREFIX . $category_id );
				delete_transient( self::COUNT_TRANSIENT_PREFIX . $category_id );
			}
		}
	}

	/**
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
	 * Clears attribute/count caches for every product category.
	 */
	public static function clear_all_category_caches(): void {
		$categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);

		if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
			self::clear_cache( $categories );
		}
	}

	/**
	 * @return string[] pa_* taxonomy slugs.
	 */
	private static function get_attribute_taxonomy_slugs(): array {
		if ( ! function_exists( 'wc_get_attribute_taxonomies' ) || ! function_exists( 'wc_attribute_taxonomy_name' ) ) {
			return array();
		}

		$slugs = array();
		foreach ( wc_get_attribute_taxonomies() as $attribute ) {
			$taxonomy = wc_attribute_taxonomy_name( $attribute->attribute_name );
			if ( '' !== $taxonomy ) {
				$slugs[] = $taxonomy;
			}
		}

		return $slugs;
	}

	/**
	 * @param array<string, int[]> $map taxonomy => term IDs.
	 * @return array<int, array{name: string, label: string, taxonomy: string, terms: \WP_Term[]}>
	 */
	private static function hydrate_attributes( array $map ): array {
		if ( empty( $map ) ) {
			return array();
		}

		$attributes = array();

		foreach ( $map as $taxonomy => $term_ids ) {
			$term_ids = array_values( array_filter( array_map( 'intval', (array) $term_ids ) ) );
			if ( empty( $term_ids ) ) {
				continue;
			}

			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'include'    => $term_ids,
					'hide_empty' => false,
					'orderby'    => 'name',
					'order'      => 'ASC',
				)
			);

			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}

			$name  = function_exists( 'wc_attribute_taxonomy_slug' )
				? wc_attribute_taxonomy_slug( $taxonomy )
				: preg_replace( '/^pa_/', '', $taxonomy );
			$label = function_exists( 'wc_attribute_label' )
				? wc_attribute_label( $taxonomy )
				: $name;

			$attributes[] = array(
				'name'     => (string) $name,
				'label'    => (string) $label,
				'taxonomy' => (string) $taxonomy,
				'terms'    => $terms,
			);
		}

		usort(
			$attributes,
			static function ( array $a, array $b ): int {
				return strcasecmp( $a['label'], $b['label'] );
			}
		);

		return $attributes;
	}
}
