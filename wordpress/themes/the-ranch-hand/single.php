<?php
/**
 * Single blog post.
 *
 * @package The_Ranch_Hand
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
while ( have_posts() ) : the_post(); ?>
	<section class="page-hero">
		<div class="container-rh">
			<h1 class="display-lg"><?php the_title(); ?></h1>
			<p class="muted mt-2"><?php echo esc_html( get_the_date() ); ?></p>
		</div>
	</section>
	<section class="section">
		<div class="container-rh">
			<article class="prose">
				<?php if ( has_post_thumbnail() ) : ?>
					<?php the_post_thumbnail( 'large', array( 'style' => 'border-radius:var(--radius-lg);margin-bottom:1.5rem;' ) ); ?>
				<?php endif; ?>
				<?php the_content(); ?>
			</article>
		</div>
	</section>
<?php endwhile;
get_footer();
