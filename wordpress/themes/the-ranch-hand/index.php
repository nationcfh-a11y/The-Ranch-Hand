<?php
/**
 * Fallback template (blog / archive / anything without a more specific template).
 *
 * @package The_Ranch_Hand
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>
<section class="page-hero">
	<div class="container-rh">
		<h1 class="display-lg"><?php echo esc_html( is_home() ? 'From the barn' : wp_get_document_title() ); ?></h1>
	</div>
</section>
<section class="section">
	<div class="container-rh">
		<?php if ( have_posts() ) : ?>
			<div class="grid grid-3">
				<?php while ( have_posts() ) : the_post(); ?>
					<article class="card card-hover">
						<?php if ( has_post_thumbnail() ) : ?>
							<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'medium', array( 'style' => 'border-radius:var(--radius-md);margin-bottom:1rem;' ) ); ?></a>
						<?php endif; ?>
						<h2 style="font-size:1.25rem;"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<p class="muted mt-2"><?php echo esc_html( get_the_excerpt() ); ?></p>
						<a class="mt-3" style="display:inline-block;font-weight:700;color:var(--barn);" href="<?php the_permalink(); ?>">Read more →</a>
					</article>
				<?php endwhile; ?>
			</div>
			<div class="mt-8"><?php the_posts_pagination( array( 'mid_size' => 1, 'type' => 'list' ) ); ?></div>
		<?php else : ?>
			<div class="prose"><p class="muted">Nothing here yet.</p></div>
		<?php endif; ?>
	</div>
</section>
<?php get_footer(); ?>
