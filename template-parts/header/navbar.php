<?php
/**
 * Header navbar: logo, main menu, actions and search panel.
 *
 * @package TanteGroen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$logo_id = (int) get_theme_mod( 'custom_logo' );
?>
<div class="navbar">
	<div class="navbar__bar container">
		<div class="navbar__inner container__inner">
			<div class="navbar__logo">
				<a class="navbar__logo-link" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
					<?php if ( $logo_id ) : ?>
						<?php
						echo wp_get_attachment_image(
							$logo_id,
							'full',
							false,
							array(
								'class' => 'navbar__logo-image',
								'alt'   => get_bloginfo( 'name' ),
							)
						);
						?>
					<?php else : ?>
						<span class="navbar__logo-text"><?php bloginfo( 'name' ); ?></span>
					<?php endif; ?>
				</a>
			</div>

			<?php if ( has_nav_menu( 'primary' ) ) : ?>
				<nav class="navbar__nav main-nav" aria-label="<?php esc_attr_e( 'Hovedmenu', 'tg' ); ?>">
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'primary',
							'container'      => false,
							'menu_class'     => 'main-nav__list',
							'depth'          => 1,
							'fallback_cb'    => false,
							'bem_block'      => 'main-nav',
						)
					);
					?>
				</nav>
			<?php endif; ?>

			<div class="navbar__actions">
				<?php get_template_part( 'template-parts/header/actions' ); ?>
			</div>
		</div>
	</div>

	<div class="navbar__search container" id="tg-header-search" hidden>
		<div class="container__inner">
			<?php get_template_part( 'template-parts/header/search-form', null, array( 'modifier' => 'navbar' ) ); ?>
		</div>
	</div>
</div>
