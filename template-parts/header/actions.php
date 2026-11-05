<?php
/**
 * Header actions: search, wishlist, account, cart and mobile menu toggle.
 *
 * @package TanteGroen
 */

use TanteGroen\Header;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wishlist_url = apply_filters( 'tg_header_wishlist_url', '#' );
$account_url  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
$cart_url     = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '';
?>
<ul class="header-actions">
	<li class="header-actions__item header-actions__item--search">
		<button
			class="header-actions__button"
			type="button"
			aria-expanded="false"
			aria-controls="tg-header-search"
			data-search-toggle
		>
			<?php Header::icon( 'search', 'header-actions__icon' ); ?>
			<span class="header-actions__label"><?php esc_html_e( 'Søg', 'tg' ); ?></span>
		</button>
	</li>

	<li class="header-actions__item header-actions__item--wishlist">
		<a class="header-actions__link" href="<?php echo esc_url( $wishlist_url ); ?>">
			<?php Header::icon( 'heart', 'header-actions__icon' ); ?>
			<span class="header-actions__label"><?php esc_html_e( 'Ønskeliste', 'tg' ); ?></span>
		</a>
	</li>

	<li class="header-actions__item header-actions__item--account">
		<a class="header-actions__link" href="<?php echo esc_url( $account_url ); ?>">
			<?php Header::icon( 'user', 'header-actions__icon' ); ?>
			<span class="header-actions__label"><?php esc_html_e( 'Min konto', 'tg' ); ?></span>
		</a>
	</li>

	<?php if ( $cart_url ) : ?>
		<li class="header-actions__item header-actions__item--cart">
			<a class="header-actions__link" href="<?php echo esc_url( $cart_url ); ?>">
				<?php Header::icon( 'bag', 'header-actions__icon' ); ?>
				<span class="header-actions__label"><?php esc_html_e( 'Kurv', 'tg' ); ?></span>
				<?php echo Header::get_cart_count_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in method. ?>
			</a>
		</li>
	<?php endif; ?>

	<li class="header-actions__item header-actions__item--menu">
		<button
			class="header-actions__button"
			type="button"
			aria-expanded="false"
			aria-controls="tg-mobile-drawer"
			data-drawer-open
		>
			<?php Header::icon( 'menu', 'header-actions__icon' ); ?>
			<span class="header-actions__label"><?php esc_html_e( 'Menu', 'tg' ); ?></span>
		</button>
	</li>
</ul>
