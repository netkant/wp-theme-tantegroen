<?php
/**
 * Mobile drawer: search, main menu and help links.
 *
 * @package TanteGroen
 */

use TanteGroen\Header;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$help_links = apply_filters(
	'tg_header_help_links',
	array(
		array(
			'label' => __( 'Kontakt os', 'tg' ),
			'url'   => '#',
			'icon'  => 'mail',
		),
		array(
			'label' => __( 'Besøg butikken', 'tg' ),
			'url'   => '#',
			'icon'  => 'store',
		),
		array(
			'label' => __( 'Se vores FAQ', 'tg' ),
			'url'   => '#',
			'icon'  => 'help',
		),
	)
);
?>
<div class="mobile-drawer" id="tg-mobile-drawer" hidden>
	<div class="mobile-drawer__backdrop" data-drawer-close></div>

	<div class="mobile-drawer__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Menu', 'tg' ); ?>">
		<button class="mobile-drawer__close" type="button" data-drawer-close>
			<?php Header::icon( 'close', 'mobile-drawer__close-icon' ); ?>
			<span class="mobile-drawer__close-label"><?php esc_html_e( 'Luk menu', 'tg' ); ?></span>
		</button>

		<div class="mobile-drawer__search">
			<?php get_template_part( 'template-parts/header/search-form', null, array( 'modifier' => 'drawer' ) ); ?>
		</div>

		<?php if ( has_nav_menu( 'primary' ) ) : ?>
			<nav class="mobile-drawer__nav drawer-nav" aria-label="<?php esc_attr_e( 'Hovedmenu', 'tg' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'drawer-nav__list',
						'depth'          => 1,
						'fallback_cb'    => false,
						'bem_block'      => 'drawer-nav',
						'link_after'     => Header::get_icon( 'chevron-right', 'drawer-nav__chevron' ),
					)
				);
				?>
			</nav>
		<?php endif; ?>

		<?php if ( ! empty( $help_links ) ) : ?>
			<div class="mobile-drawer__help drawer-help">
				<p class="drawer-help__title"><?php esc_html_e( 'Har du brug for hjælp?', 'tg' ); ?></p>
				<ul class="drawer-help__list">
					<?php foreach ( $help_links as $help_link ) : ?>
						<li class="drawer-help__item">
							<a class="drawer-help__link" href="<?php echo esc_url( $help_link['url'] ); ?>">
								<?php Header::icon( $help_link['icon'] ?? '', 'drawer-help__icon' ); ?>
								<span class="drawer-help__label"><?php echo esc_html( $help_link['label'] ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
	</div>
</div>
