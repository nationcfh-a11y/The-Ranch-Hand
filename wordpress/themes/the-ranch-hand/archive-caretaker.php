<?php
/**
 * Caretaker directory — browse + filter (animal, service, search, sort).
 * Ports client/src/pages/Search.jsx.
 *
 * @package The_Ranch_Hand
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

$trh_dir = trh_directory_url();

// Read filters from the query string.
$q         = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
$animal    = isset( $_GET['animal'] ) ? sanitize_text_field( wp_unslash( $_GET['animal'] ) ) : '';
$service   = isset( $_GET['service'] ) ? sanitize_text_field( wp_unslash( $_GET['service'] ) ) : '';
$sort      = isset( $_GET['sort'] ) ? sanitize_key( $_GET['sort'] ) : 'rating';

// Build the query.
$args = array(
	'post_type'      => 'caretaker',
	'posts_per_page' => 24,
	'paged'          => max( 1, get_query_var( 'paged' ), isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1 ),
);
if ( $q ) {
	$args['s'] = $q;
}
$tax_query = array();
if ( $animal ) {
	$tax_query[] = array( 'taxonomy' => 'animal', 'field' => 'name', 'terms' => $animal );
}
if ( $service ) {
	$tax_query[] = array( 'taxonomy' => 'service', 'field' => 'name', 'terms' => trh_service_label( $service ) );
}
if ( $tax_query ) {
	$args['tax_query'] = $tax_query;
}
switch ( $sort ) {
	case 'experience':
		$args['meta_key'] = 'trh_experience_years';
		$args['orderby']  = 'meta_value_num';
		$args['order']    = 'DESC';
		break;
	case 'name':
		$args['orderby'] = 'title';
		$args['order']   = 'ASC';
		break;
	case 'rating':
	default:
		$args['meta_key'] = 'trh_rating';
		$args['orderby']  = 'meta_value_num';
		$args['order']    = 'DESC';
		break;
}
$results = new WP_Query( $args );

// Helper to keep a filter value when re-rendering the form.
$sel = function ( $a, $b ) { echo selected( $a, $b, false ); };
?>

<section class="page-hero">
	<div class="container-rh">
		<h1 class="display-lg">Find a horse &amp; farm sitter</h1>
		<p class="muted mt-2">Browse experienced, reviewed caretakers. Filter by animal, service, and more.</p>
	</div>
</section>

<section class="section">
	<div class="container-rh directory">
		<!-- Filters -->
		<aside class="filters">
			<form class="card" action="<?php echo esc_url( $trh_dir ); ?>" method="get">
				<div class="field">
					<label class="label" for="q">Search</label>
					<input class="input" type="search" id="q" name="q" value="<?php echo esc_attr( $q ); ?>" placeholder="Name or location" />
				</div>
				<div class="field">
					<label class="label" for="animal">Animal</label>
					<select class="select" id="animal" name="animal">
						<option value="">Any animal</option>
						<?php foreach ( trh_animals() as $name => $a ) : ?>
							<option value="<?php echo esc_attr( $name ); ?>" <?php $sel( $animal, $name ); ?>><?php echo esc_html( $a['icon'] . ' ' . $name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="field">
					<label class="label" for="service">Service</label>
					<select class="select" id="service" name="service">
						<option value="">Any service</option>
						<?php foreach ( trh_services() as $key => $s ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php $sel( $service, $key ); ?>><?php echo esc_html( $s['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="field">
					<label class="label" for="sort">Sort by</label>
					<select class="select" id="sort" name="sort">
						<option value="rating" <?php $sel( $sort, 'rating' ); ?>>Top rated</option>
						<option value="experience" <?php $sel( $sort, 'experience' ); ?>>Most experienced</option>
						<option value="name" <?php $sel( $sort, 'name' ); ?>>Name (A–Z)</option>
					</select>
				</div>
				<button class="btn btn-primary" type="submit" style="width:100%;">Apply filters</button>
				<?php if ( $q || $animal || $service ) : ?>
					<a class="btn btn-ghost btn-sm" href="<?php echo esc_url( $trh_dir ); ?>" style="width:100%;margin-top:.5rem;">Clear all</a>
				<?php endif; ?>
			</form>
		</aside>

		<!-- Results -->
		<div>
			<p class="muted" style="margin-bottom:1rem;">
				<strong><?php echo esc_html( $results->found_posts ); ?></strong>
				<?php echo esc_html( _n( 'sitter', 'sitters', $results->found_posts, 'the-ranch-hand' ) ); ?> found
			</p>

			<?php if ( $results->have_posts() ) : ?>
				<div class="grid grid-3">
					<?php
					while ( $results->have_posts() ) :
						$results->the_post();
						trh_caretaker_card( get_the_ID() );
					endwhile;
					?>
				</div>

				<?php
				$big = 999999999;
				$pagination = paginate_links(
					array(
						'base'      => str_replace( $big, '%#%', esc_url( add_query_arg( 'paged', $big ) ) ),
						'format'    => '',
						'current'   => max( 1, $args['paged'] ),
						'total'     => $results->max_num_pages,
						'prev_text' => '← Prev',
						'next_text' => 'Next →',
						'type'      => 'list',
					)
				);
				if ( $pagination ) {
					echo '<div class="mt-8">' . wp_kses_post( $pagination ) . '</div>';
				}
				?>
			<?php else : ?>
				<div class="card text-center">
					<p style="font-size:1.125rem;font-weight:700;color:var(--saddle-dark);">No sitters match those filters.</p>
					<p class="muted mt-2">Try widening your search — clear a filter or two.</p>
					<a class="btn btn-secondary mt-4" href="<?php echo esc_url( $trh_dir ); ?>">Clear filters</a>
				</div>
			<?php endif; ?>
			<?php wp_reset_postdata(); ?>
		</div>
	</div>
</section>

<?php get_footer(); ?>
