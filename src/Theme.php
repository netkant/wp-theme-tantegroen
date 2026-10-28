<?php
/**
 * Main theme Feature.
 *
 * @package TanteGroen
 */

namespace TanteGroen;

use Urlund\WordPress\Feature;

/**
 * Theme setup and asset enqueue.
 */
class Theme extends Feature {

	/**
	 * @var array<int|string, mixed>
	 */
	protected $actions = array(
		'after_setup_theme'  => 'setup',
		'wp_enqueue_scripts' => array(
			'enqueue_styles',
			'enqueue_scripts',
		),
	);

	/**
	 * Theme version from style.css.
	 *
	 * @return string
	 */
	public static function get_version() {
		return wp_get_theme()->get( 'Version' );
	}

	/**
	 * Registers theme supports and textdomain.
	 */
	public function setup() {
		load_theme_textdomain( 'tg', self::get_theme_dir() . 'languages' );

		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support(
			'html5',
			array(
				'search-form',
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
				'style',
				'script',
			)
		);
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'editor-styles' );
		add_theme_support( 'wp-block-styles' );
	}

	/**
	 * Enqueues frontend stylesheets.
	 */
	public function enqueue_styles() {
		$styles = apply_filters(
			'tg_enqueue_styles',
			array(
				'tg-main' => array(
					'relative_path' => '/assets/css/main.css',
				),
			)
		);

		foreach ( $styles as $handle => $style ) {
			$path = self::get_theme_dir() . $style['relative_path'];
			if (
				file_exists( $path )
				&& apply_filters( 'tg_enqueue_style', true, $style )
			) {
				wp_enqueue_style(
					$handle,
					self::get_theme_url() . $style['relative_path'],
					$style['deps'] ?? array(),
					filemtime( $path ),
					$style['media'] ?? 'all'
				);
			}
		}
	}

	/**
	 * Enqueues frontend scripts.
	 */
	public function enqueue_scripts() {
		$scripts = apply_filters(
			'tg_enqueue_scripts',
			array(
				'tg-main' => array(
					'relative_path' => '/assets/js/main.js',
				),
			)
		);

		foreach ( $scripts as $handle => $script ) {
			$path = self::get_theme_dir() . $script['relative_path'];
			if (
				file_exists( $path )
				&& apply_filters( 'tg_enqueue_script', true, $script )
			) {
				wp_enqueue_script(
					$handle,
					self::get_theme_url() . $script['relative_path'],
					$script['deps'] ?? array(),
					filemtime( $path ),
					$script['in_footer'] ?? false
				);
			}
		}
	}
}
