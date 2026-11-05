<?php
/**
 * Mega menu panel: tabs and extra links on the left, tab panels on the right.
 *
 * @package TanteGroen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mega_menu_id = (int) ( $args['mega_menu_id'] ?? 0 );
$panel_id     = (string) ( $args['panel_id'] ?? 'tg-mega-menu-' . $mega_menu_id );
$tabs         = get_field( 'tabs', $mega_menu_id ) ?: array();
$links        = get_field( 'links', $mega_menu_id ) ?: array();

if ( empty( $tabs ) && empty( $links ) ) {
	return;
}
?>
<div class="mega-menu container" id="<?php echo esc_attr( $panel_id ); ?>" data-mega-menu hidden>
	<div class="mega-menu__inner container__inner">
		<div class="mega-menu__sidebar">
			<?php if ( $tabs ) : ?>
				<ul class="mega-menu__tabs" role="tablist" aria-orientation="vertical">
					<?php foreach ( $tabs as $index => $tab ) : ?>
						<?php
						$is_active  = 0 === $index;
						$tab_link   = $tab['link'] ?: array();
						$tab_attrs  = sprintf(
							'class="mega-menu__tab" id="%1$s-tab-%2$d" role="tab" aria-selected="%3$s" aria-controls="%1$s-panel-%2$d" tabindex="%4$s" data-mega-menu-tab',
							esc_attr( $panel_id ),
							(int) $index,
							$is_active ? 'true' : 'false',
							$is_active ? '0' : '-1'
						);
						?>
						<li class="mega-menu__tabs-item" role="presentation">
							<?php if ( ! empty( $tab_link['url'] ) ) : ?>
								<a <?php echo $tab_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above. ?> href="<?php echo esc_url( $tab_link['url'] ); ?>"<?php echo ! empty( $tab_link['target'] ) ? ' target="' . esc_attr( $tab_link['target'] ) . '" rel="noopener"' : ''; ?>>
									<?php echo esc_html( $tab['label'] ); ?>
								</a>
							<?php else : ?>
								<button <?php echo $tab_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above. ?> type="button">
									<?php echo esc_html( $tab['label'] ); ?>
								</button>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( $links ) : ?>
				<ul class="mega-menu__links">
					<?php foreach ( $links as $row ) : ?>
						<?php
						$link = $row['link'] ?: array();
						if ( empty( $link['url'] ) ) {
							continue;
						}
						?>
						<li class="mega-menu__links-item">
							<a class="mega-menu__link" href="<?php echo esc_url( $link['url'] ); ?>"<?php echo ! empty( $link['target'] ) ? ' target="' . esc_attr( $link['target'] ) . '" rel="noopener"' : ''; ?>>
								<?php echo esc_html( $link['title'] ?: $link['url'] ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<?php foreach ( $tabs as $index => $tab ) : ?>
			<?php
			$list_columns  = array();
			$image_columns = array();

			foreach ( $tab['columns'] ?: array() as $column ) {
				if ( 'image' === ( $column['type'] ?? '' ) ) {
					$image_columns[] = $column;
				} else {
					$list_columns[] = $column;
				}
			}

			$panel_class = 'mega-menu__panel';
			if ( ! $list_columns && $image_columns ) {
				$panel_class .= ' mega-menu__panel--media-only';
			} elseif ( $image_columns ) {
				$panel_class .= ' mega-menu__panel--media-' . min( count( $image_columns ), 3 );
			}
			?>
			<div class="<?php echo esc_attr( $panel_class ); ?>" id="<?php echo esc_attr( $panel_id . '-panel-' . $index ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $panel_id . '-tab-' . $index ); ?>"<?php echo 0 === $index ? '' : ' hidden'; ?>>
				<?php if ( $list_columns ) : ?>
					<div class="mega-menu__lists">
						<?php foreach ( $list_columns as $column ) : ?>
							<?php get_template_part( 'template-parts/header/mega-menu-column', null, array( 'column' => $column ) ); ?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php if ( $image_columns ) : ?>
					<div class="mega-menu__media">
						<?php foreach ( $image_columns as $column ) : ?>
							<?php get_template_part( 'template-parts/header/mega-menu-column', null, array( 'column' => $column ) ); ?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>
