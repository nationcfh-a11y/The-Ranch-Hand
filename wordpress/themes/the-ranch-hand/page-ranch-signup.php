<?php
/**
 * Template Name: Ranch Signup
 *
 * Registration for a ranch / animal owner. Auto-applied to the page with slug
 * "ranch-signup" (the "Register Your Ranch" nav link).
 *
 * A single-page form (lighter than the Hand's three-step wizard) that creates a
 * Ranch account (username + password, so they can log back in) and captures
 * questions about the place, the animals, and the help they need. Handled by
 * trh_handle_ranch_signup() in inc/ranch.php; validation errors + submitted
 * values round-trip through the shared flash system (inc/hand-signup.php).
 *
 * @package The_Ranch_Hand
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

$hero_img = get_template_directory_uri() . '/assets/img/hero-home.webp';
$perks    = array(
	array( '🤝', 'Matched with trusted Hands', 'Tell us about your place and we point the right experienced caretakers your way.' ),
	array( '🔐', 'Your own account', 'Create a login so you can come back, post jobs, and manage everything in one place.' ),
	array( '🐴', 'Care that fits your animals', 'The more we know about your herd and your needs, the better the match.' ),
);

$frequencies = array( 'One-time while I travel', 'Occasionally', 'A few times a month', 'A few times a week', 'Daily', 'Not sure yet' );
$freq_value  = trh_signup_value( 'frequency' );
?>

<section class="hero">
	<div class="hero-bg" style="background-image:url('<?php echo esc_url( $hero_img ); ?>');" aria-hidden="true"></div>
	<div class="hero-grain" aria-hidden="true"></div>
	<div class="container-rh hero-inner">
		<div style="max-width:42rem;">
			<span class="badge badge-hay">🏡 Register Your Ranch</span>
			<h1 class="display-xl mt-4">Get help caring for your animals.</h1>
			<p class="lead mt-4" style="max-width:36rem;">Create your ranch account and tell us what you need, and we'll connect you with experienced Ranch Hands nearby. It's free to join.</p>
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
		<p class="muted text-center mt-2">Create your account and tell us about your place. Fields marked <span style="color:var(--barn);">*</span> are required.</p>

		<div class="card mt-8">
			<?php trh_signup_error_notice(); ?>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="trh_ranch_signup" />
				<?php wp_nonce_field( 'trh_ranch_signup', 'trh_ranch_nonce' ); ?>
				<p style="position:absolute;left:-9999px;" aria-hidden="true"><label>Website<input type="text" name="trh_website" tabindex="-1" autocomplete="off" /></label></p>

				<!-- About you -->
				<fieldset style="border:0;padding:0;margin:0;">
					<legend class="label" style="font-size:1.05rem;font-weight:800;margin-bottom:.5rem;">About you</legend>
					<div class="grid grid-2" style="gap:0 1.25rem;">
						<div class="field">
							<label class="label" for="rn-name">Full name <span style="color:var(--barn);">*</span></label>
							<input class="input" type="text" id="rn-name" name="name" value="<?php echo esc_attr( trh_signup_value( 'name' ) ); ?>" required />
						</div>
						<div class="field">
							<label class="label" for="rn-email">Email <span style="color:var(--barn);">*</span></label>
							<input class="input" type="email" id="rn-email" name="email" value="<?php echo esc_attr( trh_signup_value( 'email' ) ); ?>" required />
						</div>
						<div class="field">
							<label class="label" for="rn-phone">Phone</label>
							<input class="input" type="tel" id="rn-phone" name="phone" value="<?php echo esc_attr( trh_signup_value( 'phone' ) ); ?>" />
						</div>
						<div class="field">
							<label class="label" for="rn-farm">Ranch or farm name</label>
							<input class="input" type="text" id="rn-farm" name="farm_name" value="<?php echo esc_attr( trh_signup_value( 'farm_name' ) ); ?>" placeholder="e.g. Blackwater Ranch" />
						</div>
						<div class="field">
							<label class="label" for="rn-loc">Location (city, state) <span style="color:var(--barn);">*</span></label>
							<input class="input" type="text" id="rn-loc" name="location" value="<?php echo esc_attr( trh_signup_value( 'location' ) ); ?>" placeholder="e.g. Spring Hill, TN" required />
						</div>
					</div>
				</fieldset>

				<!-- About your place -->
				<fieldset style="border:0;padding:0;margin:1.5rem 0 0;">
					<legend class="label" style="font-size:1.05rem;font-weight:800;margin-bottom:.5rem;">About your place</legend>
					<div class="field">
						<label class="label" for="rn-acres">How big is your property?</label>
						<input class="input" type="text" id="rn-acres" name="acres" value="<?php echo esc_attr( trh_signup_value( 'acres' ) ); ?>" placeholder="e.g. 12 acres, or a small backyard barn" />
					</div>
					<div class="field">
						<span class="label">What animals do you have? <span class="muted" style="font-weight:400;">check all that apply</span></span>
						<div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.4rem;">
							<?php foreach ( trh_animals() as $aname => $a ) : ?>
								<label style="display:inline-flex;align-items:center;gap:.4rem;border:1px solid var(--line,#dcd3c2);border-radius:999px;padding:.4rem .8rem;cursor:pointer;">
									<input type="checkbox" name="animals[]" value="<?php echo esc_attr( $aname ); ?>" />
									<?php echo esc_html( $a['icon'] . ' ' . $aname ); ?>
								</label>
							<?php endforeach; ?>
							<label style="display:inline-flex;align-items:center;gap:.4rem;border:1px solid var(--line,#dcd3c2);border-radius:999px;padding:.4rem .8rem;cursor:pointer;">
								<input type="checkbox" class="trh-other-toggle" data-target="rn-animals-other" name="animals[]" value="Other" />
								➕ Other
							</label>
						</div>
						<div id="rn-animals-other" class="field" style="margin-top:.6rem;" hidden>
							<input class="input" type="text" name="animals_other" placeholder="What other animals do you have?" />
						</div>
					</div>
					<div class="field">
						<label class="label" for="rn-animal-details">Tell us about your animals</label>
						<textarea class="textarea" id="rn-animal-details" name="animal_details" rows="3" placeholder="How many of each? Ages, temperaments, any special care (seniors, medication, foals, etc.)."><?php echo esc_textarea( trh_signup_value( 'animal_details' ) ); ?></textarea>
					</div>
				</fieldset>

				<!-- What you need -->
				<fieldset style="border:0;padding:0;margin:1.5rem 0 0;">
					<legend class="label" style="font-size:1.05rem;font-weight:800;margin-bottom:.5rem;">What you need</legend>
					<div class="field">
						<span class="label">What do you need help with? <span class="muted" style="font-weight:400;">check all that apply</span></span>
						<div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.4rem;">
							<?php foreach ( trh_services() as $skey => $s ) : ?>
								<label style="display:inline-flex;align-items:center;gap:.4rem;border:1px solid var(--line,#dcd3c2);border-radius:999px;padding:.4rem .8rem;cursor:pointer;">
									<input type="checkbox" name="needs[]" value="<?php echo esc_attr( $s['label'] ); ?>" />
									<?php echo esc_html( $s['label'] ); ?>
								</label>
							<?php endforeach; ?>
							<label style="display:inline-flex;align-items:center;gap:.4rem;border:1px solid var(--line,#dcd3c2);border-radius:999px;padding:.4rem .8rem;cursor:pointer;">
								<input type="checkbox" class="trh-other-toggle" data-target="rn-needs-other" name="needs[]" value="Other" />
								➕ Other
							</label>
						</div>
						<div id="rn-needs-other" class="field" style="margin-top:.6rem;" hidden>
							<input class="input" type="text" name="needs_other" placeholder="What other help do you need?" />
						</div>
					</div>
					<div class="grid grid-2" style="gap:0 1.25rem;">
						<div class="field">
							<label class="label" for="rn-frequency">How often do you need help?</label>
							<select class="select" id="rn-frequency" name="frequency">
								<option value="">Select…</option>
								<?php foreach ( $frequencies as $f ) : ?>
									<option value="<?php echo esc_attr( $f ); ?>" <?php selected( $f, $freq_value ); ?>><?php echo esc_html( $f ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="field">
							<label class="label" for="rn-start">When do you need to start? <span style="color:var(--barn);">*</span></label>
							<input class="input" type="date" id="rn-start" name="start" value="<?php echo esc_attr( trh_signup_value( 'start' ) ); ?>" required />
						</div>
					</div>
					<div class="field">
						<label class="label" for="rn-looking">What matters most to you in a Ranch Hand?</label>
						<textarea class="textarea" id="rn-looking" name="looking_for" rows="3" placeholder="e.g. experience with horses, someone reliable for overnights, comfortable giving meds, sends daily updates."><?php echo esc_textarea( trh_signup_value( 'looking_for' ) ); ?></textarea>
					</div>
					<div class="field">
						<label class="label" for="rn-notes">Anything else we should know?</label>
						<textarea class="textarea" id="rn-notes" name="notes" rows="3" placeholder="Gate codes, dogs on site, equipment, or anything special about your place."><?php echo esc_textarea( trh_signup_value( 'notes' ) ); ?></textarea>
					</div>
				</fieldset>

				<!-- Account -->
				<fieldset style="border:0;padding:0;margin:1.5rem 0 0;">
					<legend class="label" style="font-size:1.05rem;font-weight:800;margin-bottom:.5rem;">Create your login</legend>
					<div class="field">
						<label class="label" for="rn-username">Username <span style="color:var(--barn);">*</span></label>
						<input class="input" type="text" id="rn-username" name="username" value="<?php echo esc_attr( trh_signup_value( 'username' ) ); ?>" autocomplete="username" spellcheck="false" autocapitalize="none" required />
					</div>
					<div class="grid grid-2" style="gap:0 1.25rem;">
						<div class="field">
							<label class="label" for="rn-password">Password <span style="color:var(--barn);">*</span></label>
							<div class="pw-wrap">
								<input class="input" type="password" id="rn-password" name="password" autocomplete="new-password" minlength="8" required />
								<button class="pw-toggle" type="button" data-trh-pw="rn-password" aria-label="Show password">Show</button>
							</div>
						</div>
						<div class="field">
							<label class="label" for="rn-password2">Confirm password <span style="color:var(--barn);">*</span></label>
							<input class="input" type="password" id="rn-password2" name="password_confirm" autocomplete="new-password" minlength="8" required />
						</div>
					</div>
					<label class="check-inline" style="margin-top:.25rem;">
						<input type="checkbox" name="agree" value="1" required />
						<span>I agree to the <a class="link" href="<?php echo esc_url( home_url( '/terms/' ) ); ?>" target="_blank" rel="noopener">Terms of Service</a> and <a class="link" href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>" target="_blank" rel="noopener">Privacy Policy</a>. <span style="color:var(--barn);">*</span></span>
					</label>
				</fieldset>

				<button class="btn btn-primary mt-6" type="submit" style="width:100%;">Create my ranch account</button>
				<p class="help text-center">Already registered? <a class="link" href="<?php echo esc_url( trh_dashboard_url() ); ?>">Log in</a>.</p>
			</form>
		</div>
	</div>
</section>

<script>
( function () {
	document.querySelectorAll( '.trh-other-toggle' ).forEach( function ( cb ) {
		var box = document.getElementById( cb.getAttribute( 'data-target' ) );
		if ( ! box ) { return; }
		var sync = function () {
			box.hidden = ! cb.checked;
			if ( cb.checked ) { var i = box.querySelector( 'input' ); if ( i ) { i.focus(); } }
		};
		cb.addEventListener( 'change', sync );
		sync();
	} );
}() );
</script>

<?php get_footer(); ?>
