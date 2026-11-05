<?php
/**
 * Product category subcategory navigation.
 *
 * @package TanteGroen
 *
 * @var array $args
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$subcategories = $args['subcategories'] ?? array();
$term          = $args['term'] ?? null;
$is_child      = ! empty( $args['is_child'] );
$see_all_url   = (string) ( $args['see_all_url'] ?? '' );

if ( empty( $subcategories ) && ( ! $is_child || '' === $see_all_url ) ) {
	return;
}

$current_id = ( $term instanceof WP_Term ) ? (int) $term->term_id : 0;
?>
<nav class="product-category-header__subcategories" aria-label="<?php esc_attr_e( 'Underkategorier', 'tg' ); ?>">
	<ul class="product-category-header__subcategory-list">
		<?php foreach ( $subcategories as $subcategory ) : ?>
			<?php
			if ( ! $subcategory instanceof WP_Term ) {
				continue;
			}
			$link     = get_term_link( $subcategory );
			$is_active = (int) $subcategory->term_id === $current_id;
			if ( is_wp_error( $link ) ) {
				continue;
			}
			?>
			<li class="product-category-header__subcategory-item<?php echo $is_active ? ' is-active' : ''; ?>">
				<a href="<?php echo esc_url( $link ); ?>"<?php echo $is_active ? ' aria-current="page"' : ''; ?>>
					<?php echo esc_html( $subcategory->name ); ?>
				</a>
			</li>
		<?php endforeach; ?>

		<?php if ( $is_child && '' !== $see_all_url && ! is_wp_error( $see_all_url ) ) : ?>
			<li class="product-category-header__subcategory-item product-category-header__subcategory-item--see-all">
				<a href="<?php echo esc_url( $see_all_url ); ?>">
					<?php esc_html_e( 'Se alle', 'tg' ); ?>
				</a>
			</li>
		<?php endif; ?>
	</ul>
</nav>
