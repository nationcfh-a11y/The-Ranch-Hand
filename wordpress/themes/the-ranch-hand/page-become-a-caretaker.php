<?php
/**
 * Template Name: Become a Caretaker
 *
 * Recruiting landing page for Hands. Auto-applied to the page with slug
 * "become-a-caretaker" (the "Become A Hand" nav link), and also selectable as a
 * page template. Ports client/src/pages/BecomeCaretaker.jsx.
 *
 * Every primary call to action here points at the three-step profile wizard
 * (page-hand-signup.php). The short "send us a note" form at the bottom is the
 * low-commitment fallback for someone who is not ready to make a profile yet;
 * it feeds the same Leads pipeline as the other forms (inc/forms.php).
 *
 * @package The_Ranch_Hand
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

$signup = trh_signup_url( 1 );

$perks = array(
	array( '💵', 'Set your own rates', 'You decide what each service costs. See exactly what you keep on every booking.' ),
	array( '📅', 'Work on your schedule', 'Accept the bookings that fit your week. Overnights, drop-ins, or full-farm care.' ),
	array( '🤝', 'Owners who trust you', 'Your experience, animals, and reviews are front and center so the right owners find you.' ),
);
$steps = array(
	array( 1, 'Create your account', 'Your name, phone, and the town you work out of. About two minutes.' ),
	array( 2, 'Add your proof', 'Resume, profile picture, references, and your social accounts.' ),
	array( 3, 'Check off your experience', 'Everything you have really done, from blanketing and wound care to foal watch.' ),
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
			<a class="btn btn-hay mt-6" href="<?php echo esc_url( $signup ); ?>">Create my Hand profile</a>
			<p class="mt-4" style="color:rgba(247,241,227,.85);font-size:.9375rem;">Free to join. Three steps. Start with <?php echo esc_html( trh_trust_base() ); ?>+ Trust Score points.</p>
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

<!-- Trust Score explainer -->
<section class="section section-alt">
	<div class="container-rh">
		<div class="score-head">
			<?php trh_trust_dial( trh_trust_max(), '9rem' ); ?>
			<div>
				<h2 class="display-lg">Your Trust Score opens better jobs</h2>
				<p class="muted mt-4">Trust Score is our point system for Hands. Every piece of proof you add to your profile is worth points, the same way leaving a review somewhere earns you a few. The higher your score, the higher you sit in front of owners, and the better the work that comes your way.</p>
				<ul class="score-list mt-6">
					<?php foreach ( trh_trust_rows( 0 ) as $row ) : ?>
						<li class="score-row">
							<span class="score-tick" aria-hidden="true">+</span>
							<span class="score-text">
								<span class="score-label"><?php echo esc_html( $row['label'] ); ?><?php echo $row['bonus'] ? ' <span class="label-opt">bonus</span>' : ''; ?></span>
								<span class="score-blurb"><?php echo esc_html( $row['blurb'] ); ?></span>
							</span>
							<span class="score-pts"><?php echo esc_html( $row['points'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
				<p class="help mt-4">Finish the required steps and you land on <?php echo esc_html( trh_trust_base() ); ?>. Add a photo and link all six social accounts and you start at <?php echo esc_html( trh_trust_max() ); ?>. It keeps climbing as you complete jobs and collect reviews.</p>
			</div>
		</div>
	</div>
</section>

<section class="section">
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

<!-- Primary call to action -->
<section class="section" id="apply">
	<div class="container-rh">
		<div class="bg-barn text-center" style="border-radius:var(--radius-xl);padding:3rem 2rem;">
			<h2 class="display-lg" style="color:var(--cream-100);">Ready to get hired?</h2>
			<p class="lead mt-4 measure" style="color:rgba(247,241,227,.9);">Build your profile in three short steps and claim your Trust Score. You can stop after any step and pick it back up later, nothing is lost.</p>
			<a class="btn btn-hay mt-6" href="<?php echo esc_url( $signup ); ?>">Create my Hand profile</a>
			<p class="mt-4" style="color:rgba(247,241,227,.75);font-size:.875rem;">Already started? <a class="link" style="color:var(--hay-light);" href="<?php echo esc_url( trh_dashboard_url() ); ?>">Sign in to your dashboard</a></p>
		</div>
	</div>
</section>

<!-- Low-commitment fallback: talk to a person first -->
<section class="section section-alt">
	<div class="container-rh" style="max-width:42rem;">
		<h2 class="display-lg text-center">Not ready to build a profile?</h2>
		<p class="muted text-center mt-2">Send us a note instead and we will answer your questions first. No profile needed.</p>

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
				<button class="btn btn-secondary" type="submit" style="width:100%;">Send my note</button>
				<p class="help text-center">We read every note by hand and reply by email.</p>
			</form>
		</div>
	</div>
</section>

<?php get_footer(); ?>
