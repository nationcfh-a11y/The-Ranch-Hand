<?php
/**
 * Template Name: Ranch Job Plans
 *
 * Pricing / job-posting tiers shown to a ranch right after they register (see
 * the redirect in inc/forms.php). Auto-applied to the page with slug
 * "ranch-plans".
 *
 * Presentation only for now: the tiers and perks are laid out, but checkout is
 * future work, so the CTAs point at the contact page. Prices and perks are easy
 * to tweak in the $tiers array below.
 *
 * @package The_Ranch_Hand
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

$registered = ! empty( $_GET['registered'] );
$contact    = home_url( '/contact/' );

$tiers = array(
	array(
		'key'      => 'basic',
		'name'     => 'Basic',
		'price'    => 50,
		'tagline'  => 'Get your job in front of nearby Hands.',
		'featured' => false,
		'features' => array(
			'Post one job',
			'Reach Ranch Hands within 30 miles',
			'Hands with basic qualifications',
		),
	),
	array(
		'key'      => 'plus',
		'name'     => 'Plus',
		'price'    => 99,
		'tagline'  => 'A wider reach and more trusted Hands.',
		'featured' => true,
		'features' => array(
			'Everything in Basic',
			'A wider search radius',
			'Hands with 500+ Trust Score',
			'1 free 24-hour Boost',
		),
	),
	array(
		'key'      => 'premium',
		'name'     => 'Premium',
		'price'    => 200,
		'tagline'  => 'Maximum reach and our most trusted Hands.',
		'featured' => false,
		'features' => array(
			'Everything in Plus',
			'An extra 24-hour Boost',
			'Hands with 1,000+ Trust Score',
			'Your job shared on our social media',
			'Priority placement &amp; more perks',
		),
	),
);
?>

<section class="section">
	<div class="container-rh" style="max-width:60rem;">
		<?php if ( $registered ) : ?>
			<div class="notice notice-success" style="text-align:center;">🎉 You're registered! Choose a plan below to post your job and start reaching Ranch Hands.</div>
		<?php endif; ?>

		<div class="text-center" style="max-width:40rem;margin:0 auto;">
			<span class="badge badge-hay">📋 Post a Job</span>
			<h1 class="display-xl mt-4">Pick a plan that fits your ranch.</h1>
			<p class="lead mt-4">Post your job and unlock access to Ranch Hands on the platform. The higher the tier, the wider your reach and the more trusted the Hands you can hire.</p>
		</div>

		<div class="grid grid-3 mt-10" style="align-items:stretch;">
			<?php foreach ( $tiers as $tier ) : ?>
				<div class="card<?php echo $tier['featured'] ? ' card-hover' : ''; ?>" style="display:flex;flex-direction:column;<?php echo $tier['featured'] ? 'border:2px solid var(--barn);box-shadow:var(--shadow-pop);' : ''; ?>">
					<?php if ( $tier['featured'] ) : ?>
						<span class="badge badge-hay" style="align-self:flex-start;">Most popular</span>
					<?php endif; ?>
					<h3 class="mt-2" style="font-size:1.5rem;"><?php echo esc_html( $tier['name'] ); ?></h3>
					<div style="margin:.5rem 0;">
						<span style="font-size:2.5rem;font-weight:800;color:var(--saddle-dark,#3a2a1a);">$<?php echo esc_html( $tier['price'] ); ?></span>
						<span class="muted"> / job posting</span>
					</div>
					<p class="muted" style="min-height:2.5rem;"><?php echo esc_html( $tier['tagline'] ); ?></p>
					<ul class="mt-4" style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:.6rem;flex:1;">
						<?php foreach ( $tier['features'] as $feature ) : ?>
							<li style="display:flex;gap:.5rem;align-items:flex-start;">
								<span aria-hidden="true" style="color:var(--barn);font-weight:800;">✓</span>
								<span><?php echo wp_kses( $feature, array() ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
					<a class="btn <?php echo $tier['featured'] ? 'btn-primary' : 'btn-secondary'; ?> mt-6" href="<?php echo esc_url( add_query_arg( 'plan', $tier['key'], $contact ) ); ?>" style="width:100%;">Choose <?php echo esc_html( $tier['name'] ); ?></a>
				</div>
			<?php endforeach; ?>
		</div>

		<p class="help text-center mt-6">Secure online checkout is on the way. For now, pick a plan and we'll get you set up and your job posted.</p>

		<!-- What's a Boost -->
		<div class="card mt-10" style="max-width:42rem;margin-left:auto;margin-right:auto;">
			<h3 style="font-size:1.25rem;">⚡ What's a Boost?</h3>
			<p class="muted mt-2">A Boost promotes your job posting across The Ranch Hand for 24 hours &mdash; bumping it to the top and featuring it so more Hands see it first. Think of it as a spotlight on your job when you need to fill it fast.</p>
		</div>
	</div>
</section>

<?php get_footer(); ?>
