<?php
/**
 * Template Name: Hand Dashboard
 *
 * Where a Hand lands after finishing signup, and where they come back to. Shows
 * the Trust Score with its breakdown, the review status of the profile, and a
 * "ways to earn more" list that links back into the wizard steps.
 *
 * Signed out, this page is the front-end login (handled by trh_handle_hand_login()
 * in inc/hand-signup.php, deliberately not wp-login.php).
 *
 * @package The_Ranch_Hand
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

$trh_profile = trh_hand_profile_id();
$trh_welcome = ! empty( $_GET['welcome'] );
$trh_saved   = ! empty( $_GET['saved'] );
$trh_login   = isset( $_GET['login'] ) && is_scalar( $_GET['login'] ) ? sanitize_key( wp_unslash( $_GET['login'] ) ) : '';
?>

<?php if ( ! is_user_logged_in() ) : ?>

	<section class="page-hero page-hero-tight">
		<div class="container-rh">
			<p class="hero-kicker">Ranch Hands</p>
			<h1 class="display-lg">Sign in to your dashboard</h1>
		</div>
	</section>

	<section class="section">
		<div class="container-rh auth-grid">
			<div class="card">
				<?php if ( 'failed' === $trh_login ) : ?>
					<div class="notice notice-error">That email and password did not match. Please try again.</div>
				<?php elseif ( 'expired' === $trh_login ) : ?>
					<div class="notice notice-error">Your sign-in form timed out. Please try again.</div>
				<?php endif; ?>

				<h2 class="display-md">Welcome back</h2>
				<form class="mt-6" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
					<input type="hidden" name="action" value="trh_hand_login" />
					<?php wp_nonce_field( 'trh_hand_login', 'trh_login_nonce' ); ?>
					<div class="field">
						<label class="label" for="dash-log">Username or email</label>
						<input class="input" type="text" id="dash-log" name="log" autocomplete="username" spellcheck="false" autocapitalize="none" required />
					</div>
					<div class="field">
						<label class="label" for="dash-pwd">Password</label>
						<div class="pw-wrap">
							<input class="input" type="password" id="dash-pwd" name="pwd" autocomplete="current-password" required />
							<button class="pw-toggle" type="button" data-trh-pw="dash-pwd" aria-label="Show password">Show</button>
						</div>
					</div>
					<label class="check-inline">
						<input type="checkbox" name="rememberme" value="1" checked />
						<span>Keep me signed in</span>
					</label>
					<div class="wizard-actions">
						<button class="btn btn-primary" type="submit">Sign in</button>
					</div>
					<p class="help">Trouble getting in? <a class="link" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Send us a note</a> and we will sort it out.</p>
				</form>
			</div>

			<aside>
				<div class="card">
					<h2 class="aside-title">New here?</h2>
					<p class="muted" style="font-size:.9375rem;">Create a Hand profile in three short steps and start with <?php echo esc_html( trh_trust_base() ); ?> Trust Score points.</p>
					<a class="btn btn-hay mt-4" style="width:100%;" href="<?php echo esc_url( trh_signup_url( 1 ) ); ?>">Create my Hand profile</a>
				</div>
			</aside>
		</div>
	</section>

<?php elseif ( ! $trh_profile ) : ?>

	<section class="section">
		<div class="container-rh" style="max-width:38rem;">
			<div class="card text-center">
				<div class="icon-lg" aria-hidden="true">🤠</div>
				<h1 class="display-lg mt-4">No Hand profile yet</h1>
				<p class="muted mt-2">The account you are signed in to (<?php echo esc_html( wp_get_current_user()->user_email ); ?>) does not have a Hand profile attached.</p>
				<a class="btn btn-primary mt-6" href="<?php echo esc_url( trh_signup_url( 1 ) ); ?>">Create a Hand profile</a>
				<p class="help mt-4"><a class="link" href="<?php echo esc_url( trh_hand_logout_url() ); ?>">Sign out</a></p>
			</div>
		</div>
	</section>

<?php else : ?>

	<?php
	$trh_score     = trh_trust_score( $trh_profile );
	$trh_rows      = trh_trust_rows( $trh_profile );
	$trh_available = trh_trust_available( $trh_profile );
	$trh_awards    = trh_trust_ledger_recent( $trh_profile );
	$trh_earned    = trh_trust_ledger_total( $trh_profile );
	$trh_status    = trh_hand_status( $trh_profile );
	$trh_complete  = trh_hand_is_complete( $trh_profile );
	$trh_next_step = max( 1, min( 3, trh_hand_step_done( $trh_profile ) + 1 ) );
	$trh_checked   = trh_hand_experience( $trh_profile );
	$trh_labels    = trh_experience_labels();
	$trh_refs      = trh_hand_references( $trh_profile );
	$trh_socials   = trh_hand_socials( $trh_profile );
	$trh_resume    = trh_hand_resume_url( $trh_profile );
	$trh_photo_id  = get_post_thumbnail_id( $trh_profile );
	?>

	<section class="dash-hero">
		<div class="container-rh">
			<div class="dash-hero-inner">
				<div class="dash-ident">
					<?php if ( $trh_photo_id ) : ?>
						<img class="dash-avatar" src="<?php echo esc_url( wp_get_attachment_image_url( $trh_photo_id, 'medium' ) ); ?>" alt="" />
					<?php else : ?>
						<span class="dash-avatar dash-avatar-empty" aria-hidden="true">🤠</span>
					<?php endif; ?>
					<div>
						<p class="hero-kicker">Hand dashboard</p>
						<h1 class="display-lg"><?php echo esc_html( trh_hand_name( $trh_profile ) ); ?></h1>
						<p class="dash-sub">📍 <?php echo esc_html( trh_hand_field( $trh_profile, 'location' ) ); ?>
							<span class="badge badge-<?php echo esc_attr( $trh_status['tone'] ); ?>"><?php echo esc_html( $trh_status['label'] ); ?></span>
						</p>
					</div>
				</div>
				<a class="btn btn-ghost btn-sm" href="<?php echo esc_url( trh_hand_logout_url() ); ?>">Sign out</a>
			</div>
		</div>
	</section>

	<section class="section">
		<div class="container-rh">

			<?php if ( $trh_welcome ) : ?>
				<div class="celebrate">
					<div class="celebrate-badge">+<?php echo esc_html( $trh_score ); ?></div>
					<div>
						<h2 class="display-md">Your profile is in. You earned <?php echo esc_html( $trh_score ); ?> Trust Score points.</h2>
						<p class="muted mt-2">We review every Hand by hand before the profile goes live in the sitter directory, usually within a business day. <?php echo $trh_available ? 'There are still <strong>' . esc_html( $trh_available ) . ' points</strong> on the table below.' : 'You claimed every point available at signup, nicely done.'; ?></p>
					</div>
				</div>
			<?php elseif ( $trh_saved ) : ?>
				<div class="notice notice-success">Saved. Your Trust Score is now <?php echo esc_html( $trh_score ); ?>.</div>
			<?php endif; ?>

			<?php if ( ! $trh_complete ) : ?>
				<div class="notice notice-error" style="display:flex;flex-wrap:wrap;align-items:center;gap:1rem;justify-content:space-between;">
					<span>Your profile is not finished yet, so it is not being reviewed. Step <?php echo esc_html( $trh_next_step ); ?> is next.</span>
					<a class="btn btn-primary btn-sm" href="<?php echo esc_url( trh_signup_url( $trh_next_step ) ); ?>">Finish my profile</a>
				</div>
			<?php endif; ?>

			<div class="dash-grid mt-6">
				<div class="dash-main">

					<!-- Trust Score -->
					<div class="card" id="trust-score">
						<div class="score-head">
							<?php trh_trust_dial( $trh_score, '8.5rem' ); ?>
							<div>
								<h2 class="display-md">Trust Score</h2>
								<p class="muted mt-2">Your Trust Score is how your profile grows. A higher score puts you in front of more owners and better paying jobs. It climbs as you complete your profile, and later as you finish bookings and collect reviews.</p>
								<?php if ( $trh_earned ) : ?>
									<p class="score-avail mt-4"><strong><?php echo esc_html( $trh_score - $trh_earned ); ?></strong> from your profile, <strong>+<?php echo esc_html( $trh_earned ); ?></strong> earned since.</p>
								<?php endif; ?>
								<?php if ( $trh_available ) : ?>
									<p class="score-avail mt-4"><strong><?php echo esc_html( $trh_available ); ?> points</strong> still available on this page.</p>
								<?php endif; ?>
							</div>
						</div>

						<ul class="score-list mt-6">
							<?php foreach ( $trh_rows as $trh_row ) : ?>
								<li class="score-row<?php echo $trh_row['earned'] ? ' is-earned' : ''; ?>">
									<span class="score-tick" aria-hidden="true"><?php echo $trh_row['earned'] ? '✓' : '○'; ?></span>
									<span class="score-text">
										<span class="score-label"><?php echo esc_html( $trh_row['label'] ); ?><?php echo $trh_row['bonus'] ? ' <span class="label-opt">bonus</span>' : ''; ?></span>
										<span class="score-blurb"><?php echo esc_html( $trh_row['blurb'] ); ?></span>
									</span>
									<?php if ( $trh_row['done'] ) : ?>
										<span class="score-pts">+<?php echo esc_html( $trh_row['awarded'] ); ?></span>
									<?php else : ?>
										<a class="btn btn-hay btn-sm score-cta" href="<?php echo esc_url( add_query_arg( 'from', 'dashboard', trh_signup_url( $trh_row['step'] ) ) ); ?>"><?php
											$trh_row_left = (int) $trh_row['points'] - (int) $trh_row['awarded'];
											echo esc_html( $trh_row['awarded'] ? '+' . $trh_row_left . ' more' : 'Add it +' . $trh_row_left );
										?></a>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>

						<?php if ( $trh_awards ) : ?>
							<div class="score-earned">
								<p class="score-earned-head"><span>Earned since signup</span> <span>+<?php echo esc_html( $trh_earned ); ?></span></p>
								<ul class="score-list mt-2">
									<?php foreach ( $trh_awards as $trh_award ) : ?>
										<li class="score-row is-earned">
											<span class="score-tick" aria-hidden="true">✓</span>
											<span class="score-text">
												<span class="score-label"><?php echo esc_html( trh_trust_award_label( $trh_award['type'] ) ); ?></span>
												<?php if ( $trh_award['note'] ) : ?>
													<span class="score-blurb"><?php echo esc_html( $trh_award['note'] ); ?></span>
												<?php endif; ?>
												<?php if ( $trh_award['time'] ) : ?>
													<span class="score-when"><?php echo esc_html( wp_date( 'M j, Y', $trh_award['time'] ) ); ?></span>
												<?php endif; ?>
											</span>
											<span class="score-pts">+<?php echo esc_html( $trh_award['points'] ); ?></span>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php else : ?>
							<p class="help mt-4">Coming with the booking engine: points for completed jobs, owner reviews, repeat clients, and identity verification.</p>
						<?php endif; ?>
					</div>

					<!-- Experience -->
					<div class="card mt-6">
						<div class="card-head">
							<h2 class="display-md">Your experience</h2>
							<a class="link" href="<?php echo esc_url( add_query_arg( 'from', 'dashboard', trh_signup_url( 3 ) ) ); ?>">Edit</a>
						</div>
						<?php if ( $trh_checked ) : ?>
							<p class="muted mt-2"><?php echo esc_html( count( $trh_checked ) ); ?> skills checked<?php
							$trh_years = (int) trh_hand_field( $trh_profile, 'experience_years', 0 );
							echo $trh_years ? esc_html( ', ' . $trh_years . ' years around horses and livestock' ) : '';
							?>.</p>
							<div class="chip-wrap mt-4">
								<?php foreach ( $trh_checked as $trh_key ) : ?>
									<span class="chip"><?php echo esc_html( isset( $trh_labels[ $trh_key ] ) ? $trh_labels[ $trh_key ] : $trh_key ); ?></span>
								<?php endforeach; ?>
							</div>
						<?php else : ?>
							<p class="muted mt-2">You have not filled in the experience checklist yet. It is worth 20 points and it is what owners read first.</p>
						<?php endif; ?>
					</div>

					<!-- Proof -->
					<div class="card mt-6">
						<div class="card-head">
							<h2 class="display-md">Resume, references &amp; links</h2>
							<a class="link" href="<?php echo esc_url( add_query_arg( 'from', 'dashboard', trh_signup_url( 2 ) ) ); ?>">Edit</a>
						</div>

						<div class="rate-row">
							<span>Resume</span>
							<span><?php
							if ( $trh_resume ) {
								echo '<a class="link" href="' . esc_url( $trh_resume ) . '" target="_blank" rel="noopener">View file</a>';
							} else {
								echo '<span class="muted">Not uploaded</span>';
							}
							?></span>
						</div>
						<div class="rate-row">
							<span>Profile picture</span>
							<span><?php echo $trh_photo_id ? '✓ Added' : '<span class="muted">Not added</span>'; ?></span>
						</div>
						<div class="rate-row">
							<span>Social accounts</span>
							<span><?php
							if ( $trh_socials ) {
								$trh_parts = array();
								foreach ( $trh_socials as $trh_key => $trh_url ) {
									$trh_parts[] = '<a class="link" href="' . esc_url( $trh_url ) . '" target="_blank" rel="noopener">' . esc_html( ucfirst( $trh_key ) ) . '</a>';
								}
								echo wp_kses_post( implode( ', ', $trh_parts ) );
							} else {
								echo '<span class="muted">None linked</span>';
							}
							?></span>
						</div>
						<div class="rate-row">
							<span>References</span>
							<span><?php echo $trh_refs ? esc_html( count( $trh_refs ) . ' on file' ) : '<span class="muted">None yet</span>'; ?></span>
						</div>
					</div>
				</div>

				<aside class="dash-aside">
					<div class="card">
						<h2 class="aside-title">Your profile</h2>
						<p class="muted" style="font-size:.9375rem;">
							<?php if ( 'publish' === get_post_status( $trh_profile ) ) : ?>
								Your profile is live in the sitter directory.
							<?php elseif ( $trh_complete ) : ?>
								We are reviewing your profile now. Once it is approved it appears in the sitter directory and owners can send you booking requests.
							<?php else : ?>
								Finish all three steps and we will start reviewing your profile.
							<?php endif; ?>
						</p>
						<?php if ( 'publish' === get_post_status( $trh_profile ) ) : ?>
							<a class="btn btn-secondary mt-4" style="width:100%;" href="<?php echo esc_url( get_permalink( $trh_profile ) ); ?>">View my public profile</a>
						<?php endif; ?>
					</div>

					<div class="card mt-6">
						<h2 class="aside-title">Contact details</h2>
						<ul class="dash-facts">
							<li><span>Username</span><strong><?php echo esc_html( trh_hand_field( $trh_profile, 'username', wp_get_current_user()->user_login ) ); ?></strong></li>
							<li><span>Email</span><strong><?php echo esc_html( trh_hand_field( $trh_profile, 'email' ) ); ?></strong></li>
							<li><span>Phone</span><strong><?php echo esc_html( trh_hand_field( $trh_profile, 'phone' ) ); ?></strong></li>
							<li><span>Location</span><strong><?php echo esc_html( trh_hand_field( $trh_profile, 'location' ) ); ?></strong></li>
						</ul>
						<p class="help mt-4">Need one of these changed? <a class="link" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Tell us</a> and we will update it. Your username is fixed.</p>
					</div>

					<div class="card mt-6">
						<h2 class="aside-title">What happens next</h2>
						<ol class="dash-steps">
							<li>We review your profile and call your references.</li>
							<li>Your profile goes live in the sitter directory.</li>
							<li>Owners near <?php echo esc_html( trh_hand_field( $trh_profile, 'city' ) ); ?> send you booking requests.</li>
						</ol>
						<a class="btn btn-ghost btn-sm mt-4" href="<?php echo esc_url( trh_directory_url() ); ?>">See the directory</a>
					</div>
				</aside>
			</div>
		</div>
	</section>

<?php endif; ?>

<?php get_footer(); ?>
