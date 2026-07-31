<?php
/**
 * Single caretaker profile — bio, experience, animals, services & rates,
 * availability, and a booking-request form. Ports CaretakerProfile.jsx (the
 * live booking flow becomes a lead form until the phase-2 engine ships).
 *
 * @package The_Ranch_Hand
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

while ( have_posts() ) :
	the_post();
	$pid       = get_the_ID();
	$name      = get_the_title();
	$photo     = trh_caretaker_photo( $pid );
	$location  = trh_caretaker_location( $pid );
	$exp       = trh_caretaker_experience( $pid );
	$headline  = trh_caretaker_headline( $pid );
	$avail     = trh_caretaker_availability( $pid );
	$rating    = trh_caretaker_rating( $pid );
	$reviews   = trh_caretaker_review_count( $pid );
	$rates     = trh_caretaker_rates( $pid );
	$animals   = wp_get_object_terms( $pid, 'animal', array( 'fields' => 'names' ) );
	?>

	<section class="page-hero">
		<div class="container-rh">
			<a href="<?php echo esc_url( trh_directory_url() ); ?>" class="muted" style="font-weight:700;">← Back to all sitters</a>
			<div class="profile-head mt-6">
				<img class="profile-photo" src="<?php echo esc_url( $photo ); ?>" alt="<?php echo esc_attr( $name ); ?>" />
				<div>
					<h1 class="display-lg"><?php echo esc_html( $name ); ?></h1>
					<p class="muted mt-2">📍 <?php echo esc_html( $location ); ?> · <?php echo esc_html( $exp ); ?> yrs experience</p>
					<div class="mt-2"><?php trh_stars( $rating, $reviews ); ?></div>
					<p class="mt-3" style="font-size:1.125rem;color:var(--saddle-dark);font-weight:600;"><?php echo esc_html( $headline ); ?></p>
					<div class="ck-tags mt-3">
						<?php foreach ( (array) $animals as $a ) : ?>
							<span class="badge badge-service"><?php echo esc_html( $a ); ?></span>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section class="section">
		<div class="container-rh profile-grid">
			<!-- Main column -->
			<div>
				<div class="card">
					<h2 class="display-md">About <?php echo esc_html( strtok( $name, ' ' ) ); ?></h2>
					<div class="mt-3" style="line-height:1.7;">
						<?php the_content(); ?>
					</div>
				</div>

				<?php if ( $avail ) : ?>
					<div class="card mt-6">
						<h2 class="display-md">Availability</h2>
						<p class="mt-2"><span class="badge badge-sage">🟢 <?php echo esc_html( $avail ); ?></span></p>
					</div>
				<?php endif; ?>

				<div class="card mt-6">
					<h2 class="display-md">Reviews</h2>
					<div class="mt-2 flex-between" style="align-items:center;">
						<?php trh_stars( $rating, $reviews ); ?>
						<span class="muted" style="font-size:.875rem;"><?php echo esc_html( $reviews ); ?> verified reviews</span>
					</div>
					<p class="muted mt-3" style="font-size:.9375rem;">Reviews are imported from The Ranch Hand's verified booking history. Full review text lands with the phase-2 booking engine.</p>
				</div>
			</div>

			<!-- Sidebar: rates + booking request -->
			<aside>
				<div class="card" style="position:sticky;top:88px;">
					<h2 class="display-md">Services &amp; rates</h2>
					<div class="mt-3">
						<?php if ( $rates ) : ?>
							<?php foreach ( $rates as $key => $price ) : ?>
								<div class="rate-row">
									<span>
										<span style="font-weight:700;color:var(--saddle-dark);"><?php echo esc_html( trh_service_label( $key ) ); ?></span><br />
										<span class="muted" style="font-size:.8125rem;"><?php echo esc_html( trh_service_unit( $key ) ); ?></span>
									</span>
									<span class="ck-price"><?php echo esc_html( trh_money( $price ) ); ?></span>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>

					<hr style="border:none;border-top:1px solid var(--line);margin:1.25rem 0;" />

					<h3 style="font-size:1.125rem;">Request a booking</h3>
					<p class="muted" style="font-size:.8125rem;margin-top:.25rem;">Send <?php echo esc_html( strtok( $name, ' ' ) ); ?> your dates and details. No charge — we'll connect you by email.</p>

					<?php trh_lead_notice(); ?>

					<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" style="margin-top:1rem;">
						<input type="hidden" name="action" value="trh_lead" />
						<input type="hidden" name="lead_type" value="booking" />
						<input type="hidden" name="caretaker" value="<?php echo esc_attr( $name ); ?>" />
						<?php wp_nonce_field( 'trh_lead', 'trh_lead_nonce' ); ?>
						<p style="position:absolute;left:-9999px;" aria-hidden="true"><label>Website<input type="text" name="trh_website" tabindex="-1" autocomplete="off" /></label></p>

						<div class="field">
							<label class="label" for="bk-name">Your name</label>
							<input class="input" type="text" id="bk-name" name="name" required />
						</div>
						<div class="field">
							<label class="label" for="bk-email">Email</label>
							<input class="input" type="email" id="bk-email" name="email" required />
						</div>
						<div class="field">
							<label class="label" for="bk-service">Service</label>
							<select class="select" id="bk-service" name="service">
								<?php foreach ( $rates as $key => $price ) : ?>
									<option value="<?php echo esc_attr( trh_service_label( $key ) ); ?>"><?php echo esc_html( trh_service_label( $key ) . ' — ' . trh_money( $price ) . ' ' . trh_service_unit( $key ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="field">
							<label class="label" for="bk-dates">Dates</label>
							<input class="input" type="text" id="bk-dates" name="dates" placeholder="e.g. Jun 20–23" />
						</div>
						<div class="field">
							<label class="label" for="bk-msg">Care details</label>
							<textarea class="textarea" id="bk-msg" name="message" placeholder="Animals, feeding, meds, anything the sitter should know…"></textarea>
						</div>
						<button class="btn btn-primary" type="submit" style="width:100%;">Send booking request</button>
					</form>
				</div>
			</aside>
		</div>
	</section>

	<?php
endwhile;
get_footer();
