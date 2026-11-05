<?php
/**
 * Header product search form.
 *
 * @package TanteGroen
 *
 * @var array $args {
 *     @type string $modifier BEM modifier, e.g. 'navbar' or 'drawer'.
 * }
 */

use TanteGroen\Header;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$modifier = sanitize_html_class( (string) ( $args['modifier'] ?? '' ) );
$class    = 'header-search' . ( $modifier ? ' header-search--' . $modifier : '' );
$input_id = 'tg-search-' . ( $modifier ?: 'default' );
?>
<form class="<?php echo esc_attr( $class ); ?>" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="header-search__label" for="<?php echo esc_attr( $input_id ); ?>"><?php esc_html_e( 'Søg', 'tg' ); ?></label>
	<?php Header::icon( 'search', 'header-search__icon' ); ?>
	<input
		class="header-search__input"
		type="search"
		id="<?php echo esc_attr( $input_id ); ?>"
		name="s"
		value="<?php echo esc_attr( get_search_query() ); ?>"
		placeholder="<?php esc_attr_e( 'Søg', 'tg' ); ?>"
	/>
	<?php if ( class_exists( 'WooCommerce' ) ) : ?>
		<input type="hidden" name="post_type" value="product" />
	<?php endif; ?>
</form>
