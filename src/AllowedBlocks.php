<?php
/**
 * Restrict Gutenberg block inserter for configured roles.
 *
 * @package TanteGroen
 */

namespace TanteGroen;

use Urlund\WordPress\Feature;
use WP_Block_Type_Registry;

/**
 * Whitelists custom ACF blocks plus a curated set of core blocks.
 */
class AllowedBlocks extends Feature {

	/**
	 * Roles this restriction applies to.
	 *
	 * @var string[]
	 */
	const ALLOWED_BLOCKS_ROLES = array(
		'editor',
		'author',
		'contributor',
		'administrator',
	);

	/**
	 * Essential core blocks that should always be available.
	 *
	 * @var string[]
	 */
	const ALLOWED_CORE_BLOCKS = array(
		'core/group',
		'core/columns',
		'core/column',
		'core/paragraph',
		'core/heading',
		'core/gallery',
		'core/image',
		'core/video',
		'core/audio',
		'core/quote',
		'core/list',
		'core/list-item',
		'core/table',
		'core/separator',
		'core/buttons',
		'core/button',
		'core/navigation',
		'generateblocks/element',
	);

	/**
	 * @var array<int|string, mixed>
	 */
	protected $filters = array(
		'allowed_block_types_all' => 'allowed_block_types',
	);

	/**
	 * Collect registered acf/* block names.
	 *
	 * @return string[]
	 */
	public function get_custom_block_names() {
		$names = array();

		foreach ( WP_Block_Type_Registry::get_instance()->get_all_registered() as $name => $block ) {
			if ( strpos( $name, 'acf/' ) === 0 ) {
				$names[] = $name;
			}
		}

		return $names;
	}

	/**
	 * Whether the current user should have blocks restricted.
	 *
	 * @return bool
	 */
	public function should_restrict_blocks() {
		$user = wp_get_current_user();

		if ( ! $user || empty( $user->roles ) ) {
			return false;
		}

		return (bool) array_intersect( (array) $user->roles, self::ALLOWED_BLOCKS_ROLES );
	}

	/**
	 * Filter allowed block types for configured roles.
	 *
	 * @param bool|string[]           $allowed_blocks Current allowed blocks.
	 * @param \WP_Block_Editor_Context $context        Editor context.
	 * @return bool|string[]
	 */
	public function allowed_block_types( $allowed_blocks, $context ) {
		if ( ! $this->should_restrict_blocks() ) {
			return $allowed_blocks;
		}

		$new_allowed_blocks = self::ALLOWED_CORE_BLOCKS;

		if ( is_user_logged_in() && get_current_user_id() === 1 ) {
			$new_allowed_blocks = array_merge(
				$new_allowed_blocks,
				array(
					'core/html',
				)
			);
		}

		return array_values(
			array_unique(
				array_merge(
					$new_allowed_blocks,
					$this->get_custom_block_names()
				)
			)
		);
	}
}
