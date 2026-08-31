<?php
/**
 * My Profile: the parts of a Hand's profile they come back to change.
 *
 * Just the picture and the social links. Both are scored, and the page says so
 * next to every control, so the connection between "add a link" and "the number
 * in the header went up" is never a mystery. Resume, references and the
 * experience checklist stay in the wizard.
 *
 * @package The_Ranch_Hand
 */
get_header();

$trh_profile = is_user_logged_in() ? trh_hand_profile_id() : 0;

if ( ! $trh_profile ) {
	?>
	<section class="section">
		<div class="container-rh" style="max-width:32rem;">
			<div class="card">
				<h1 class="display-md">Sign in first</h1>
				<p class="muted mt-2">Your profile lives behind your Hand account.</p>
				<a class="btn btn-primary mt-4" href="<?php echo esc_url( trh_dashboard_url() ); ?>">Go to sign in</a>
			</div>
		</div>
	</section>
	<?php
	get_footer();
	return;
}

$trh_score    = trh_trust_score( $trh_profile );
$trh_socials  = trh_hand_socials( $trh_profile );
$trh_networks = trh_social_networks();
$trh_photo    = trh_hand_avatar_url( $trh_profile, 'medium' );
$trh_saved    = isset( $_GET['saved'] );
$trh_delta    = isset( $_GET['delta'] ) ? (int) $_GET['delta'] : 0;

// Points per social link, read from the scoring rules rather than hardcoded, so
// this page cannot drift from trh_trust_components().
$trh_components  = trh_trust_components();
$trh_social_max  = isset( $trh_components['socials']['points'] ) ? (int) $trh_components['socials']['points'] : 0;
$trh_photo_pts   = isset( $trh_components['photo']['points'] ) ? (int) $trh_components['photo']['points'] : 0;
$trh_per_social  = count( $trh_networks ) ? intdiv( $trh_social_max, count( $trh_networks ) ) : 0;
?>

<section class="dash-hero">
	<div class="container-rh">
		<div class="dash-hero-inner">
			<div class="dash-ident">
				<?php trh_hand_avatar( $trh_profile, 'dash-avatar', 'medium' ); ?>
				<div>
					<p class="hero-kicker">My profile</p>
					<h1 class="display-lg"><?php echo esc_html( trh_hand_name( $trh_profile ) ); ?></h1>
					<p class="dash-sub">@<?php echo esc_html( trh_hand_field( $trh_profile, 'username' ) ); ?></p>
				</div>
			</div>
			<a class="btn btn-ghost btn-sm" href="<?php echo esc_url( trh_dashboard_url() ); ?>">Back to dashboard</a>
		</div>
	</div>
</section>

<section class="section">
	<div class="container-rh" style="max-width:44rem;">

		<?php trh_signup_error_notice(); ?>

		<?php if ( $trh_saved ) : ?>
			<?php if ( $trh_delta > 0 ) : ?>
				<div class="notice notice-success">Saved. That earned you <strong>+<?php echo esc_html( $trh_delta ); ?></strong> Trust Score points, putting you at <?php echo esc_html( $trh_score ); ?>.</div>
			<?php elseif ( $trh_delta < 0 ) : ?>
				<div class="notice notice-warn">Saved. Taking that off your profile cost you <strong><?php echo esc_html( $trh_delta ); ?></strong> Trust Score points, putting you at <?php echo esc_html( $trh_score ); ?>.</div>
			<?php else : ?>
				<div class="notice notice-success">Saved. Your Trust Score is still <?php echo esc_html( $trh_score ); ?>.</div>
			<?php endif; ?>
		<?php endif; ?>

		<form class="card" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="trh_hand_profile" />
			<?php wp_nonce_field( 'trh_hand_profile', 'trh_profile_nonce' ); ?>

			<!-- Profile picture -->
			<div class="card-head">
				<h2 class="display-md">Profile picture</h2>
				<?php trh_points_pill( $trh_photo_pts, (bool) $trh_photo ); ?>
			</div>
			<p class="muted mt-2">Owners pick profiles with a face on them far more often. A clear photo of you, ideally around animals.</p>

			<div class="profile-photo mt-4">
				<?php trh_hand_avatar( $trh_profile, 'profile-photo-current', 'medium' ); ?>
				<div class="profile-photo-controls">
					<label class="btn btn-hay btn-sm" for="trh-photo">
						<?php echo $trh_photo ? 'Choose a new picture' : 'Add a picture'; ?>
					</label>
					<input type="file" id="trh-photo" name="photo" accept="image/jpeg,image/png,image/webp" class="visually-hidden" />
					<?php if ( $trh_photo ) : ?>
						<label class="profile-photo-remove">
							<input type="checkbox" name="remove_photo" value="1" />
							Remove it <span class="help">(costs <?php echo esc_html( $trh_photo_pts ); ?> points)</span>
						</label>
					<?php endif; ?>
				</div>
			</div>

			<!-- Social links -->
			<div class="card-head mt-8">
				<h2 class="display-md">Social accounts</h2>
				<?php trh_points_pill( $trh_per_social, false ); ?>
			</div>
			<p class="muted mt-2">
				<?php echo esc_html( $trh_per_social ); ?> Trust Score points for each account you link, up to
				<?php echo esc_html( $trh_social_max ); ?> for all <?php echo esc_html( count( $trh_networks ) ); ?>.
				Clear a field and save to unlink it, and those points come back off.
			</p>

			<div class="social-rows mt-4">
				<?php foreach ( $trh_networks as $trh_key => $trh_net ) : ?>
					<?php $trh_val = isset( $trh_socials[ $trh_key ] ) ? $trh_socials[ $trh_key ] : ''; ?>
					<div class="social-row<?php echo $trh_val ? ' is-linked' : ''; ?>">
						<label class="social-label" for="trh-social-<?php echo esc_attr( $trh_key ); ?>">
							<span aria-hidden="true"><?php echo esc_html( $trh_net['icon'] ); ?></span>
							<?php echo esc_html( $trh_net['label'] ); ?>
						</label>
						<input type="url" id="trh-social-<?php echo esc_attr( $trh_key ); ?>"
							name="social[<?php echo esc_attr( $trh_key ); ?>]"
							value="<?php echo esc_attr( $trh_val ); ?>"
							placeholder="<?php echo esc_attr( $trh_net['placeholder'] ); ?>" />
						<span class="social-pts"><?php echo $trh_val ? '<strong>+' . esc_html( $trh_per_social ) . '</strong>' : '&mdash;'; ?></span>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="mt-6" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
				<button class="btn btn-primary" type="submit">Save my profile</button>
				<span class="help">Your Trust Score updates the moment you save.</span>
			</div>
		</form>

		<p class="help mt-6">
			Your resume, references and experience checklist live in
			<a class="link" href="<?php echo esc_url( add_query_arg( 'from', 'profile', trh_signup_url( 2 ) ) ); ?>">the full profile editor</a>.
		</p>

	</div>
</section>

<?php get_footer(); ?>
