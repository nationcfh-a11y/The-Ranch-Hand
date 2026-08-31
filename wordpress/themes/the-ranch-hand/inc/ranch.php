<?php
/**
 * Ranch accounts: the owner-side equivalent of a Hand account.
 *
 * A ranch that registers gets a WordPress user (role trh_ranch) with a username
 * and password so they can log back in, their signup answers stored as user
 * meta, and a PDF compiled from those answers that links from the sheet. Unlike
 * a Hand (whose profile is a `caretaker` post shown in the directory), a ranch
 * has no public listing yet, so user meta is enough.
 *
 * The signup form (page-ranch-signup.php) posts to trh_ranch_signup. Logging
 * back in reuses the shared front-end login on the dashboard (wp_signon works
 * for any role); only the post-login redirect is role-aware.
 *
 * @package The_Ranch_Hand
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Role slug for a Ranch (owner) account. */
const TRH_RANCH_ROLE = 'trh_ranch';

/* -------------------------------------------------------------------------
 * Role
 * ---------------------------------------------------------------------- */

add_action( 'after_setup_theme', 'trh_ensure_ranch_role' );
/** Create the Ranch role: `read` only, front-end account with no wp-admin powers. */
function trh_ensure_ranch_role() {
	if ( get_option( 'trh_ranch_role_v1' ) ) {
		return;
	}
	remove_role( TRH_RANCH_ROLE ); // keep re-runs idempotent
	add_role( TRH_RANCH_ROLE, 'Ranch', array( 'read' => true ) );
	update_option( 'trh_ranch_role_v1', 1 );
}

/* -------------------------------------------------------------------------
 * Role helpers (for the bulletin board R/H badge, redirects, etc.)
 * ---------------------------------------------------------------------- */

/** True if the user (default: current) is a Ranch account. */
function trh_is_ranch( $user_id = 0 ) {
	$user = $user_id ? get_userdata( $user_id ) : wp_get_current_user();
	return $user && in_array( TRH_RANCH_ROLE, (array) $user->roles, true );
}

/** True if the user (default: current) is a Hand account. */
function trh_is_hand_user( $user_id = 0 ) {
	$user = $user_id ? get_userdata( $user_id ) : wp_get_current_user();
	return $user && in_array( TRH_HAND_ROLE, (array) $user->roles, true );
}

/**
 * One-letter role badge for the bulletin board: 'R' for a ranch, 'H' for a
 * hand, '' otherwise. See trh_role_badge_html() for a styled chip.
 */
function trh_user_role_badge( $user_id = 0 ) {
	if ( trh_is_ranch( $user_id ) ) {
		return 'R';
	}
	if ( trh_is_hand_user( $user_id ) ) {
		return 'H';
	}
	return '';
}

/**
 * A small styled R/H chip to place next to a username in the bulletin board.
 * Returns '' for users who are neither a ranch nor a hand.
 */
function trh_role_badge_html( $user_id = 0 ) {
	$letter = trh_user_role_badge( $user_id );
	if ( '' === $letter ) {
		return '';
	}
	$is_ranch = ( 'R' === $letter );
	$label    = $is_ranch ? 'Ranch' : 'Ranch Hand';
	$bg       = $is_ranch ? '#7a3b2e' : '#3f6b3f';
	return sprintf(
		'<span class="trh-role-badge" title="%1$s" aria-label="%1$s" style="display:inline-flex;align-items:center;justify-content:center;min-width:1.25rem;height:1.25rem;padding:0 .25rem;border-radius:999px;background:%2$s;color:#fff;font-size:.7rem;font-weight:800;line-height:1;vertical-align:middle;">%3$s</span>',
		esc_attr( $label ),
		esc_attr( $bg ),
		esc_html( $letter )
	);
}

/** Where a logged-in ranch should land (no dedicated ranch dashboard yet). */
function trh_ranch_home_url() {
	return trh_page_url( 'ranch-plans' );
}

