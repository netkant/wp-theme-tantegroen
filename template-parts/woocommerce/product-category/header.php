<?php
/**
 * Product category archive header shell.
 *
 * @package TanteGroen
 *
 * @var array $args {
 *     @type WP_Term   $term
 *     @type WP_Term|null $parent
 *     @type bool      $is_child
 *     @type array     $breadcrumbs
 *     @type string    $title_primary
 *     @type string    $title_secondary
 *     @type string    $description
 *     @type WP_Term[] $subcategories
 *     @type string    $see_all_url
 *     @type WP_Term[] $tags
 *     @type string    $active_tag
 *     @type string    $base_url
 * }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$args = wp_parse_args(
	$args ?? array(),
	array(
		'term'            => null,
		'parent'          => null,
		'is_child'        => false,
		'breadcrumbs'     => array(),
		'title_primary'   => '',
		'title_secondary' => '',
		'description'     => '',
		'subcategories'   => array(),
		'see_all_url'     => '',
		'tags'            => array(),
		'active_tag'      => '',
		'base_url'        => '',
	)
);

if ( empty( $args['term'] ) ) {
	return;
}
?>
<header class="product-category-header">
	<?php get_template_part( 'template-parts/woocommerce/product-category/breadcrumbs', null, $args ); ?>

	<div class="product-category-header__inner">
		<div class="product-category-header__intro">
			<?php get_template_part( 'template-parts/woocommerce/product-category/intro', null, $args ); ?>
		</div>

		<div class="product-category-header__nav">
			<?php get_template_part( 'template-parts/woocommerce/product-category/subcategories', null, $args ); ?>
			<?php get_template_part( 'template-parts/woocommerce/product-category/tags', null, $args ); ?>
		</div>
	</div>
</header>
