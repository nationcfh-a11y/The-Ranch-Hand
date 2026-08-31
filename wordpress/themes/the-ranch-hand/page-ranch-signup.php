<?php
/**
 * Template Name: Ranch Signup
 *
 * Registration for a ranch / animal owner. Auto-applied to the page with slug
 * "ranch-signup" (the "Register Your Ranch" nav link).
 *
 * A single-page form (deliberately lighter than the Hand's three-step wizard):
 * contact details plus questions about the place, the animals, and the help
 * they need. It feeds the shared Leads pipeline as a "ranch" lead (inc/forms.php)
 * and mirrors into the "Ranch" tab of the Google Sheet (inc/sheet.php).
 *
 * @package The_Ranch_Hand
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

$hero_img = get_template_directory_uri() . '/assets/img/hero-home.webp';
$perks    = array(
	array( '🤝', 'Matched with trusted Hands', 'Tell us about your place and we point the right experienced caretakers your way.' ),
	array( '📍', 'Local to you', 'Set how far a Hand can travel so you only hear from people who can actually get to you.' ),
	array( '🐴', 'Care that fits your animals', 'The more we know about your herd and your needs, the better the match.' ),
);

$frequencies = array( 'One-time while I travel', 'Occasionally', 'A few times a week', 'Daily', 'Ongoing / regular help', 'Not sure yet' );
?>

<section class="hero">
	<div class="hero-bg" style="background-image:url('<?php echo esc_url( $hero_img ); ?>');" aria-hidden="true"></div>
	<div class="hero-grain" aria-hidden="true"></div>
	<div class="container-rh hero-inner">
		<div style="max-width:42rem;">
			<span class="badge badge-hay">🏡 Register Your Ranch</span>
			<h1 class="display-xl mt-4">Get help caring for your animals.</h1>
			<p class="lead mt-4" style="max-width:36rem;">Tell us about your ranch or farm and what you need, and we'll connect you with experienced Ranch Hands nearby. It's free to join.</p>
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
	<div class="container-rh" style="max-width:44rem;">
		<h2 class="display-lg text-center">Register your ranch</h2>
		<p class="muted text-center mt-2">Only your name, email, and location are required &mdash; the rest just helps us match you better.</p>

		<div class="card mt-8">
			<?php trh_lead_notice( 'Welcome to The Ranch Hand! Your ranch is registered. We\'ll be in touch by email soon.' ); ?>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="trh_lead" />
				<input type="hidden" name="lead_type" value="ranch" />
				<?php wp_nonce_field( 'trh_lead', 'trh_lead_nonce' ); ?>
				<p style="position:absolute;left:-9999px;" aria-hidden="true"><label>Website<input type="text" name="trh_website" tabindex="-1" autocomplete="off" /></label></p>

				<!-- About you -->
				<fieldset style="border:0;padding:0;margin:0;">
					<legend class="label" style="font-size:1.05rem;font-weight:800;margin-bottom:.5rem;">About you</legend>
					<div class="grid grid-2" style="gap:0 1.25rem;">
						<div class="field">
							<label class="label" for="rn-name">Full name</label>
							<input class="input" type="text" id="rn-name" name="name" required />
						</div>
						<div class="field">
							<label class="label" for="rn-email">Email</label>
							<input class="input" type="email" id="rn-email" name="email" required />
						</div>
						<div class="field">
							<label class="label" for="rn-phone">Phone <span class="label-opt">optional</span></label>
							<input class="input" type="tel" id="rn-phone" name="phone" />
						</div>
						<div class="field">
							<label class="label" for="rn-farm">Ranch or farm name <span class="label-opt">optional</span></label>
							<input class="input" type="text" id="rn-farm" name="farm_name" placeholder="e.g. Blackwater Ranch" />
						</div>
						<div class="field">
							<label class="label" for="rn-loc">Location (city, state)</label>
							<input class="input" type="text" id="rn-loc" name="location" placeholder="e.g. Spring Hill, TN" required />
						</div>
						<div class="field">
							<label class="label" for="rn-radius">How far can a Hand travel to you?</label>
							<select class="select" id="rn-radius" name="search_radius">
								<?php
								$radii   = array( 10, 20, 30, 50, 75, 100 );
								$default = 20;
								foreach ( $radii as $r ) {
									printf( '<option value="%1$d"%2$s>Up to %1$d miles</option>', $r, selected( $r, $default, false ) );
								}
								?>
							</select>
						</div>
					</div>
				</fieldset>

				<!-- About your place -->
				<fieldset style="border:0;padding:0;margin:1.5rem 0 0;">
					<legend class="label" style="font-size:1.05rem;font-weight:800;margin-bottom:.5rem;">About your place</legend>
					<div class="field">
						<label class="label" for="rn-acres">How big is your property? <span class="label-opt">optional</span></label>
						<input class="input" type="text" id="rn-acres" name="acres" placeholder="e.g. 12 acres, or a small backyard barn" />
					</div>
					<div class="field">
						<span class="label">What animals do you have? <span class="label-opt">check all that apply</span></span>
						<div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.4rem;">
							<?php foreach ( trh_animals() as $aname => $a ) : ?>
								<label style="display:inline-flex;align-items:center;gap:.4rem;border:1px solid var(--line,#dcd3c2);border-radius:999px;padding:.4rem .8rem;cursor:pointer;">
									<input type="checkbox" name="animals[]" value="<?php echo esc_attr( $aname ); ?>" />
									<?php echo esc_html( $a['icon'] . ' ' . $aname ); ?>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
					<div class="field">
						<label class="label" for="rn-animal-details">Tell us about your animals <span class="label-opt">optional</span></label>
						<textarea class="textarea" id="rn-animal-details" name="animal_details" rows="3" placeholder="How many of each? Ages, temperaments, any special care (seniors, medication, foals, etc.)."></textarea>
					</div>
				</fieldset>

				<!-- What you need -->
				<fieldset style="border:0;padding:0;margin:1.5rem 0 0;">
					<legend class="label" style="font-size:1.05rem;font-weight:800;margin-bottom:.5rem;">What you need</legend>
					<div class="field">
						<span class="label">What do you need help with? <span class="label-opt">check all that apply</span></span>
						<div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.4rem;">
							<?php foreach ( trh_services() as $skey => $s ) : ?>
								<label style="display:inline-flex;align-items:center;gap:.4rem;border:1px solid var(--line,#dcd3c2);border-radius:999px;padding:.4rem .8rem;cursor:pointer;">
									<input type="checkbox" name="needs[]" value="<?php echo esc_attr( $s['label'] ); ?>" />
									<?php echo esc_html( $s['label'] ); ?>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
					<div class="grid grid-2" style="gap:0 1.25rem;">
						<div class="field">
							<label class="label" for="rn-frequency">How often do you need help?</label>
							<select class="select" id="rn-frequency" name="frequency">
								<option value="">Select…</option>
								<?php foreach ( $frequencies as $f ) : ?>
									<option value="<?php echo esc_attr( $f ); ?>"><?php echo esc_html( $f ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="field">
							<label class="label" for="rn-start">When do you need to start? <span class="label-opt">optional</span></label>
							<input class="input" type="text" id="rn-start" name="start" placeholder="e.g. next month, or June 3-10" />
						</div>
					</div>
					<div class="field">
						<label class="label" for="rn-looking">What matters most to you in a Ranch Hand? <span class="label-opt">optional</span></label>
						<textarea class="textarea" id="rn-looking" name="looking_for" rows="3" placeholder="e.g. experience with horses, someone reliable for overnights, comfortable giving meds, sends daily updates."></textarea>
					</div>
					<div class="field">
						<label class="label" for="rn-notes">Anything else we should know? <span class="label-opt">optional</span></label>
						<textarea class="textarea" id="rn-notes" name="notes" rows="3" placeholder="Gate codes, dogs on site, equipment, or anything special about your place."></textarea>
					</div>
				</fieldset>

				<button class="btn btn-primary mt-6" type="submit" style="width:100%;">Register my ranch</button>
				<p class="help text-center">Free to join. We'll only use your details to connect you with caretakers.</p>
			</form>
		</div>
	</div>
</section>

<?php get_footer(); ?>
