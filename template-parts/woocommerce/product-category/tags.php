<?php
/**
 * Product tags for the current category tree.
 *
 * @package TanteGroen
 *
 * @var array $args
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tags       = $args['tags'] ?? array();
$active_tag = (string) ( $args['active_tag'] ?? '' );
$base_url   = (string) ( $args['base_url'] ?? '' );

if ( empty( $tags ) || '' === $base_url || is_wp_error( $base_url ) ) {
	return;
}

/**
 * Build category URL with optional product_tag query arg (drops paged).
 *
 * @param string $base Category term link.
 * @param string $tag_slug Tag slug or empty to clear.
 * @return string
 */
$build_tag_url = static function ( string $base, string $tag_slug = '' ): string {
	$url = remove_query_arg( array( 'product_tag', 'paged', 'product-page' ), $base );

	// Preserve other current query args except pagination / our tag.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$query = wp_unslash( $_GET );
	unset( $query['product_tag'], $query['paged'], $query['product-page'] );

	if ( '' !== $tag_slug ) {
		$query['product_tag'] = $tag_slug;
	}

	return ! empty( $query ) ? add_query_arg( $query, $url ) : $url;
};
?>
<nav class="product-category-header__tags" aria-label="<?php esc_attr_e( 'Produkt tags', 'tg' ); ?>">
	<ul class="product-category-header__tag-list">
		<?php foreach ( $tags as $tag ) : ?>
			<?php
			if ( ! $tag instanceof WP_Term ) {
				continue;
			}
			$is_active = ( $active_tag === $tag->slug );
			$href      = $is_active
				? $build_tag_url( $base_url, '' )
				: $build_tag_url( $base_url, $tag->slug );
			?>
			<li class="product-category-header__tag-item<?php echo $is_active ? ' is-active' : ''; ?>">
				<a href="<?php echo esc_url( $href ); ?>"<?php echo $is_active ? ' aria-current="true"' : ''; ?>>
					<?php echo esc_html( $tag->name ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
