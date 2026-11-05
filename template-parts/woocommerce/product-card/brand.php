<?php
/**
 * Product card brand label.
 *
 * @package TanteGroen
 *
 * @var array $args
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$brand = $args['brand'] ?? null;
if ( ! $brand instanceof WP_Term ) {
	return;
}
?>
<p class="product-card__brand"><?php echo esc_html( $brand->name ); ?></p>
