<?php
/**
 * Mobile drawer drill-down for a menu item with a mega menu: tabs and extra
 * links on the second level, column titles on the third. No products.
 *
 * @package TanteGroen
 */

use TanteGroen\Header;
use TanteGroen\MegaMenu;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mega_menu_id = (int) ( $args['mega_menu_id'] ?? 0 );
$level_id     = (string) ( $args['level_id'] ?? 'tg-drawer-level-' . $mega_menu_id );
$title        = (string) ( $args['title'] ?? '' );
$url          = (string) ( $args['url'] ?? '' );
$tabs         = get_field( 'tabs', $mega_menu_id ) ?: array();
$links        = get_field( 'links', $mega_menu_id ) ?: array();

// Rows on the second level. Rows with children open a third level.
$rows = array();

foreach ( $tabs as $tab ) {
	$children = array();
	foreach ( $tab['columns'] ?: array() as $column ) {
		$child = MegaMenu::get_column_link( $column );
		if ( $child ) {
			$children[] = $child;
		}
	}

	$tab_link = $tab['link'] ?: array();
	if ( ! $children && empty( $tab_link['url'] ) ) {
		continue;
	}

	$rows[] = array(
		'title'    => $tab['label'],
		'url'      => $tab_link['url'] ?? '',
		'target'   => $tab_link['target'] ?? '',
		'children' => $children,
	);
}

foreach ( $links as $row ) {
	$link = $row['link'] ?: array();
	if ( empty( $link['url'] ) ) {
		continue;
	}

	$rows[] = array(
		'title'    => $link['title'] ?: $link['url'],
		'url'      => $link['url'],
		'target'   => $link['target'] ?? '',
		'children' => array(),
	);
}

$chevron = Header::get_icon( 'chevron-right', 'drawer-nav__chevron' );
$back    = Header::get_icon( 'chevron-right', 'drawer-level__back-icon' );

if ( ! $rows ) : ?>
	<a class="drawer-nav__link" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a>
	<?php
	return;
endif;
?>
<button class="drawer-nav__link drawer-nav__toggle" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr( $level_id ); ?>" data-drawer-level-open>
	<?php echo esc_html( $title ); ?>
	<?php echo $chevron; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme SVG file. ?>
</button>

<div class="drawer-level" id="<?php echo esc_attr( $level_id ); ?>" role="group" aria-label="<?php echo esc_attr( $title ); ?>" hidden>
	<button class="drawer-level__back" type="button" data-drawer-level-back>
		<?php echo $back; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme SVG file. ?>
		<span class="drawer-level__back-label"><?php echo esc_html( $title ); ?></span>
	</button>

	<ul class="drawer-nav__list">
		<?php foreach ( $rows as $index => $row ) : ?>
			<?php $child_level_id = $level_id . '-' . $index; ?>
			<li class="drawer-nav__item<?php echo $row['children'] ? ' drawer-nav__item--has-children' : ''; ?>">
				<?php if ( $row['children'] ) : ?>
					<button class="drawer-nav__link drawer-nav__toggle" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr( $child_level_id ); ?>" data-drawer-level-open>
						<?php echo esc_html( $row['title'] ); ?>
						<?php echo $chevron; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme SVG file. ?>
					</button>

					<div class="drawer-level" id="<?php echo esc_attr( $child_level_id ); ?>" role="group" aria-label="<?php echo esc_attr( $row['title'] ); ?>" hidden>
						<button class="drawer-level__back" type="button" data-drawer-level-back>
							<?php echo $back; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme SVG file. ?>
							<span class="drawer-level__back-label"><?php echo esc_html( $row['title'] ); ?></span>
						</button>

						<ul class="drawer-nav__list">
							<?php foreach ( $row['children'] as $child ) : ?>
								<li class="drawer-nav__item">
									<a class="drawer-nav__link" href="<?php echo esc_url( $child['url'] ); ?>"><?php echo esc_html( $child['title'] ); ?></a>
								</li>
							<?php endforeach; ?>
						</ul>

						<?php if ( $row['url'] ) : ?>
							<a class="drawer-level__all" href="<?php echo esc_url( $row['url'] ); ?>"<?php echo $row['target'] ? ' target="' . esc_attr( $row['target'] ) . '" rel="noopener"' : ''; ?>>
								<?php
								/* translators: %s: menu item title, lowercase. */
								echo esc_html( sprintf( __( 'Se alle %s', 'tg' ), mb_strtolower( $row['title'] ) ) );
								?>
							</a>
						<?php endif; ?>
					</div>
				<?php else : ?>
					<a class="drawer-nav__link" href="<?php echo esc_url( $row['url'] ); ?>"<?php echo $row['target'] ? ' target="' . esc_attr( $row['target'] ) . '" rel="noopener"' : ''; ?>><?php echo esc_html( $row['title'] ); ?></a>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php if ( $url ) : ?>
		<a class="drawer-level__all" href="<?php echo esc_url( $url ); ?>">
			<?php
			/* translators: %s: menu item title, lowercase. */
			echo esc_html( sprintf( __( 'Se alle %s', 'tg' ), mb_strtolower( $title ) ) );
			?>
		</a>
	<?php endif; ?>
</div>
