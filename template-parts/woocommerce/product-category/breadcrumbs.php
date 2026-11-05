<?php
/**
 * Product category breadcrumbs.
 *
 * @package TanteGroen
 *
 * @var array $args
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$crumbs = $args['breadcrumbs'] ?? array();
if ( empty( $crumbs ) || ! is_array( $crumbs ) ) {
	return;
}
?>
<nav class="product-category-header__breadcrumbs" aria-label="<?php esc_attr_e( 'Brødkrummer', 'tg' ); ?>">
	<ol class="product-category-header__breadcrumb-list">
		<?php foreach ( $crumbs as $index => $crumb ) : ?>
			<?php
			$label = isset( $crumb['label'] ) ? (string) $crumb['label'] : '';
			$url   = isset( $crumb['url'] ) ? (string) $crumb['url'] : '';
			$is_last = ( $index === array_key_last( $crumbs ) );
			if ( '' === $label ) {
				continue;
			}
			?>
			<li class="product-category-header__breadcrumb-item">
				<?php if ( ! $is_last && '' !== $url ) : ?>
					<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php else : ?>
					<span aria-current="page"><?php echo esc_html( $label ); ?></span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
</nav>
