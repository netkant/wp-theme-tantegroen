<?php
/**
 * Mega menu column: a product list for a category, attribute or tag, or an image card.
 *
 * @package TanteGroen
 */

use TanteGroen\MegaMenu;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$column = $args['column'] ?? array();
$type   = $column['type'] ?? '';

if ( 'image' === $type ) :
	$image = $column['image'] ?: array();
	$link  = $column['link'] ?: array();
	$text  = $column['text'] ?? '';

	if ( empty( $image['ID'] ) ) {
		return;
	}

	$tag   = ! empty( $link['url'] ) ? 'a' : 'div';
	$attrs = '';
	if ( 'a' === $tag ) {
		$attrs = ' href="' . esc_url( $link['url'] ) . '"';
		if ( ! empty( $link['target'] ) ) {
			$attrs .= ' target="' . esc_attr( $link['target'] ) . '" rel="noopener"';
		}
	}
	?>
	<<?php echo $tag . $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above. ?> class="mega-menu-card">
		<span class="mega-menu-card__media">
			<?php
			echo wp_get_attachment_image(
				(int) $image['ID'],
				'medium_large',
				false,
				array(
					'class'   => 'mega-menu-card__image',
					'loading' => 'lazy',
					'sizes'   => '(min-width: 64rem) 20vw, 100vw',
				)
			);
			?>
		</span>
		<?php if ( $text || ! empty( $link['title'] ) ) : ?>
			<span class="mega-menu-card__footer">
				<span class="mega-menu-card__text"><?php echo esc_html( $text ); ?></span>
				<?php if ( ! empty( $link['title'] ) ) : ?>
					<span class="mega-menu-card__more"><?php echo esc_html( $link['title'] ); ?></span>
				<?php endif; ?>
			</span>
		<?php endif; ?>
	</<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed tag name. ?>>
	<?php
	return;
endif;

$term = MegaMenu::get_column_term( $column );
if ( ! $term ) {
	return;
}

$title    = ( $column['title'] ?? '' ) ?: $term->name;
$url      = MegaMenu::get_term_archive_url( $term );
$products = MegaMenu::get_column_products( $term, (int) ( $column['limit'] ?? 0 ) ?: MegaMenu::DEFAULT_LIMIT );
?>
<div class="mega-menu-column">
	<a class="mega-menu-column__title" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a>

	<?php if ( $products ) : ?>
		<ul class="mega-menu-column__list">
			<?php foreach ( $products as $product_id ) : ?>
				<li class="mega-menu-column__item">
					<a class="mega-menu-column__link" href="<?php echo esc_url( get_permalink( $product_id ) ); ?>"><?php echo esc_html( get_the_title( $product_id ) ); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<a class="mega-menu-column__more" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Se alle', 'tg' ); ?></a>
</div>
