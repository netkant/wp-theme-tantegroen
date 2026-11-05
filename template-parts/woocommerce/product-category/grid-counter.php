<?php
/**
 * Product category grid counter.
 *
 * @package TanteGroen
 *
 * @var array $args
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$shown = isset( $args['shown_count'] ) ? (int) $args['shown_count'] : 0;
$total = isset( $args['total_count'] ) ? (int) $args['total_count'] : 0;
?>
<p class="product-category-grid-header__counter-text">
	<?php
	printf(
		/* translators: 1: products shown on this page, 2: total products in category */
		esc_html__( 'Viser %1$d af %2$d opskrifter', 'tg' ),
		$shown,
		$total
	);
	?>
</p>
