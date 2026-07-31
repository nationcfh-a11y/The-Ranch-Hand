<?php
/**
 * Generic page template.
 *
 * @package The_Ranch_Hand
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>
<section class="page-hero">
	<div class="container-rh">
		<?php while ( have_posts() ) : the_post(); ?>
			<h1 class="display-lg"><?php the_title(); ?></h1>
		<?php endwhile; ?>
	</div>
</section>
<section class="section">
	<div class="container-rh">
		<article class="prose">
			<?php
			rewind_posts();
			while ( have_posts() ) :
				the_post();
				the_content();
			endwhile;
			?>
		</article>
	</div>
</section>
<?php get_footer(); ?>
