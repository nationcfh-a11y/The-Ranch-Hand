<?php
/**
 * Contact page. Feeds the same lead pipeline as the booking and application
 * forms (inc/forms.php), tagged lead_type=contact so it lands in wp-admin ->
 * Leads under its own heading.
 *
 * @package The_Ranch_Hand
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

$trh_dir = trh_directory_url();
?>

<section class="page-hero">
	<div class="container-rh">
		<h1 class="display-lg">Get in touch</h1>
		<p class="muted mt-2">Questions about finding a sitter, listing yourself as a caretaker, or anything else? Send us a note.</p>
	</div>
</section>

<section class="section">
	<div class="container-rh contact-grid">

		<div class="card">
			<?php trh_lead_notice(); ?>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="trh_lead" />
				<input type="hidden" name="lead_type" value="contact" />
				<?php wp_nonce_field( 'trh_lead', 'trh_lead_nonce' ); ?>
				<p style="position:absolute;left:-9999px;" aria-hidden="true"><label>Website<input type="text" name="trh_website" tabindex="-1" autocomplete="off" /></label></p>

				<div class="grid grid-2" style="gap:0 1.25rem;">
					<div class="field">
						<label class="label" for="ct-name">Your name</label>
						<input class="input" type="text" id="ct-name" name="name" autocomplete="name" required />
					</div>
					<div class="field">
						<label class="label" for="ct-email">Email</label>
						<input class="input" type="email" id="ct-email" name="email" autocomplete="email" required />
					</div>
					<div class="field">
						<label class="label" for="ct-phone">Phone <span class="muted" style="font-weight:400;">(optional)</span></label>
						<input class="input" type="tel" id="ct-phone" name="phone" autocomplete="tel" />
					</div>
					<div class="field">
						<label class="label" for="ct-subject">What is this about?</label>
						<select class="select" id="ct-subject" name="service">
							<option value="General question">General question</option>
							<option value="Help finding a sitter">Help finding a sitter</option>
							<option value="Becoming a caretaker">Becoming a caretaker</option>
							<option value="Problem with the site">Problem with the site</option>
							<option value="Something else">Something else</option>
						</select>
					</div>
				</div>
				<div class="field">
					<label class="label" for="ct-msg">Message</label>
					<textarea class="textarea" id="ct-msg" name="message" rows="6" required></textarea>
				</div>
				<button class="btn btn-primary" type="submit" style="width:100%;">Send message</button>
				<p class="help text-center">We read every message and reply by email, usually within one business day.</p>
			</form>
		</div>

		<aside class="contact-aside">
			<div class="card">
				<h2 style="font-size:1.125rem;">Looking for something specific?</h2>
				<ul class="contact-links mt-4">
					<li><a class="contact-link" href="<?php echo esc_url( $trh_dir ); ?>">Browse horse &amp; farm sitters</a></li>
					<li><a class="contact-link" href="<?php echo esc_url( home_url( '/become-a-caretaker/' ) ); ?>">Apply to become a caretaker</a></li>
					<li><a class="contact-link" href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">How we handle your information</a></li>
				</ul>
			</div>

			<div class="card mt-6">
				<h2 style="font-size:1.125rem;">Before you write</h2>
				<p class="muted mt-2" style="font-size:.9375rem;">If you are trying to book a specific sitter, the fastest route is the request form on their profile. It reaches them directly with your dates and care details.</p>
			</div>

			<div class="card mt-6">
				<h2 style="font-size:1.125rem;">Animal emergency?</h2>
				<p class="muted mt-2" style="font-size:.9375rem;">We are not an emergency service and cannot respond quickly enough to help. Please call your veterinarian.</p>
			</div>
		</aside>

	</div>
</section>

<?php get_footer(); ?>
