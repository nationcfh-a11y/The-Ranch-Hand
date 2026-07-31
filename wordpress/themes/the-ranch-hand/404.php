<?php
/**
 * 404 — page not found.
 *
 * @package The_Ranch_Hand
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>
<section class="section">
	<div class="container-rh text-center" style="padding:5rem 0;">
		<div style="font-size:4rem;" aria-hidden="true">🐴</div>
		<h1 class="display-lg mt-4">This trail doesn't lead anywhere.</h1>
		<p class="muted measure mt-2">The page you're looking for has wandered off. Let's get you back to the barn.</p>
		<div class="mt-6" style="display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap;">
			<a class="btn btn-primary" href="<?php echo esc_url( home_url( '/' ) ); ?>">Back home</a>
			<a class="btn btn-secondary" href="<?php echo esc_url( trh_directory_url() ); ?>">Find a sitter</a>
		</div>
	</div>
</section>
<?php get_footer(); ?>
