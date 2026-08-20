<?php
/**
 * Template Name: Ranch Signup
 *
 * Simple registration for a ranch / animal owner. Auto-applied to the page with
 * slug "ranch-signup" (the "Register Your Ranch" nav link).
 *
 * Deliberately minimal for now (name, email, location, search radius): it feeds
 * the shared Leads pipeline as a "ranch" lead and is mirrored into the "Ranch"
 * tab of the Google Sheet (inc/sheet.php). A fuller owner account + job posting
 * is planned for later.
 *
 * @package The_Ranch_Hand
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

$hero_img = get_template_directory_uri() . '/assets/img/hero-home.webp';
$perks    = array(
	array( '🤝', 'Matched with trusted Hands', 'Tell us where you are and how far you can reach, and we help the right caretakers find you.' ),
	array( '📍', 'Local to you', 'Set a search radius so you only hear from Hands who can actually get to your place.' ),
	array( '⏱️', 'Two-minute signup', 'Just the basics for now. You can add your animals and needs later.' ),
);
?>

<section class="hero">
	<div class="hero-bg" style="background-image:url('<?php echo esc_url( $hero_img ); ?>');" aria-hidden="true"></div>
	<div class="hero-grain" aria-hidden="true"></div>
	<div class="container-rh hero-inner">
		<div style="max-width:42rem;">
			<span class="badge badge-hay">🏡 Register Your Ranch</span>
			<h1 class="display-xl mt-4">Get help caring for your animals.</h1>
			<p class="lead mt-4" style="max-width:36rem;">Sign up your ranch or farm and we'll connect you with experienced Ranch Hands nearby. It takes about two minutes, and it's free.</p>
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
	<div class="container-rh" style="max-width:42rem;">
		<h2 class="display-lg text-center">Register your ranch</h2>
		<p class="muted text-center mt-2">We'll use this to match you with Ranch Hands in your area.</p>

		<div class="card mt-8">
			<?php trh_lead_notice( 'Welcome to The Ranch Hand! Your ranch is registered. We\'ll be in touch by email soon.' ); ?>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="trh_lead" />
				<input type="hidden" name="lead_type" value="ranch" />
				<?php wp_nonce_field( 'trh_lead', 'trh_lead_nonce' ); ?>
				<p style="position:absolute;left:-9999px;" aria-hidden="true"><label>Website<input type="text" name="trh_website" tabindex="-1" autocomplete="off" /></label></p>

				<div class="field">
					<label class="label" for="rn-name">Full name</label>
					<input class="input" type="text" id="rn-name" name="name" required />
				</div>
				<div class="field">
					<label class="label" for="rn-email">Email</label>
					<input class="input" type="email" id="rn-email" name="email" required />
				</div>
				<div class="grid grid-2" style="gap:0 1.25rem;">
					<div class="field">
						<label class="label" for="rn-loc">Location (city, state)</label>
						<input class="input" type="text" id="rn-loc" name="location" placeholder="e.g. Spring Hill, TN" required />
					</div>
					<div class="field">
						<label class="label" for="rn-radius">Search radius (mi)</label>
						<select class="select" id="rn-radius" name="search_radius">
							<?php
							$radii   = array( 10, 20, 30, 50, 75, 100 );
							$default = 20;
							foreach ( $radii as $r ) {
								printf(
									'<option value="%1$d"%2$s>%1$d miles</option>',
									$r,
									selected( $r, $default, false )
								);
							}
							?>
						</select>
					</div>
				</div>
				<button class="btn btn-primary" type="submit" style="width:100%;">Register my ranch</button>
				<p class="help text-center">Free to join. We'll only use your details to connect you with caretakers.</p>
			</form>
		</div>
	</div>
</section>

<?php get_footer(); ?>
