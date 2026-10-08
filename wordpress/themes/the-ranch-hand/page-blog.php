<?php
/**
 * Blog (slug "blog"). Lists published posts with its own query, so it works
 * whether or not Settings → Reading names a posts page.
 *
 * @package The_Ranch_Hand
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

$trh_paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
$trh_posts = new WP_Query(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 9,
		'paged'               => $trh_paged,
		'ignore_sticky_posts' => false,
	)
);
?>
<section class="page-hero">
	<div class="container-rh">
		<h1 class="display-lg">From the barn</h1>
		<p class="muted mt-2" style="max-width:40rem;">Horse and farm care tips, advice for Ranches and Hands, and news from The Ranch Hand.</p>
	</div>
</section>
<section class="section">
	<div class="container-rh">
		<?php if ( $trh_posts->have_posts() ) : ?>
			<div class="grid grid-3">
				<?php while ( $trh_posts->have_posts() ) : $trh_posts->the_post(); ?>
					<article class="card card-hover">
						<?php if ( has_post_thumbnail() ) : ?>
							<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'medium_large', array( 'style' => 'border-radius:var(--radius-md);margin-bottom:1rem;width:100%;height:auto;' ) ); ?></a>
						<?php endif; ?>
						<p class="muted" style="font-size:.8125rem;"><?php echo esc_html( get_the_date() ); ?></p>
						<h2 class="mt-2" style="font-size:1.25rem;"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<p class="muted mt-2"><?php echo esc_html( get_the_excerpt() ); ?></p>
						<a class="mt-3" style="display:inline-block;font-weight:700;color:var(--barn);" href="<?php the_permalink(); ?>">Read more →</a>
					</article>
				<?php endwhile; ?>
			</div>
			<?php if ( $trh_posts->max_num_pages > 1 ) : ?>
				<nav class="mt-8 pagination" aria-label="Blog pages">
					<?php
					echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput -- core-escaped markup.
						array(
							'total'     => $trh_posts->max_num_pages,
							'current'   => $trh_paged,
							'mid_size'  => 1,
							'type'      => 'list',
							'prev_text' => '← Newer',
							'next_text' => 'Older →',
						)
					);
					?>
				</nav>
			<?php endif; ?>
			<?php wp_reset_postdata(); ?>
		<?php else : ?>
			<div class="card text-center" style="max-width:36rem;margin:0 auto;">
				<h2 style="font-size:1.375rem;">First posts are on the way</h2>
				<p class="muted mt-2">We are writing up care guides, checklists, and stories from the barn. Check back soon.</p>
				<p class="mt-4">
					<a class="btn btn-primary btn-sm" href="<?php echo esc_url( trh_directory_url() ); ?>">Browse Ranch Hands</a>
					<a class="btn btn-secondary btn-sm" href="<?php echo esc_url( trh_page_url( 'our-mission' ) ); ?>">Read our mission</a>
				</p>
			</div>
		<?php endif; ?>
	</div>
</section>
<?php get_footer(); ?>
