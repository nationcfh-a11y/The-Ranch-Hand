<?php
/**
 * Lead capture. Until the real booking engine exists (phase 2), the site
 * collects two kinds of leads (booking requests and caretaker applications),
 * stores each as a private "trh_lead" post (so nothing is ever lost) and
 * emails the site admin. Fully self-contained; no third-party form plugin.
 *
 * @package The_Ranch_Hand
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Private CPT that holds every submission, viewable in wp-admin → Leads. */
add_action( 'init', 'trh_register_lead_cpt' );
function trh_register_lead_cpt() {
	register_post_type(
		'trh_lead',
		array(
			'labels'          => array( 'name' => 'Leads', 'singular_name' => 'Lead', 'menu_name' => 'Leads' ),
			'public'          => false,
			'show_ui'         => true,
			'menu_icon'       => 'dashicons-email-alt',
			'supports'        => array( 'title' ),
			'capability_type' => 'post',
		)
	);
}

/** Handle both form types via admin-post.php (logged-in + logged-out visitors). */
add_action( 'admin_post_nopriv_trh_lead', 'trh_handle_lead' );
add_action( 'admin_post_trh_lead', 'trh_handle_lead' );
function trh_handle_lead() {
	// Nonce + honeypot.
	if ( ! isset( $_POST['trh_lead_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['trh_lead_nonce'] ) ), 'trh_lead' ) ) {
		trh_redirect_back( 'error' );
	}
	if ( ! empty( $_POST['trh_website'] ) ) { // honeypot: bots fill this hidden field
		trh_redirect_back( 'ok' ); // pretend success, drop silently
	}

	$type      = isset( $_POST['lead_type'] ) ? sanitize_key( $_POST['lead_type'] ) : 'booking';
	$name      = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$email     = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$phone     = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$location  = isset( $_POST['location'] ) ? sanitize_text_field( wp_unslash( $_POST['location'] ) ) : '';
	$service   = isset( $_POST['service'] ) ? sanitize_text_field( wp_unslash( $_POST['service'] ) ) : '';
	$dates     = isset( $_POST['dates'] ) ? sanitize_text_field( wp_unslash( $_POST['dates'] ) ) : '';
	$caretaker = isset( $_POST['caretaker'] ) ? sanitize_text_field( wp_unslash( $_POST['caretaker'] ) ) : '';
	$message   = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

	if ( empty( $name ) || empty( $email ) || ! is_email( $email ) ) {
		trh_redirect_back( 'invalid' );
	}

	$is_application = ( 'application' === $type );
	$title = ( $is_application ? 'Application: ' : 'Booking request: ' ) . $name;

	$lead_id = wp_insert_post(
		array(
			'post_type'   => 'trh_lead',
			'post_status' => 'private',
			'post_title'  => $title,
		)
	);
	if ( $lead_id && ! is_wp_error( $lead_id ) ) {
		foreach ( compact( 'type', 'name', 'email', 'phone', 'location', 'service', 'dates', 'caretaker', 'message' ) as $k => $v ) {
			if ( '' !== $v ) {
				update_post_meta( $lead_id, 'trh_' . $k, $v );
			}
		}
	}

	// Notify the admin (fail-soft; a mail error never blocks the thank-you).
	$lines = array(
		'A new ' . ( $is_application ? 'caretaker application' : 'booking request' ) . ' came in from The Ranch Hand:',
		'',
		'Name:     ' . $name,
		'Email:    ' . $email,
	);
	if ( $phone )     { $lines[] = 'Phone:    ' . $phone; }
	if ( $location )  { $lines[] = 'Location: ' . $location; }
	if ( $caretaker ) { $lines[] = 'Sitter:   ' . $caretaker; }
	if ( $service )   { $lines[] = 'Service:  ' . $service; }
	if ( $dates )     { $lines[] = 'Dates:    ' . $dates; }
	if ( $message )   { $lines[] = ''; $lines[] = 'Message:'; $lines[] = $message; }

	wp_mail(
		get_option( 'admin_email' ),
		'[The Ranch Hand] ' . $title,
		implode( "\n", $lines ),
		array( 'Reply-To: ' . $name . ' <' . $email . '>' )
	);

	trh_redirect_back( 'ok' );
}

/** Redirect to the referring page with a status flag the templates read. */
function trh_redirect_back( $status ) {
	$back = wp_get_referer();
	if ( ! $back ) {
		$back = home_url( '/' );
	}
	wp_safe_redirect( add_query_arg( 'trh_lead', $status, remove_query_arg( 'trh_lead', $back ) ) );
	exit;
}

/** Render a success/error notice from the ?trh_lead= flag. Call at top of forms. */
function trh_lead_notice() {
	if ( empty( $_GET['trh_lead'] ) ) {
		return;
	}
	$status = sanitize_key( wp_unslash( $_GET['trh_lead'] ) );
	if ( 'ok' === $status ) {
		echo '<div class="notice notice-success">Thank you, your request is in! We\'ll be in touch by email shortly.</div>';
	} elseif ( 'invalid' === $status ) {
		echo '<div class="notice notice-error">Please add your name and a valid email so we can reach you.</div>';
	} elseif ( 'error' === $status ) {
		echo '<div class="notice notice-error">Something went wrong sending your request. Please try again.</div>';
	}
}
