<?php
/**
 * Product category title and description.
 *
 * @package TanteGroen
 *
 * @var array $args
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$title_primary   = (string) ( $args['title_primary'] ?? '' );
$title_secondary = (string) ( $args['title_secondary'] ?? '' );
$description     = (string) ( $args['description'] ?? '' );

if ( '' === $title_primary && '' === $description ) {
	return;
}

$plain       = wp_strip_all_tags( $description );
$excerpt_len = 180;
$needs_more  = '' !== $plain && mb_strlen( $plain ) > $excerpt_len;
$excerpt     = $needs_more
	? mb_substr( $plain, 0, $excerpt_len ) . '…'
	: $plain;
?>
<div class="product-category-header__intro-content">
	<?php if ( '' !== $title_primary ) : ?>
		<h1 class="product-category-header__title">
			<span class="product-category-header__title-primary"><?php echo esc_html( $title_primary ); ?></span>
			<?php if ( '' !== $title_secondary ) : ?>
				<span class="product-category-header__title-secondary"><?php echo esc_html( $title_secondary ); ?></span>
			<?php endif; ?>
		</h1>
	<?php endif; ?>

	<?php if ( '' !== $description ) : ?>
		<div class="product-category-header__description">
			<?php if ( $needs_more ) : ?>
				<details class="product-category-header__description-details">
					<summary class="product-category-header__read-more">
						<span class="product-category-header__description-excerpt"><?php echo esc_html( $excerpt ); ?></span>
						<span class="product-category-header__read-more-label"><?php esc_html_e( 'Læs mere', 'tg' ); ?></span>
					</summary>
					<div class="product-category-header__description-full">
						<?php echo wp_kses_post( $description ); ?>
					</div>
				</details>
			<?php else : ?>
				<?php echo wp_kses_post( $description ); ?>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
