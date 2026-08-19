<?php
/**
 * Template Name: Hand Signup
 *
 * The three-step "create your Hand profile" wizard, auto-applied to the page
 * with slug "hand-signup". The step is driven by ?step=1|2|3; each step posts to
 * admin-post.php and is handled in inc/hand-signup.php.
 *
 * Step 1 is public. Steps 2 and 3 require the account that step 1 creates, and
 * they double as the edit screens the dashboard links to when a Hand comes back
 * to raise their Trust Score.
 *
 * @package The_Ranch_Hand
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$trh_step    = isset( $_GET['step'] ) ? max( 1, min( 3, (int) $_GET['step'] ) ) : 1;
$trh_profile = trh_hand_profile_id();
$trh_from    = isset( $_GET['from'] ) && 'dashboard' === $_GET['from'] ? 'dashboard' : 'wizard';

// Steps 2-3 need the account step 1 creates.
if ( $trh_step > 1 && ! $trh_profile ) {
	wp_safe_redirect( trh_signup_url( 1 ) );
	exit;
}
// A Hand who already has a profile never needs the account step again.
if ( 1 === $trh_step && $trh_profile ) {
	wp_safe_redirect( trh_hand_is_complete( $trh_profile ) ? trh_dashboard_url() : trh_signup_url( 2 ) );
	exit;
}

$trh_steps = array(
	1 => array( 'label' => 'About you',   'points' => 40, 'blurb' => 'Who you are and where you work.' ),
	2 => array( 'label' => 'Your proof',  'points' => 70, 'blurb' => 'Resume, photo, socials, references.' ),
	3 => array( 'label' => 'Experience',  'points' => 20, 'blurb' => 'Everything you have hands-on experience with.' ),
);

get_header();
?>

<section class="page-hero page-hero-tight">
	<div class="container-rh">
		<p class="hero-kicker">Become a Hand</p>
		<h1 class="display-lg">Create your Hand profile</h1>
		<p class="muted mt-2">Three short steps. Finish them all and you start with <strong><?php echo esc_html( trh_trust_base() ); ?> Trust Score points</strong>, plus up to <?php echo esc_html( trh_trust_max() - trh_trust_base() ); ?> more for the optional extras.</p>
	</div>
</section>

<section class="section">
	<div class="container-rh">

		<!-- Progress -->
		<ol class="steps-bar" aria-label="Signup progress">
			<?php foreach ( $trh_steps as $n => $s ) : ?>
				<?php
				$state = 'todo';
				if ( $n < $trh_step ) {
					$state = 'done';
				} elseif ( $n === $trh_step ) {
					$state = 'current';
				}
				?>
				<li class="step-item is-<?php echo esc_attr( $state ); ?>">
					<span class="step-dot"><?php echo 'done' === $state ? '✓' : esc_html( $n ); ?></span>
					<span class="step-meta">
						<span class="step-label"><?php echo esc_html( $s['label'] ); ?></span>
						<span class="step-blurb"><?php echo esc_html( $s['blurb'] ); ?></span>
					</span>
				</li>
			<?php endforeach; ?>
		</ol>

		<div class="wizard">
			<div class="wizard-main">
				<?php trh_signup_error_notice(); ?>

				<?php if ( 1 === $trh_step ) : ?>
					<?php if ( is_user_logged_in() ) : ?>
						<div class="notice notice-error">
							You are signed in as <?php echo esc_html( wp_get_current_user()->display_name ); ?>, which has no Hand profile.
							<a href="<?php echo esc_url( trh_hand_logout_url() ); ?>">Sign out</a> first if you are creating a profile for yourself.
						</div>
					<?php endif; ?>

					<?php /* ---------------- STEP 1: account + contact ---------------- */ ?>
					<div class="card wizard-card">
						<h2 class="display-md">Tell us about you</h2>
						<p class="muted mt-2">This is how owners near you get in touch. Your phone number and email are never shown publicly, we pass them on only when you accept a job.</p>

						<form class="mt-6" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
							<input type="hidden" name="action" value="trh_hand_step1" />
							<?php wp_nonce_field( 'trh_hand_step1', 'trh_hand_nonce' ); ?>
							<p style="position:absolute;left:-9999px;" aria-hidden="true"><label>Website<input type="text" name="trh_website" tabindex="-1" autocomplete="off" /></label></p>

							<div class="grid grid-2" style="gap:0 1.25rem;">
								<div class="field">
									<label class="label" for="hs-first">First name</label>
									<input class="input" type="text" id="hs-first" name="first_name" autocomplete="given-name" value="<?php echo esc_attr( trh_signup_value( 'first_name' ) ); ?>" required />
								</div>
								<div class="field">
									<label class="label" for="hs-last">Last name</label>
									<input class="input" type="text" id="hs-last" name="last_name" autocomplete="family-name" value="<?php echo esc_attr( trh_signup_value( 'last_name' ) ); ?>" required />
								</div>
								<div class="field">
									<label class="label" for="hs-phone">Phone number</label>
									<input class="input" type="tel" id="hs-phone" name="phone" autocomplete="tel" placeholder="(555) 123-4567" value="<?php echo esc_attr( trh_signup_value( 'phone' ) ); ?>" required />
								</div>
								<div class="field">
									<label class="label" for="hs-email">Email</label>
									<input class="input" type="email" id="hs-email" name="email" autocomplete="email" value="<?php echo esc_attr( trh_signup_value( 'email' ) ); ?>" required />
								</div>
							</div>

							<fieldset class="fieldset">
								<legend class="legend">Where do you work?</legend>
								<div class="grid grid-loc">
									<div class="field">
										<label class="label" for="hs-city">City or town</label>
										<div class="combo" data-trh-combo>
											<input class="input" type="text" id="hs-city" name="city" autocomplete="off" spellcheck="false"
												role="combobox" aria-expanded="false" aria-autocomplete="list" aria-controls="hs-city-list"
												placeholder="Start typing, e.g. Columbia"
												value="<?php echo esc_attr( trh_signup_value( 'city' ) ); ?>" required />
											<ul class="combo-list" id="hs-city-list" role="listbox" aria-label="Matching towns" hidden></ul>
										</div>
									</div>
									<div class="field">
										<label class="label" for="hs-state">State</label>
										<select class="select" id="hs-state" name="state" required>
											<option value="">Choose…</option>
											<?php foreach ( trh_us_states() as $code => $name ) : ?>
												<option value="<?php echo esc_attr( $code ); ?>" <?php selected( trh_signup_value( 'state' ), $code ); ?>><?php echo esc_html( $name ); ?></option>
											<?php endforeach; ?>
										</select>
									</div>
									<div class="field">
										<label class="label" for="hs-zip">ZIP <span class="label-opt">optional</span></label>
										<input class="input" type="text" id="hs-zip" name="zip" inputmode="numeric" maxlength="5" autocomplete="postal-code" value="<?php echo esc_attr( trh_signup_value( 'zip' ) ); ?>" />
									</div>
								</div>
								<p class="help">Type your town and pick it from the list to confirm which state you mean. Small towns not in the list are fine, just choose your state.</p>
							</fieldset>

							<fieldset class="fieldset">
								<legend class="legend">Create your sign-in</legend>
								<p class="help" style="margin-top:0;margin-bottom:1rem;">This is how you get back into your dashboard to edit your profile and, later, to answer booking requests.</p>

								<div class="field">
									<label class="label" for="hs-username">Username</label>
									<input class="input" type="text" id="hs-username" name="username" autocomplete="username"
										minlength="3" maxlength="30" pattern="[A-Za-z0-9][A-Za-z0-9._\-]{2,29}"
										spellcheck="false" autocapitalize="none"
										placeholder="e.g. jane.rides"
										value="<?php echo esc_attr( trh_signup_value( 'username' ) ); ?>" required />
									<p class="help">3 to 30 characters: letters, numbers, periods, hyphens, and underscores. Pick something you will remember, it cannot be changed later.</p>
								</div>

								<div class="grid grid-2" style="gap:0 1.25rem;">
									<div class="field">
										<label class="label" for="hs-pass">Password</label>
										<div class="pw-wrap">
											<input class="input" type="password" id="hs-pass" name="password" minlength="8" autocomplete="new-password" required />
											<button class="pw-toggle" type="button" data-trh-pw="hs-pass" aria-label="Show password">Show</button>
										</div>
										<p class="help">At least 8 characters.</p>
									</div>
									<div class="field">
										<label class="label" for="hs-pass2">Confirm password</label>
										<div class="pw-wrap">
											<input class="input" type="password" id="hs-pass2" name="password_confirm" minlength="8" autocomplete="new-password" required />
											<button class="pw-toggle" type="button" data-trh-pw="hs-pass2" aria-label="Show password">Show</button>
										</div>
										<p class="help">Type it once more so a typo cannot lock you out.</p>
									</div>
								</div>
							</fieldset>

							<label class="check-inline">
								<input type="checkbox" name="agree" value="1" required />
								<span>I am 18 or older and I agree to the
									<a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>" target="_blank" rel="noopener">Terms of Service</a> and
									<a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>" target="_blank" rel="noopener">Privacy Policy</a>.</span>
							</label>

							<div class="wizard-actions">
								<button class="btn btn-primary" type="submit">Create my account <span class="pts-inline">+40</span></button>
								<span class="help">Step 1 of 3</span>
							</div>
						</form>
					</div>

				<?php elseif ( 2 === $trh_step ) : ?>
					<?php
					/* ---------------- STEP 2: proof, socials, references ---------------- */
					$trh_socials  = trh_hand_socials( $trh_profile );
					$trh_refs     = trh_hand_references( $trh_profile );
					$trh_resume   = trh_hand_resume_url( $trh_profile );
					$trh_photo_id = get_post_thumbnail_id( $trh_profile );
					$trh_earned   = trh_trust_earned( $trh_profile );
					?>
					<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
						<input type="hidden" name="action" value="trh_hand_step2" />
						<input type="hidden" name="after" value="<?php echo esc_attr( $trh_from ); ?>" />
						<?php wp_nonce_field( 'trh_hand_step2', 'trh_hand_nonce' ); ?>

						<div class="card wizard-card">
							<div class="card-head">
								<h2 class="display-md">Show owners who you are</h2>
								<?php trh_points_pill( 15, ! empty( $trh_earned['photo'] ) ); ?>
							</div>
							<p class="muted mt-2">A clear photo of you, ideally with an animal, is the single biggest thing that gets you picked.</p>

							<div class="upload-row mt-4">
								<div class="upload-preview" data-trh-preview="hs-photo">
									<?php if ( $trh_photo_id ) : ?>
										<img src="<?php echo esc_url( wp_get_attachment_image_url( $trh_photo_id, 'medium' ) ); ?>" alt="Your current profile picture" />
									<?php else : ?>
										<span class="upload-placeholder" aria-hidden="true">🤠</span>
									<?php endif; ?>
								</div>
								<div class="upload-body">
									<label class="label" for="hs-photo">Profile picture</label>
									<input class="file" type="file" id="hs-photo" name="photo" accept="image/jpeg,image/png,image/webp,image/gif" />
									<p class="help">JPG, PNG, WebP, or GIF, up to <?php echo esc_html( size_format( TRH_MAX_PHOTO_BYTES ) ); ?>.<?php echo $trh_photo_id ? ' Uploading a new one replaces the current picture.' : ''; ?></p>
								</div>
							</div>
						</div>

						<div class="card wizard-card mt-6">
							<div class="card-head">
								<h2 class="display-md">Upload your resume</h2>
								<?php trh_points_pill( 20, ! empty( $trh_earned['resume'] ) ); ?>
							</div>
							<p class="muted mt-2">Barn work, ranch work, vet clinics, showing, 4-H, anything relevant. A plain list of where you have worked is plenty.</p>

							<div class="mt-4">
								<label class="label" for="hs-resume">Resume file</label>
								<input class="file" type="file" id="hs-resume" name="resume" accept=".pdf,.doc,.docx,.odt,.rtf,.txt" />
								<p class="help">PDF, Word, or text, up to <?php echo esc_html( size_format( TRH_MAX_RESUME_BYTES ) ); ?>. Only you and our review team can see it.</p>
								<?php if ( $trh_resume ) : ?>
									<p class="file-current">✓ On file: <a href="<?php echo esc_url( $trh_resume ); ?>" target="_blank" rel="noopener">view your resume</a>. Uploading a new one replaces it.</p>
								<?php endif; ?>
							</div>
						</div>

						<div class="card wizard-card mt-6">
							<div class="card-head">
								<h2 class="display-md">Connect your social accounts</h2>
								<?php trh_points_pill( 15, ! empty( $trh_earned['socials'] ) ); ?>
							</div>
							<p class="muted mt-2">Add any one of these to earn the points. Owners like seeing the animals you already work with.</p>

							<div class="grid grid-2 mt-4" style="gap:0 1.25rem;">
								<?php foreach ( trh_social_networks() as $key => $net ) : ?>
									<div class="field">
										<label class="label" for="hs-social-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $net['icon'] . ' ' . $net['label'] ); ?></label>
										<input class="input" type="text" id="hs-social-<?php echo esc_attr( $key ); ?>"
											name="social[<?php echo esc_attr( $key ); ?>]"
											placeholder="<?php echo esc_attr( $net['placeholder'] ); ?>"
											value="<?php echo esc_attr( isset( $trh_socials[ $key ] ) ? $trh_socials[ $key ] : '' ); ?>" />
									</div>
								<?php endforeach; ?>
							</div>
						</div>

						<div class="card wizard-card mt-6">
							<div class="card-head">
								<h2 class="display-md">References</h2>
								<?php trh_points_pill( 20, ! empty( $trh_earned['references'] ) ); ?>
							</div>
							<p class="muted mt-2">People who can speak to how you handle animals: a barn manager, a trainer, a vet, an owner you have sat for. One is enough, three is better. We contact them only during review.</p>

							<?php for ( $i = 0; $i < 3; $i++ ) : ?>
								<?php $trh_ref = isset( $trh_refs[ $i ] ) ? $trh_refs[ $i ] : array(); ?>
								<fieldset class="ref-block">
									<legend class="legend">Reference <?php echo esc_html( $i + 1 ); ?><?php echo 0 === $i ? '' : ' <span class="label-opt">optional</span>'; ?></legend>
									<div class="grid grid-2" style="gap:0 1.25rem;">
										<div class="field">
											<label class="label" for="hs-ref<?php echo esc_attr( $i ); ?>-name">Name</label>
											<input class="input" type="text" id="hs-ref<?php echo esc_attr( $i ); ?>-name" name="ref[<?php echo esc_attr( $i ); ?>][name]" value="<?php echo esc_attr( isset( $trh_ref['name'] ) ? $trh_ref['name'] : '' ); ?>" />
										</div>
										<div class="field">
											<label class="label" for="hs-ref<?php echo esc_attr( $i ); ?>-rel">How they know you</label>
											<input class="input" type="text" id="hs-ref<?php echo esc_attr( $i ); ?>-rel" name="ref[<?php echo esc_attr( $i ); ?>][relationship]" placeholder="e.g. barn manager at Willow Creek" value="<?php echo esc_attr( isset( $trh_ref['relationship'] ) ? $trh_ref['relationship'] : '' ); ?>" />
										</div>
										<div class="field">
											<label class="label" for="hs-ref<?php echo esc_attr( $i ); ?>-phone">Phone</label>
											<input class="input" type="tel" id="hs-ref<?php echo esc_attr( $i ); ?>-phone" name="ref[<?php echo esc_attr( $i ); ?>][phone]" value="<?php echo esc_attr( isset( $trh_ref['phone'] ) ? $trh_ref['phone'] : '' ); ?>" />
										</div>
										<div class="field">
											<label class="label" for="hs-ref<?php echo esc_attr( $i ); ?>-email">Email</label>
											<input class="input" type="email" id="hs-ref<?php echo esc_attr( $i ); ?>-email" name="ref[<?php echo esc_attr( $i ); ?>][email]" value="<?php echo esc_attr( isset( $trh_ref['email'] ) ? $trh_ref['email'] : '' ); ?>" />
										</div>
									</div>
								</fieldset>
							<?php endfor; ?>
						</div>

						<div class="wizard-actions mt-6">
							<button class="btn btn-primary" type="submit">Save and continue</button>
							<?php if ( 'dashboard' === $trh_from ) : ?>
								<a class="btn btn-ghost" href="<?php echo esc_url( trh_dashboard_url() ); ?>">Cancel</a>
							<?php else : ?>
								<a class="btn btn-ghost" href="<?php echo esc_url( trh_signup_url( 3 ) ); ?>">Skip for now</a>
							<?php endif; ?>
						</div>
					</form>

				<?php else : ?>
					<?php
					/* ---------------- STEP 3: experience checklist ---------------- */
					$trh_checked = trh_hand_experience( $trh_profile );
					$trh_years   = (int) trh_hand_field( $trh_profile, 'experience_years', 0 );
					$trh_about   = get_post_field( 'post_content', $trh_profile );
					?>
					<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
						<input type="hidden" name="action" value="trh_hand_step3" />
						<?php wp_nonce_field( 'trh_hand_step3', 'trh_hand_nonce' ); ?>

						<div class="card wizard-card">
							<div class="card-head">
								<h2 class="display-md">What have you actually done?</h2>
								<?php trh_points_pill( 20, count( $trh_checked ) >= TRH_EXPERIENCE_MIN ); ?>
							</div>
							<p class="muted mt-2">Check everything you have real hands-on experience with. Be honest, owners ask about these, and your references get asked too. Check at least <?php echo esc_html( TRH_EXPERIENCE_MIN ); ?>.</p>

							<div class="field mt-6" style="max-width:22rem;">
								<label class="label" for="hs-years">Years working around horses or livestock</label>
								<select class="select" id="hs-years" name="experience_years">
									<?php
									$trh_year_opts = array( 0 => 'Less than a year', 1 => '1 year', 2 => '2 years', 3 => '3 years', 5 => '5 years', 8 => '8 years', 10 => '10 years', 15 => '15 years', 20 => '20+ years', 30 => '30+ years' );
									foreach ( $trh_year_opts as $val => $label ) :
										?>
										<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $trh_years, $val ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>

							<p class="check-counter" data-trh-counter aria-live="polite"><strong><?php echo esc_html( count( $trh_checked ) ); ?></strong> checked</p>

							<?php foreach ( trh_experience_groups() as $trh_group_name => $trh_group ) : ?>
								<fieldset class="check-group">
									<legend class="group-title"><span aria-hidden="true"><?php echo esc_html( $trh_group['icon'] ); ?></span> <?php echo esc_html( $trh_group_name ); ?></legend>
									<div class="check-grid">
										<?php foreach ( $trh_group['items'] as $trh_key => $trh_label ) : ?>
											<?php $trh_on = in_array( $trh_key, $trh_checked, true ); ?>
											<label class="check-item<?php echo $trh_on ? ' is-checked' : ''; ?>">
												<input type="checkbox" name="experience[]" value="<?php echo esc_attr( $trh_key ); ?>" <?php checked( $trh_on ); ?> />
												<span class="check-box" aria-hidden="true"></span>
												<span class="check-text"><?php echo esc_html( $trh_label ); ?></span>
											</label>
										<?php endforeach; ?>
									</div>
								</fieldset>
							<?php endforeach; ?>

							<div class="field mt-6">
								<label class="label" for="hs-about">Anything else owners should know <span class="label-opt">optional</span></label>
								<textarea class="textarea" id="hs-about" name="about" rows="5" placeholder="How you got started, the barns you have worked at, what you are best at, anything you would rather not take on."><?php echo esc_textarea( $trh_about ); ?></textarea>
								<p class="help">This becomes the "about" section of your public profile.</p>
							</div>
						</div>

						<div class="wizard-actions mt-6">
							<button class="btn btn-primary" type="submit"><?php echo trh_hand_is_complete( $trh_profile ) ? 'Save my experience' : 'Finish and claim my Trust Score'; ?></button>
							<a class="btn btn-ghost" href="<?php echo esc_url( trh_signup_url( 2 ) ); ?>">Back</a>
						</div>
					</form>
				<?php endif; ?>
			</div>

			<!-- Score sidebar -->
			<aside class="wizard-aside">
				<div class="card">
					<h2 class="aside-title">Your Trust Score</h2>
					<p class="muted" style="font-size:.875rem;">Trust Score is how your profile earns better jobs. Every piece you add is worth points, the same way a review earns points elsewhere.</p>

					<div class="mt-6 text-center">
						<?php trh_trust_dial( $trh_profile ? trh_trust_score( $trh_profile ) : 0, '8.5rem' ); ?>
						<p class="help" style="margin-top:.75rem;">of <?php echo esc_html( trh_trust_max() ); ?> available at signup</p>
					</div>

					<ul class="score-list mt-6">
						<?php
						$trh_rows = $trh_profile ? trh_trust_rows( $trh_profile ) : trh_trust_rows( 0 );
						foreach ( $trh_rows as $trh_row ) :
							?>
							<li class="score-row<?php echo $trh_row['earned'] ? ' is-earned' : ''; ?>">
								<span class="score-tick" aria-hidden="true"><?php echo $trh_row['earned'] ? '✓' : '○'; ?></span>
								<span class="score-text">
									<span class="score-label"><?php echo esc_html( $trh_row['label'] ); ?><?php echo $trh_row['bonus'] ? ' <span class="label-opt">bonus</span>' : ''; ?></span>
									<span class="score-blurb"><?php echo esc_html( $trh_row['blurb'] ); ?></span>
								</span>
								<span class="score-pts"><?php echo $trh_row['earned'] ? '+' : ''; ?><?php echo esc_html( $trh_row['points'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>

				<div class="card mt-6">
					<h2 class="aside-title">Already started?</h2>
					<p class="muted" style="font-size:.875rem;">Your progress saves after every step. Come back any time and sign in from the <a class="link" href="<?php echo esc_url( trh_dashboard_url() ); ?>">dashboard</a>.</p>
				</div>
			</aside>
		</div>
	</div>
</section>

<?php get_footer(); ?>