/** Keep a logged-in ranch off the Hand dashboard (which would show a Hand shell). */
add_action( 'template_redirect', 'trh_ranch_dashboard_redirect' );
function trh_ranch_dashboard_redirect() {
	if ( is_page( 'dashboard' ) && is_user_logged_in() && trh_is_ranch() ) {
		wp_safe_redirect( trh_ranch_home_url() );
		exit;
	}
}

/* -------------------------------------------------------------------------
 * Signup
 * ---------------------------------------------------------------------- */

/** Bounce back to the ranch signup form with errors + the submitted values. */
function trh_ranch_fail( $errors, $values = array() ) {
	$token = trh_signup_flash_set( (array) $errors, $values );
	wp_safe_redirect( add_query_arg( 'e', $token, trh_page_url( 'ranch-signup' ) ) . '#trh-signup-errors' );
	exit;
}

/** Merge an "Other" free-text value into a comma list, replacing a bare "Other". */
function trh_merge_other( $list, $other ) {
	$other = trim( (string) $other );
	if ( '' === $other ) {
		return $list;
	}
	$parts = array();
	foreach ( array_map( 'trim', explode( ',', (string) $list ) ) as $part ) {
		if ( '' !== $part && 0 !== strcasecmp( $part, 'Other' ) ) {
			$parts[] = $part;
		}
	}
	$parts[] = 'Other: ' . $other;
	return implode( ', ', $parts );
}

