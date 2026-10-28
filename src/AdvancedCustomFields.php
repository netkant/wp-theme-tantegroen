<?php
/**
 * ACF blocks and JSON loading.
 *
 * @package TanteGroen
 */

namespace TanteGroen;

use DirectoryIterator;
use Urlund\WordPress\Feature;

/**
 * Registers ACF blocks from theme-root blocks/ and loads ACF JSON paths.
 */
class AdvancedCustomFields extends Feature {

	/**
	 * @var array<int|string, mixed>
	 */
	protected $actions = array(
		'init'                        => array(
			'register_block_types',
			'register_block_styles',
		),
		'enqueue_block_editor_assets' => 'enqueue_block_editor_assets',
	);

	/**
	 * @var array<int|string, mixed>
	 */
	protected $filters = array(
		'acf/json/load_paths' => 'add_acf_json_load_paths',
	);

	/**
	 * Block directories to scan.
	 *
	 * @return string[]
	 */
	public function get_block_dirs() {
		$block_dirs = array(
			get_template_directory() . '/blocks',
		);

		if ( get_stylesheet_directory() !== get_template_directory() ) {
			$block_dirs[] = get_stylesheet_directory() . '/blocks';
		}

		$block_dirs = apply_filters( 'tg_get_registered_block_dirs', $block_dirs );

		if ( empty( $block_dirs ) || ! is_array( $block_dirs ) ) {
			return array();
		}

		return $block_dirs;
	}

	/**
	 * Map of block slug => absolute path.
	 *
	 * @return array<string, string>
	 */
	public function get_registered_blocks() {
		$blocks = array();

		foreach ( $this->get_block_dirs() as $block_dir ) {
			if ( ! is_dir( $block_dir ) ) {
				continue;
			}

			$block_paths = new DirectoryIterator( $block_dir );
			foreach ( $block_paths as $block_path ) {
				if ( $block_path->isDot() || ! $block_path->isDir() ) {
					continue;
				}

				$blocks[ basename( $block_path->getFilename() ) ] = $block_path->getRealPath();
			}
		}

		return apply_filters( 'tg_get_registered_blocks', $blocks );
	}

	/**
	 * Registers block types and optional local field groups.
	 */
	public function register_block_types() {
		foreach ( $this->get_registered_blocks() as $block_path ) {
			if ( file_exists( $block_path . '/block.json' ) ) {
				register_block_type( $block_path );
			}

			foreach ( array( 'fields.json', 'field-groups.json' ) as $filename ) {
				$path = $block_path . '/' . $filename;
				if ( ! file_exists( $path ) ) {
					continue;
				}

				$fields = json_decode( file_get_contents( $path ), true );
				if ( empty( $fields ) || ! is_array( $fields ) ) {
					continue;
				}

				foreach ( $fields as $field ) {
					if ( function_exists( 'acf_add_local_field_group' ) ) {
						acf_add_local_field_group( $field );
					}
				}
			}
		}
	}

	/**
	 * Registers custom block styles.
	 */
	public function register_block_styles() {
		register_block_style(
			'core/group',
			array(
				'name'  => '3-4-width',
				'label' => '3/4 bredde',
			)
		);
	}

	/**
	 * Enqueues optional per-block preview styles in the editor.
	 */
	public function enqueue_block_editor_assets() {
		foreach ( $this->get_registered_blocks() as $slug => $block_path ) {
			$path = $block_path . '/preview.css';
			if ( ! file_exists( $path ) ) {
				continue;
			}

			wp_enqueue_style(
				'preview-styles-' . $slug,
				self::get_theme_url() . 'blocks/' . $slug . '/preview.css',
				array(),
				filemtime( $path )
			);
		}
	}

	/**
	 * Adds ACF JSON load paths for field groups and post types.
	 *
	 * @param array $paths Existing load paths.
	 * @return array
	 */
	public function add_acf_json_load_paths( $paths ) {
		$paths[] = self::get_theme_dir() . 'assets/acf/field-groups';
		$paths[] = self::get_theme_dir() . 'assets/acf/post-types';

		return $paths;
	}
}
