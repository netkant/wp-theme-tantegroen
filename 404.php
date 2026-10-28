<?php
/**
 * The template for displaying 404 pages.
 *
 * @package TanteGroen
 */

get_header();
?>

<main id="main" class="site-main">
	<article class="error-404">
		<header class="entry-header">
			<h1 class="entry-title"><?php esc_html_e( 'Page not found', 'tg' ); ?></h1>
		</header>

		<div class="entry-content">
			<p><?php esc_html_e( 'Nothing was found at this location.', 'tg' ); ?></p>
		</div>
	</article>
</main>

<?php
get_footer();