add_action( 'admin_post_nopriv_trh_ranch_signup', 'trh_handle_ranch_signup' );
add_action( 'admin_post_trh_ranch_signup', 'trh_handle_ranch_signup' );
function trh_handle_ranch_signup() {
	if ( ! wp_verify_nonce( trh_post_text( 'trh_ranch_nonce' ), 'trh_ranch_signup' ) ) {
		trh_ranch_fail( array( 'Your session timed out. Please fill the form in again.' ) );
	}
	if ( ! empty( $_POST['trh_website'] ) ) { // honeypot: bots fill this hidden field
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}

	$v = array(
		'name'           => trh_post_text( 'name' ),
		'email'          => sanitize_email( trh_post_raw( 'email' ) ),
		'phone'          => trh_post_text( 'phone' ),
		'farm_name'      => trh_post_text( 'farm_name' ),
		'location'       => trh_post_text( 'location' ),
		'acres'          => trh_post_text( 'acres' ),
		'frequency'      => trh_post_text( 'frequency' ),
		'start'          => trh_post_text( 'start' ),
		'username'       => trh_post_text( 'username' ),
		'animal_details' => sanitize_textarea_field( trh_post_raw( 'animal_details' ) ),
		'looking_for'    => sanitize_textarea_field( trh_post_raw( 'looking_for' ) ),
		'notes'          => sanitize_textarea_field( trh_post_raw( 'notes' ) ),
	);
	$animals  = trh_merge_other( trh_lead_checkbox_list( 'animals' ), trh_post_text( 'animals_other' ) );
	$needs    = trh_merge_other( trh_lead_checkbox_list( 'needs' ), trh_post_text( 'needs_other' ) );
	$password = trh_post_raw( 'password' );
	$confirm  = trh_post_raw( 'password_confirm' );

	$errors = array();
	if ( '' === $v['name'] ) {
		$errors[] = 'Please add your name.';
	}
	if ( ! is_email( $v['email'] ) ) {
		$errors[] = 'Please add a valid email address.';
	} elseif ( email_exists( $v['email'] ) ) {
		$errors[] = 'That email already has an account. <a href="' . esc_url( trh_dashboard_url() ) . '">Log in</a> instead.';
	}
	if ( '' === $v['location'] ) {
		$errors[] = 'Please add your location (city, state).';
	}
	if ( '' === $v['start'] ) {
		$errors[] = 'Please choose when you need to start.';
	}
	$username_problem = trh_username_problem( $v['username'] );
	if ( '' !== $username_problem ) {
		$errors[] = $username_problem;
	}
	if ( strlen( $password ) < 8 ) {
		$errors[] = 'Please choose a password of at least 8 characters.';
	} elseif ( $password !== $confirm ) {
		$errors[] = 'Those two passwords do not match.';
	}
	if ( empty( $_POST['agree'] ) ) {
		$errors[] = 'Please agree to the Terms of Service and Privacy Policy.';
	}

	if ( $errors ) {
		trh_ranch_fail( $errors, $v );
	}

	$user_id = wp_insert_user(
		array(
			'user_login'   => $v['username'],
			'user_pass'    => $password,
			'user_email'   => $v['email'],
			'display_name' => $v['name'],
			'role'         => TRH_RANCH_ROLE,
		)
	);
	if ( is_wp_error( $user_id ) ) {
		trh_ranch_fail( array( 'We could not create your account: ' . $user_id->get_error_message() ), $v );
	}

	// Store the answers as user meta.
	$meta = array(
		'trh_name'           => $v['name'],
		'trh_email'          => $v['email'],
		'trh_phone'          => $v['phone'],
		'trh_ranch_name'     => $v['farm_name'],
		'trh_location'       => $v['location'],
		'trh_acres'          => $v['acres'],
		'trh_animals'        => $animals,
		'trh_animal_details' => $v['animal_details'],
		'trh_needs'          => $needs,
		'trh_frequency'      => $v['frequency'],
		'trh_start'          => $v['start'],
		'trh_looking_for'    => $v['looking_for'],
		'trh_notes'          => $v['notes'],
		'trh_username'       => $v['username'],
	);
	foreach ( $meta as $key => $val ) {
		if ( '' !== $val ) {
			update_user_meta( $user_id, $key, $val );
		}
	}

	// Log them straight in.
	$user = get_user_by( 'id', $user_id );
	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id, true );
	do_action( 'wp_login', $user->user_login, $user );

	// Compile the answers into a PDF and mirror to the sheet's "Ranch" tab.
	$pdf_url = trh_store_ranch_pdf(
		$user_id,
		array(
			'name'           => $v['name'],
			'email'          => $v['email'],
			'phone'          => $v['phone'],
			'location'       => $v['location'],
			'search_radius'  => '',
			'farm_name'      => $v['farm_name'],
			'acres'          => $v['acres'],
			'animals'        => $animals,
			'animal_details' => $v['animal_details'],
			'needs'          => $needs,
			'frequency'      => $v['frequency'],
			'start'          => $v['start'],
			'looking_for'    => $v['looking_for'],
			'notes'          => $v['notes'],
		)
	);

	trh_mirror_to_sheet(
		'Ranch',
		array(
			'ID'             => (string) $user_id,
			'Full Name'      => $v['name'],
			'Email'          => $v['email'],
			'Role'           => 'owner',
			'Location'       => $v['location'],
			'Phone'          => $v['phone'],
			'Ranch Name'     => $v['farm_name'],
			'Property Size'  => $v['acres'],
			'Animals'        => $animals,
			'Animal Details' => $v['animal_details'],
			'Care Needed'    => $needs,
			'How Often'      => $v['frequency'],
			'Start'          => $v['start'],
			'Looking For'    => $v['looking_for'],
			'Notes'          => $v['notes'],
			'Username'       => $v['username'],
			'User Info'      => trh_sheet_link( $pdf_url, 'View Profile' ),
		)
	);

	// Notify the admin.
	$lines = array(
		'A new ranch registered on The Ranch Hand:',
		'',
		'Name:     ' . $v['name'],
		'Email:    ' . $v['email'],
		'Username: ' . $v['username'],
	);
	if ( $v['phone'] ) {
		$lines[] = 'Phone:    ' . $v['phone'];
	}
	if ( $v['location'] ) {
		$lines[] = 'Location: ' . $v['location'];
	}
	wp_mail(
		get_option( 'admin_email' ),
		'[The Ranch Hand] Ranch signup: ' . $v['name'],
		implode( "\n", $lines ),
		array( 'Reply-To: ' . $v['name'] . ' <' . $v['email'] . '>' )
	);

	wp_safe_redirect( add_query_arg( 'registered', '1', trh_ranch_home_url() ) );
	exit;
}
