<?php
/**
 * Product category grid header shell.
 *
 * @package TanteGroen
 *
 * @var array $args
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$args = wp_parse_args(
	$args ?? array(),
	array(
		'term'            => null,
		'base_url'        => '',
		'shown_count'     => 0,
		'total_count'     => 0,
		'attributes'      => array(),
		'active_filters'  => array(),
		'preserved_query' => array(),
	)
);

if ( empty( $args['term'] ) ) {
	return;
}
?>
<div class="product-category-grid-header">
	<div class="product-category-grid-header__counter">
		<?php get_template_part( 'template-parts/woocommerce/product-category/grid-counter', null, $args ); ?>
	</div>

	<div class="product-category-grid-header__toolbar">
		<?php get_template_part( 'template-parts/woocommerce/product-category/grid-toolbar', null, $args ); ?>
	</div>
</div>
