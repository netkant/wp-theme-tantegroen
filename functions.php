<?php
/**
 * Tante Groen theme bootstrap.
 *
 * @package TanteGroen
 */

use Urlund\WordPress\Feature;
use Urlund\WordPress\Updater\GitHubThemeRepository;
use TanteGroen\Theme;
use TanteGroen\NavMenus;
use TanteGroen\Header;
use TanteGroen\AdvancedCustomFields;
use TanteGroen\BlockCategories;
use TanteGroen\AllowedBlocks;
use TanteGroen\WooCommerce\ProductCategory\CategoryArchiveHeader;
use TanteGroen\WooCommerce\ProductCategory\CategoryGridHeader;
use TanteGroen\WooCommerce\ProductLoop\ProductCard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Optional read-only GitHub token for private-repo update checks.
 * Prefer defining this in wp-config.php rather than committing secrets.
 */
if ( ! defined( 'TANTEGROEN_GITHUB_TOKEN' ) ) {
	define( 'TANTEGROEN_GITHUB_TOKEN', '' );
}

GitHubThemeRepository::getInstance(
	get_stylesheet(),
	'netkant/wp-theme-tantegroen',
	array(
		'auth' => TANTEGROEN_GITHUB_TOKEN ?: null,
	)
);

Feature::bootstrap(
	array(
		Theme::class,
		NavMenus::class,
		Header::class,
		AdvancedCustomFields::class,
		BlockCategories::class,
		AllowedBlocks::class,
		CategoryArchiveHeader::class,
		CategoryGridHeader::class,
		ProductCard::class,
	)
);
