<?php
/**
 * Header top bar: USP text and secondary menu.
 *
 * @package TanteGroen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="top-bar container">
	<div class="top-bar__inner container__inner">
		<p class="top-bar__text"><?php esc_html_e( 'Levering fra 39 DKK', 'tg' ); ?></p>

		<?php if ( has_nav_menu( 'secondary' ) ) : ?>
			<nav class="top-bar__nav top-bar-nav" aria-label="<?php esc_attr_e( 'Topmenu', 'tg' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'secondary',
						'container'      => false,
						'menu_class'     => 'top-bar-nav__list',
						'depth'          => 1,
						'fallback_cb'    => false,
						'bem_block'      => 'top-bar-nav',
					)
				);
				?>
			</nav>
		<?php endif; ?>
	</div>
</div>
