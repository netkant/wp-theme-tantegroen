# Tante Groen

WordPress theme for Tante Groen ([netkant/wp-theme-tantegroen](https://github.com/netkant/wp-theme-tantegroen)), built on [urlund/wordpress-feature](https://github.com/urlund/wordpress-feature) and [urlund/wordpress-updater](https://github.com/urlund/wordpress-updater).

## Requirements

- PHP 8.0+
- Composer
- WordPress 6.0+

## Setup

1. Place the theme under `wp-content/themes/tantegroen`.
2. Install dependencies:

```bash
composer install
```

3. Activate under **Appearance → Themes**.

## Theme structure

| Path | Role |
|------|------|
| `style.css` | WordPress theme headers |
| `functions.php` | Composer autoload, GitHub updater, `Feature::bootstrap` |
| `src/` | Feature subclasses (Theme, ACF, block categories, allowed blocks) |
| `blocks/` | ACF blocks (auto-registered from subfolders with `block.json`) |
| `theme.json` | Design tokens and global styles |
| `assets/` | Frontend CSS/JS, fonts, ACF JSON paths |

Styles and scripts are enqueued from `Theme.php`. Extend with:

- `tg_enqueue_styles` / `tg_enqueue_scripts`
- Per-item gates: `tg_enqueue_style` / `tg_enqueue_script`

## GitHub updates

Runtime updates use `GitHubThemeRepository` against `netkant/wp-theme-tantegroen`.

For private repos, define a **read-only** token in `wp-config.php` (do not commit it):

```php
define( 'TANTEGROEN_GITHUB_TOKEN', 'ghp_…' );
```

Release tooling reads `extra.wordpress-updater` in `composer.json`. Publishing needs a write-capable `GITHUB_TOKEN` in the environment / `.env` (gitignored).
