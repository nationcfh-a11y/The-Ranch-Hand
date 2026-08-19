<?php
/**
 * Template Name: Become a Caretaker
 *
 * Recruiting landing + application form. Auto-applied to the page with slug
 * "become-a-caretaker" (created on theme activation), and also selectable as a
 * page template. Ports client/src/pages/BecomeCaretaker.jsx.
 *
 * @package The_Ranch_Hand
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

$perks = array(
	array( '💵', 'Set your own rates', 'You decide what each service costs. See exactly what you keep on every booking.' ),
	array( '📅', 'Work on your schedule', 'Accept the bookings that fit your week. Overnights, drop-ins, or full-farm care.' ),
	array( '🤝', 'Owners who trust you', 'Your experience, animals, and reviews are front and center so the right owners find you.' ),
);
$steps = array(
	array( 1, 'Tell us about you', 'Your experience, the animals you handle, and where you work.' ),
	array( 2, 'List your services', 'Pick the services you offer and set a rate for each.' ),
	array( 3, 'Start getting requests', 'Owners near you send booking requests. You accept the ones you want.' ),
);
?>

<section class="hero">
	<div class="hero-bg" style="background-image:url('<?php echo esc_url( get_template_directory_uri() . '/assets/img/hero-become-caretaker.webp' ); ?>');" aria-hidden="true"></div>
	<div class="hero-grain" aria-hidden="true"></div>
	<div class="container-rh hero-inner">
		<div style="max-width:42rem;">
			<span class="badge badge-hay">🤠 Become a Ranch Hand</span>
			<h1 class="display-xl mt-4">Turn your animal experience into income.</h1>
			<p class="lead mt-4" style="max-width:36rem;">Know your way around a barn? Join The Ranch Hand as a horse sitter, farm sitter, or livestock caretaker. Set your own rates, pick your own schedule, and get matched with owners who need you.</p>
			<a class="btn btn-hay mt-6" href="#apply">Apply to be a caretaker</a>
		</div>
	</div>
</section>

<section class="section">
	<div class="container-rh">
		<div class="grid grid-3">
			<?php foreach ( $perks as $p ) : ?>
				<div class="card">
					<div class="icon-lg" aria-hidden="true"><?php echo esc_html( $p[0] ); ?></div>
					<h3 class="mt-3" style="font-size:1.25rem;"><?php echo esc_html( $p[1] ); ?></h3>
					<p class="muted mt-2"><?php echo esc_html( $p[2] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="section section-alt">
	<div class="container-rh">
		<h2 class="display-lg text-center">How to get started</h2>
		<div class="grid grid-3 mt-10">
			<?php foreach ( $steps as $s ) : ?>
				<div class="card text-center">
					<div class="step-num"><?php echo esc_html( $s[0] ); ?></div>
					<h3 class="mt-4" style="font-size:1.25rem;"><?php echo esc_html( $s[1] ); ?></h3>
					<p class="muted mt-2"><?php echo esc_html( $s[2] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- Application form -->
<section class="section" id="apply">
	<div class="container-rh" style="max-width:42rem;">
		<h2 class="display-lg text-center">Apply to be a caretaker</h2>
		<p class="muted text-center mt-2">Tell us a bit about your experience. We'll review your application and get you set up with a profile.</p>

		<div class="card mt-8">
			<?php trh_lead_notice(); ?>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="trh_lead" />
				<input type="hidden" name="lead_type" value="application" />
				<?php wp_nonce_field( 'trh_lead', 'trh_lead_nonce' ); ?>
				<p style="position:absolute;left:-9999px;" aria-hidden="true"><label>Website<input type="text" name="trh_website" tabindex="-1" autocomplete="off" /></label></p>

				<div class="grid grid-2" style="gap:0 1.25rem;">
					<div class="field">
						<label class="label" for="ap-name">Full name</label>
						<input class="input" type="text" id="ap-name" name="name" required />
					</div>
					<div class="field">
						<label class="label" for="ap-email">Email</label>
						<input class="input" type="email" id="ap-email" name="email" required />
					</div>
					<div class="field">
						<label class="label" for="ap-phone">Phone</label>
						<input class="input" type="tel" id="ap-phone" name="phone" />
					</div>
					<div class="field">
						<label class="label" for="ap-loc">Location (city, state)</label>
						<input class="input" type="text" id="ap-loc" name="location" placeholder="e.g. Bozeman, MT" />
					</div>
				</div>
				<div class="field">
					<label class="label" for="ap-msg">Your experience with animals</label>
					<textarea class="textarea" id="ap-msg" name="message" placeholder="Which animals have you cared for? How many years? Any certifications (vet tech, etc.)?" required></textarea>
				</div>
				<button class="btn btn-primary" type="submit" style="width:100%;">Submit application</button>
				<p class="help text-center">We review every application by hand and reply by email.</p>
			</form>
		</div>
	</div>
</section>

<?php get_footer(); ?>
