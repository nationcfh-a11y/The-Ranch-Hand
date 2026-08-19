<?php
/**
 * Homepage: hero + search, value props, how-it-works, featured caretakers,
 * animal categories, services, testimonials, CTA. Ports client/src/pages/Home.jsx.
 *
 * @package The_Ranch_Hand
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

$trh_dir  = trh_directory_url();
$hero_img = trh_opt( 'trh_hero_image', get_template_directory_uri() . '/assets/img/hero-home.webp' );

$value_props = array(
	array( '🛡️', 'Vetted & experienced', 'Every horse sitter and farm sitter lists real ranch experience, the animals they handle, and verified reviews.' ),
	array( '📸', 'Daily updates', 'Get photos and notes so you know your herd is fed, watered, and happy while you’re away.' ),
	array( '💵', 'Clear, simple pricing', 'Sitters set their own rates. You see the full fee breakdown before you ever confirm.' ),
);
$steps = array(
	array( 1, 'Search', 'Tell us your animals, location, and dates. Browse sitters who fit.' ),
	array( 2, 'Book', 'Pick a service, share feeding & care instructions, and request your dates.' ),
	array( 3, 'Relax', 'Your sitter takes over the chores and keeps you posted with updates.' ),
);
$testimonials = array(
	array( 'Karen M.', 'Bozeman, MT', 32, 5, 'Dale sent photos every day and my horses were calmer when I got back than when I left. The Ranch Hand is a lifesaver.' ),
	array( 'Frank D.', 'Ocala, FL', 53, 5, 'Marisol handled my gelding’s medication schedule perfectly. Finally a sitter who actually knows horses.' ),
	array( 'Aisha B.', 'Lexington, KY', 44, 5, 'Booked turnout for my show horse and got detailed reports each day. Worth every penny.' ),
);
?>

<!-- ---- Hero ---- -->
<section class="hero">
	<div class="hero-bg" style="background-image:url('<?php echo esc_url( $hero_img ); ?>');" aria-hidden="true"></div>
	<div class="hero-grain" aria-hidden="true"></div>
	<div class="container-rh hero-inner">
		<div style="max-width:42rem;">
			<span class="badge badge-hay"><?php echo esc_html( trh_opt( 'trh_hero_eyebrow', '🐴 Equine, livestock & ranch care, done right' ) ); ?></span>
			<h1 class="display-xl mt-4"><?php echo esc_html( trh_opt( 'trh_hero_title', 'Find a horse sitter or farm sitter you can trust.' ) ); ?></h1>
			<p class="lead mt-4" style="max-width:36rem;"><?php echo esc_html( trh_opt( 'trh_hero_subtitle', 'The Ranch Hand connects horse and farm-animal owners with experienced, reviewed sitters. Book horse sitting, farm sitting, and barn sitting services with an equine, livestock, or ranch sitter for overnights, feeding, turnout, mucking, and meds.' ) ); ?></p>
		</div>

		<!-- Search bar -> filters the directory -->
		<form class="searchbar mt-8" action="<?php echo esc_url( $trh_dir ); ?>" method="get" role="search">
			<div class="field">
				<label class="label" for="q">Search</label>
				<input class="input" type="search" id="q" name="q" placeholder="Name or location" />
			</div>
			<div class="field">
				<label class="label" for="animal">Animal</label>
				<select class="select" id="animal" name="animal">
					<option value="">Any animal</option>
					<?php foreach ( trh_animals() as $name => $a ) : ?>
						<option value="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $a['icon'] . ' ' . $name ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="field">
				<label class="label" for="service">Service</label>
				<select class="select" id="service" name="service">
					<option value="">Any service</option>
					<?php foreach ( trh_services() as $key => $s ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $s['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<button class="btn btn-primary" type="submit">Search sitters</button>
		</form>
	</div>
</section>

<!-- ---- Value props ---- -->
<section class="section">
	<div class="container-rh">
		<div class="grid grid-3">
			<?php foreach ( $value_props as $v ) : ?>
				<div class="card">
					<div class="icon-lg" aria-hidden="true"><?php echo esc_html( $v[0] ); ?></div>
					<h3 class="mt-3" style="font-size:1.25rem;"><?php echo esc_html( $v[1] ); ?></h3>
					<p class="muted mt-2"><?php echo esc_html( $v[2] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- ---- How it works ---- -->
<section class="section section-alt">
	<div class="container-rh">
		<h2 class="display-lg text-center">How horse &amp; farm sitting works</h2>
		<p class="muted text-center measure mt-2">Three simple steps from “I need to leave town” to “my animals are in good hands.” Book a horse sitter or farm sitter in minutes.</p>
		<div class="grid grid-3 mt-10">
			<?php foreach ( $steps as $s ) : ?>
				<div class="card text-center">
					<div class="step-num"><?php echo esc_html( $s[0] ); ?></div>
					<h3 class="mt-4" style="font-size:1.25rem;"><?php echo esc_html( $s[1] ); ?></h3>
					<p class="muted mt-2"><?php echo esc_html( $s[2] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="text-center mt-8">
			<a class="btn btn-primary" href="<?php echo esc_url( $trh_dir ); ?>">Browse horse &amp; farm sitters</a>
		</div>
	</div>
</section>

<!-- ---- Featured caretakers ---- -->
<section class="section">
	<div class="container-rh">
		<div class="flex-between">
			<div>
				<h2 class="display-lg">Top-rated horse &amp; farm sitters</h2>
				<p class="muted mt-2">Hand-picked horse sitters, equine sitters, and livestock caretakers with glowing reviews.</p>
			</div>
			<a href="<?php echo esc_url( $trh_dir ); ?>" style="font-weight:700;color:var(--barn);white-space:nowrap;">View all →</a>
		</div>
		<div class="grid grid-3 mt-8">
			<?php
			$featured = new WP_Query(
				array(
					'post_type'      => 'caretaker',
					'posts_per_page' => 6,
					'meta_key'       => 'trh_rating',
					'orderby'        => 'meta_value_num',
					'order'          => 'DESC',
				)
			);
			if ( $featured->have_posts() ) :
				while ( $featured->have_posts() ) :
					$featured->the_post();
					trh_caretaker_card( get_the_ID() );
				endwhile;
				wp_reset_postdata();
			else :
				echo '<p class="muted">Caretakers will appear here once added in <strong>wp-admin → Caretakers</strong>.</p>';
			endif;
			?>
		</div>
	</div>
</section>

<!-- ---- Animal categories ---- -->
<section class="section section-dark">
	<div class="container-rh">
		<h2 class="display-lg text-center" style="color:var(--cream-100);">Care for every animal</h2>
		<p class="text-center measure mt-2" style="color:rgba(247,241,227,.8);">From a single trail horse to a whole mixed farm, find a horse sitter, livestock sitter, or ranch sitter who specializes in your animals.</p>
		<div class="grid grid-6 mt-10">
			<?php foreach ( trh_animals() as $name => $a ) : ?>
				<a class="animal-tile" href="<?php echo esc_url( add_query_arg( 'animal', rawurlencode( $name ), $trh_dir ) ); ?>">
					<div class="emoji" aria-hidden="true"><?php echo esc_html( $a['icon'] ); ?></div>
					<h3><?php echo esc_html( $name ); ?></h3>
					<p><?php echo esc_html( $a['blurb'] ); ?></p>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- ---- Services strip ---- -->
<section class="section">
	<div class="container-rh">
		<h2 class="display-lg text-center">Horse &amp; farm sitting services</h2>
		<p class="muted text-center measure mt-2">From barn sitting and overnight care to feeding, turnout, and meds, pick the horse sitting or farm sitting service you need.</p>
		<div class="grid grid-3 mt-8">
			<?php foreach ( trh_services() as $key => $s ) : ?>
				<a class="card card-hover" href="<?php echo esc_url( add_query_arg( 'service', $key, $trh_dir ) ); ?>">
					<h3 style="font-size:1.125rem;"><?php echo esc_html( $s['label'] ); ?></h3>
					<p class="muted mt-2" style="font-size:.9375rem;"><?php echo esc_html( $s['blurb'] ); ?></p>
					<span class="mt-3" style="display:inline-block;font-weight:700;color:var(--barn);">Find sitters →</span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- ---- Testimonials ---- -->
<section class="section section-alt">
	<div class="container-rh">
		<h2 class="display-lg text-center">Loved by horse &amp; farm owners</h2>
		<div class="grid grid-3 mt-10">
			<?php foreach ( $testimonials as $t ) : ?>
				<figure class="card" style="margin:0;">
					<?php trh_stars( $t[3] ); ?>
					<blockquote class="mt-3" style="margin:0;color:var(--charcoal);">“<?php echo esc_html( $t[4] ); ?>”</blockquote>
					<figcaption class="mt-4" style="display:flex;align-items:center;gap:.75rem;">
						<img src="<?php echo esc_url( 'https://i.pravatar.cc/80?img=' . $t[2] ); ?>" alt="" style="height:2.5rem;width:2.5rem;border-radius:9999px;object-fit:cover;" loading="lazy" />
						<span>
							<span style="display:block;font-weight:700;color:var(--saddle-dark);"><?php echo esc_html( $t[0] ); ?></span>
							<span class="muted" style="font-size:.75rem;"><?php echo esc_html( $t[1] ); ?></span>
						</span>
					</figcaption>
				</figure>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- ---- Photo gallery ---- -->
<?php $trh_gallery = trh_gallery_images(); ?>
<?php if ( ! empty( $trh_gallery ) ) : ?>
<section class="section">
	<div class="container-rh">
		<h2 class="display-lg text-center">Life at the ranch</h2>
		<p class="muted text-center measure mt-2">A few moments from the trails, arenas, and pastures our sitters and owners call home.</p>
		<div class="photo-gallery mt-10">
			<?php foreach ( $trh_gallery as $img ) : ?>
				<figure class="photo-item">
					<img src="<?php echo esc_url( $img['url'] ); ?>" alt="<?php echo esc_attr( $img['alt'] ); ?>" loading="lazy" />
				</figure>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<!-- ---- CTA ---- -->
<section class="section">
	<div class="container-rh">
		<div class="bg-barn" style="border-radius:var(--radius-xl);padding:3rem 2rem;text-align:center;box-shadow:var(--shadow-pop);">
			<h2 class="display-lg" style="color:var(--cream-100);">Know your way around a barn?</h2>
			<p class="measure mt-2" style="color:rgba(247,241,227,.9);">Turn your animal experience into income as a horse sitter, farm sitter, or barn sitter. Set your own rates and schedule as a Ranch Hand caretaker.</p>
			<a class="btn btn-hay mt-6" href="<?php echo esc_url( home_url( '/become-a-caretaker/' ) ); ?>">Become a Caretaker</a>
		</div>
	</div>
</section>

<?php get_footer(); ?>
