<?php
/**
 * Custom Gutenberg block categories.
 *
 * @package TanteGroen
 */

namespace TanteGroen;

use Urlund\WordPress\Feature;

/**
 * Registers theme block categories ahead of core categories.
 */
class BlockCategories extends Feature {

	/**
	 * @var array<int|string, mixed>
	 */
	protected $filters = array(
		'block_categories_all' => 'register_block_categories',
	);

	/**
	 * Theme block categories.
	 *
	 * @return array<int, array{slug: string, title: string, icon: ?string}>
	 */
	public function get_categories() {
		$categories = array(
			array(
				'slug'  => 'tg-content',
				'title' => __( 'Indhold', 'tg' ),
				'icon'  => 'text',
			),
		);

		return apply_filters( 'tg_block_categories', $categories );
	}

	/**
	 * Prepends theme categories so they surface first in the inserter.
	 *
	 * @param array $categories Existing categories.
	 * @return array
	 */
	public function register_block_categories( $categories ) {
		return array_merge( $this->get_categories(), $categories );
	}
}
