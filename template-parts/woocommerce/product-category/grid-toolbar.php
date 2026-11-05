<?php
/**
 * Product category grid toolbar: attribute filters + sorting.
 *
 * @package TanteGroen
 *
 * @var array $args
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$base_url        = (string) ( $args['base_url'] ?? '' );
$attributes      = $args['attributes'] ?? array();
$active_filters  = $args['active_filters'] ?? array();
$preserved_query = $args['preserved_query'] ?? array();

if ( '' === $base_url || is_wp_error( $base_url ) ) {
	return;
}
?>
<div class="product-category-grid-header__attributes">
	<?php if ( ! empty( $attributes ) ) : ?>
		<form
			class="product-category-grid-header__filters-form"
			method="get"
			action="<?php echo esc_url( $base_url ); ?>"
			onsubmit="Array.prototype.forEach.call(this.elements, function (el) { if (el.tagName === 'SELECT' && !el.value) { el.disabled = true; } });"
		>
			<?php foreach ( $preserved_query as $key => $value ) : ?>
				<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" />
			<?php endforeach; ?>

			<?php foreach ( $attributes as $attribute ) : ?>
				<?php
				$name  = isset( $attribute['name'] ) ? (string) $attribute['name'] : '';
				$label = isset( $attribute['label'] ) ? (string) $attribute['label'] : $name;
				$terms = $attribute['terms'] ?? array();
				if ( '' === $name || empty( $terms ) ) {
					continue;
				}
				$field_id    = 'tg-filter-' . $name;
				$field_name  = 'filter_' . $name;
				$active_slug = isset( $active_filters[ $name ] ) ? (string) $active_filters[ $name ] : '';
				?>
				<div class="product-category-grid-header__attribute">
					<label class="product-category-grid-header__attribute-label" for="<?php echo esc_attr( $field_id ); ?>">
						<?php echo esc_html( strtoupper( $label ) . ' +' ); ?>
					</label>
					<select
						class="product-category-grid-header__attribute-select"
						id="<?php echo esc_attr( $field_id ); ?>"
						name="<?php echo esc_attr( $field_name ); ?>"
						onchange="this.form.submit()"
					>
						<option value=""><?php esc_html_e( 'Alle', 'tg' ); ?></option>
						<?php foreach ( $terms as $term ) : ?>
							<?php
							if ( ! $term instanceof WP_Term ) {
								continue;
							}
							?>
							<option
								value="<?php echo esc_attr( $term->slug ); ?>"
								<?php selected( $active_slug, $term->slug ); ?>
							>
								<?php echo esc_html( $term->name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php endforeach; ?>
		</form>
	<?php endif; ?>
</div>

<div class="product-category-grid-header__sorting">
	<?php
	if ( function_exists( 'woocommerce_catalog_ordering' ) ) {
		woocommerce_catalog_ordering();
	}
	?>
</div>
