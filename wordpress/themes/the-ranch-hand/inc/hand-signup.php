<?php
/**
 * The three-step Hand signup: form handlers, validation, uploads, and the
 * front-end login/logout used by the dashboard.
 *
 * Each step posts to admin-post.php and redirects, so a refresh never re-submits
 * and validation errors survive the round trip (carried in a short-lived
 * transient keyed by a token in the URL, see trh_signup_flash_set()).
 *
 * Step 1 creates the account and the profile post immediately and logs the
 * person in; steps 2 and 3 then just update the profile they already own. That
 * means a half-finished signup is never lost: they can come back, log in, and
 * the dashboard picks up exactly where they stopped.
 *
 * The data model, Trust Score engine, and checklist live in inc/hands.php.
 *
 * @package The_Ranch_Hand
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Upload ceilings, in bytes. */
const TRH_MAX_RESUME_BYTES = 10485760; // 10 MB
const TRH_MAX_PHOTO_BYTES  = 12582912; // 12 MB

/* -------------------------------------------------------------------------
 * Flash messages across the POST -> redirect -> GET hop
 * ---------------------------------------------------------------------- */

/**
 * Stash validation errors + the values that were submitted.
 *
 * @param string[] $errors Human-readable messages.
 * @param array    $values Sanitized submission, used to repopulate the form.
 * @return string Token to put in the redirect URL.
 */
function trh_signup_flash_set( $errors, $values = array() ) {
	// Lowercase hex on purpose. The read side has to sanitize the token coming
	// back in the URL, and every sanitizer worth using lowercases, so a
	// mixed-case token can never be looked up again.
	$token = bin2hex( random_bytes( 12 ) );
	set_transient( 'trh_signup_' . $token, array( 'errors' => $errors, 'values' => $values ), 20 * MINUTE_IN_SECONDS );
	return $token;
}

/** Read (and cache) the flash payload for this request. */
function trh_signup_flash() {
	static $flash = null;
	if ( null !== $flash ) {
		return $flash;
	}
	$flash = array( 'errors' => array(), 'values' => array() );
	if ( empty( $_GET['e'] ) || ! is_scalar( $_GET['e'] ) ) {
		return $flash;
	}

	$token = strtolower( trim( (string) wp_unslash( $_GET['e'] ) ) );
	if ( ! preg_match( '/^[a-f0-9]{24}$/', $token ) ) {
		return $flash;
	}

	$data = get_transient( 'trh_signup_' . $token );
	if ( is_array( $data ) ) {
		$flash = wp_parse_args( $data, $flash );
		return $flash;
	}

	// The token is well formed but the payload is gone: expired, or the object
	// cache dropped it. Never leave someone staring at a form that looks like it
	// did nothing when they pressed the button.
	$flash['errors'] = array( 'We could not save that. Please check your details and send the form again.' );
	return $flash;
}

/** Error messages to show above the current step's form. */
function trh_signup_errors() {
	$flash = trh_signup_flash();
	return is_array( $flash['errors'] ) ? $flash['errors'] : array();
}

/** A previously submitted value, for repopulating a field after an error. */
function trh_signup_value( $key, $fallback = '' ) {
	$flash = trh_signup_flash();
	if ( isset( $flash['values'][ $key ] ) && '' !== $flash['values'][ $key ] ) {
		return $flash['values'][ $key ];
	}
	return $fallback;
}

/** Render the error block. */
function trh_signup_error_notice() {
	$errors = trh_signup_errors();
	if ( ! $errors ) {
		return;
	}
	echo '<div class="notice notice-error" id="trh-signup-errors" tabindex="-1"><ul class="notice-list">';
	foreach ( $errors as $error ) {
		echo '<li>' . wp_kses( $error, array( 'a' => array( 'href' => array() ), 'strong' => array() ) ) . '</li>';
	}
	echo '</ul></div>';
}

/** Bounce back to a step with errors attached. */
function trh_signup_fail( $step, $errors, $values = array() ) {
	$token = trh_signup_flash_set( (array) $errors, $values );
	// The fragment matters: without it the bounce looks identical to "the button
	// did nothing", because the notice can be below the fold on a long step.
	wp_safe_redirect( add_query_arg( 'e', $token, trh_signup_url( $step ) ) . '#trh-signup-errors' );
	exit;
}

/* -------------------------------------------------------------------------
 * Shared sanitizing helpers
 * ---------------------------------------------------------------------- */

/**
 * POST field as a raw string, or '' if it is missing or is not a scalar.
 *
 * The scalar check matters: a crafted request can send any field as an array,
 * and some of core's sanitizers (sanitize_email() among them) fatal on one.
 */
function trh_post_raw( $key ) {
	return isset( $_POST[ $key ] ) && is_scalar( $_POST[ $key ] ) ? (string) wp_unslash( $_POST[ $key ] ) : '';
}

/** POST field as plain text. */
function trh_post_text( $key ) {
	return sanitize_text_field( trh_post_raw( $key ) );
}

/** One value out of a POSTed array field, as plain text. */
function trh_row_text( $row, $key ) {
	return isset( $row[ $key ] ) && is_scalar( $row[ $key ] ) ? sanitize_text_field( wp_unslash( $row[ $key ] ) ) : '';
}

/** Digits in a phone number, for a "did they type a real number" check. */
function trh_phone_digits( $phone ) {
	return preg_replace( '/\D+/', '', (string) $phone );
}

/** Normalize a pasted profile URL ("instagram.com/x" -> "https://instagram.com/x"). */
function trh_normalize_url( $raw ) {
	$raw = trim( (string) $raw );
	if ( '' === $raw ) {
		return '';
	}
	if ( ! preg_match( '#^https?://#i', $raw ) ) {
		$raw = 'https://' . ltrim( $raw, '/' );
	}
	$url  = esc_url_raw( $raw );
	$host = wp_parse_url( $url, PHP_URL_HOST );
	return $host ? $url : '';
}

/**
 * Check a username the Hand chose for themselves.
 *
 * Stricter than core's validate_username() on purpose: core permits spaces and
 * punctuation that make a login name awkward to type and to say over the phone.
 * A username is permanent once the account exists, so it is worth being fussy
 * here rather than apologetic later.
 *
 * @return string Error message, or '' when the name is usable.
 */
function trh_username_problem( $username ) {
	if ( '' === $username ) {
		return 'Please choose a username.';
	}
	if ( ! preg_match( '/^[A-Za-z0-9][A-Za-z0-9._-]{2,29}$/', $username ) ) {
		return 'A username needs to be 3 to 30 characters and can use only letters, numbers, periods, hyphens, and underscores, starting with a letter or number.';
	}
	if ( ! validate_username( $username ) ) {
		return 'That username is not allowed here. Please pick a different one.';
	}
	if ( username_exists( $username ) ) {
		return 'The username <strong>' . esc_html( $username ) . '</strong> is already taken. Please pick another, or <a href="' . esc_url( trh_dashboard_url() ) . '">sign in</a> if it is yours.';
	}
	return '';
}

/** The logged-in Hand's profile, or redirect them to the start of signup. */
function trh_require_hand_profile() {
	$post_id = trh_hand_profile_id();
	if ( ! $post_id ) {
		wp_safe_redirect( trh_signup_url( 1 ) );
		exit;
	}
	return $post_id;
}

/** Verify a step's nonce or bail with a friendly error. */
function trh_signup_check_nonce( $step ) {
	$field = 'trh_hand_nonce';
	$ok    = wp_verify_nonce( trh_post_text( $field ), 'trh_hand_step' . $step );
	if ( ! $ok ) {
		trh_signup_fail( $step, array( 'Your session timed out. Please fill the form in again.' ) );
	}
}

/* -------------------------------------------------------------------------
 * Step 1: account + contact details
 * ---------------------------------------------------------------------- */

add_action( 'admin_post_nopriv_trh_hand_step1', 'trh_handle_hand_step1' );
add_action( 'admin_post_trh_hand_step1', 'trh_handle_hand_step1' );
function trh_handle_hand_step1() {
	trh_signup_check_nonce( 1 );

	// Honeypot: silently treat bots as success.
	if ( ! empty( $_POST['trh_website'] ) ) {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}

	$values = array(
		'first_name' => trh_post_text( 'first_name' ),
		'last_name'  => trh_post_text( 'last_name' ),
		'phone'      => trh_post_text( 'phone' ),
		'email'      => sanitize_email( trh_post_raw( 'email' ) ),
		'city'       => trh_post_text( 'city' ),
		'state'      => strtoupper( trh_post_text( 'state' ) ),
		'zip'        => trh_post_text( 'zip' ),
		'username'   => trh_post_text( 'username' ),
	);
	$password = trh_post_raw( 'password' );
	$confirm  = trh_post_raw( 'password_confirm' );
	$states   = trh_us_states();
	$errors   = array();

	if ( '' === $values['first_name'] ) {
		$errors[] = 'Please add your first name.';
	}
	if ( '' === $values['last_name'] ) {
		$errors[] = 'Please add your last name.';
	}
	if ( strlen( trh_phone_digits( $values['phone'] ) ) < 10 ) {
		$errors[] = 'Please add a phone number with area code so owners can reach you.';
	}
	if ( ! is_email( $values['email'] ) ) {
		$errors[] = 'Please add a valid email address.';
	} elseif ( email_exists( $values['email'] ) ) {
		$errors[] = 'That email already has an account. <a href="' . esc_url( trh_dashboard_url() ) . '">Log in</a> to pick up where you left off.';
	}
	if ( '' === $values['city'] ) {
		$errors[] = 'Please add the city or town you work out of.';
	}
	if ( ! isset( $states[ $values['state'] ] ) ) {
		$errors[] = 'Please confirm your state from the list.';
	}
	if ( '' !== $values['zip'] && ! preg_match( '/^\d{5}$/', $values['zip'] ) ) {
		$errors[] = 'A ZIP code should be five digits, or you can leave it blank.';
	}
	$username_problem = trh_username_problem( $values['username'] );
	if ( '' !== $username_problem ) {
		$errors[] = $username_problem;
	}
	if ( strlen( $password ) < 8 ) {
		$errors[] = 'Please choose a password of at least 8 characters.';
	} elseif ( $password !== $confirm ) {
		// Worth catching: there is no self-service password reset yet, so a typo
		// here would lock the Hand out of their own profile.
		$errors[] = 'Those two passwords do not match.';
	}
	if ( empty( $_POST['agree'] ) ) {
		$errors[] = 'Please agree to the Terms of Service and Privacy Policy.';
	}

	if ( $errors ) {
		trh_signup_fail( 1, $errors, $values );
	}

	$user_id = wp_insert_user(
		array(
			'user_login'   => $values['username'],
			'user_pass'    => $password,
			'user_email'   => $values['email'],
			'first_name'   => $values['first_name'],
			'last_name'    => $values['last_name'],
			'display_name' => $values['first_name'] . ' ' . $values['last_name'],
			'role'         => TRH_HAND_ROLE,
		)
	);
	if ( is_wp_error( $user_id ) ) {
		trh_signup_fail( 1, array( 'We could not create your account: ' . $user_id->get_error_message() ), $values );
	}

	$name    = $values['first_name'] . ' ' . $values['last_name'];
	$post_id = wp_insert_post(
		array(
			'post_type'      => 'caretaker',
			'post_status'    => 'pending', // held back until an admin approves
			'post_title'     => $name,
			'post_author'    => $user_id,
			'post_content'   => '',
			'comment_status' => 'closed',
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $user_id ); // do not leave an account with no profile
		trh_signup_fail( 1, array( 'We could not start your profile. Please try again.' ), $values );
	}

	$location = $values['city'] . ', ' . $values['state'];
	update_post_meta( $post_id, 'trh_user_id', $user_id );
	update_post_meta( $post_id, 'trh_first_name', $values['first_name'] );
	update_post_meta( $post_id, 'trh_last_name', $values['last_name'] );
	update_post_meta( $post_id, 'trh_email', $values['email'] );
	update_post_meta( $post_id, 'trh_username', $values['username'] );
	update_post_meta( $post_id, 'trh_phone', $values['phone'] );
	update_post_meta( $post_id, 'trh_city', $values['city'] );
	update_post_meta( $post_id, 'trh_state', $values['state'] );
	update_post_meta( $post_id, 'trh_location', $location );
	if ( '' !== $values['zip'] ) {
		update_post_meta( $post_id, 'trh_zip', $values['zip'] );
	}
	update_post_meta( $post_id, 'trh_signup_step', 1 );
	update_post_meta( $post_id, 'trh_headline', 'Ranch Hand in ' . $location );
	update_user_meta( $user_id, 'trh_caretaker_id', $post_id );

	trh_recalculate_trust_score( $post_id );

	// Log them straight in so steps 2 and 3 are authenticated.
	$user = get_user_by( 'id', $user_id );
	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id, true );
	do_action( 'wp_login', $user->user_login, $user );

	// Mirror the new Hand into the "Hand" tab of the Google Sheet (see
	// inc/sheet.php). Fires here at step 1 so a Hand who stops before finishing
	// is still captured. Fail-soft: the WordPress account stays the record of
	// truth, and role 'caretaker' routes the row to the Hand tab.
	trh_mirror_signup_to_sheet(
		array(
			'name'     => $name,
			'email'    => $values['email'],
			'role'     => 'caretaker',
			'location' => $location,
		)
	);

	trh_notify_admin_new_hand( $post_id, 'started' );
	trh_email_hand_welcome( $post_id );

	wp_safe_redirect( trh_signup_url( 2 ) );
	exit;
}

/* -------------------------------------------------------------------------
 * Step 2: resume, photo, socials, references
 * ---------------------------------------------------------------------- */

add_action( 'admin_post_nopriv_trh_hand_step2', 'trh_handle_hand_step2' );
add_action( 'admin_post_trh_hand_step2', 'trh_handle_hand_step2' );
function trh_handle_hand_step2() {
	trh_signup_check_nonce( 2 );
	$post_id = trh_require_hand_profile();
	$errors  = array();

	// Resume.
	$resume = trh_store_hand_upload( 'resume', 'resume', $post_id );
	if ( is_wp_error( $resume ) ) {
		$errors[] = $resume->get_error_message();
	} elseif ( $resume ) {
		$previous = (int) trh_hand_field( $post_id, 'resume_id' );
		if ( $previous && $previous !== $resume ) {
			wp_delete_attachment( $previous, true );
		}
		update_post_meta( $post_id, 'trh_resume_id', $resume );
	}

	// Profile picture -> featured image of the profile.
	$photo = trh_store_hand_upload( 'photo', 'photo', $post_id );
	if ( is_wp_error( $photo ) ) {
		$errors[] = $photo->get_error_message();
	} elseif ( $photo ) {
		$previous = (int) get_post_thumbnail_id( $post_id );
		set_post_thumbnail( $post_id, $photo );
		if ( $previous && $previous !== $photo ) {
			wp_delete_attachment( $previous, true );
		}
	}

	// Socials.
	$socials    = array();
	$social_in  = isset( $_POST['social'] ) && is_array( $_POST['social'] ) ? $_POST['social'] : array();
	$networks   = trh_social_networks();
	foreach ( array_keys( $networks ) as $key ) {
		$raw = isset( $social_in[ $key ] ) && is_scalar( $social_in[ $key ] ) ? wp_unslash( $social_in[ $key ] ) : '';
		$url = trh_normalize_url( $raw );
		if ( '' !== trim( (string) $raw ) && '' === $url ) {
			$errors[] = 'That does not look like a working link for ' . $networks[ $key ]['label'] . '.';
			continue;
		}
		if ( $url ) {
			$socials[ $key ] = $url;
		}
	}
	update_post_meta( $post_id, 'trh_socials', $socials );

	// References.
	$references = array();
	$rows       = isset( $_POST['ref'] ) && is_array( $_POST['ref'] ) ? $_POST['ref'] : array();
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$ref = array(
			'name'         => trh_row_text( $row, 'name' ),
			'relationship' => trh_row_text( $row, 'relationship' ),
			'phone'        => trh_row_text( $row, 'phone' ),
			'email'        => sanitize_email( trh_row_text( $row, 'email' ) ),
		);
		if ( '' === $ref['name'] ) {
			continue; // an untouched row, not an error
		}
		if ( '' === $ref['phone'] && '' === $ref['email'] ) {
			$errors[] = 'Add a phone number or an email for your reference ' . $ref['name'] . ', so we can reach them.';
			continue;
		}
		$references[] = $ref;
	}
	update_post_meta( $post_id, 'trh_references', $references );

	if ( trh_hand_step_done( $post_id ) < 2 ) {
		update_post_meta( $post_id, 'trh_signup_step', 2 );
	}
	trh_recalculate_trust_score( $post_id );

	if ( $errors ) {
		trh_signup_fail( 2, $errors );
	}

	wp_safe_redirect( trh_signup_after_url( 3 ) );
	exit;
}

/* -------------------------------------------------------------------------
 * Step 3: experience checklist
 * ---------------------------------------------------------------------- */

add_action( 'admin_post_nopriv_trh_hand_step3', 'trh_handle_hand_step3' );
add_action( 'admin_post_trh_hand_step3', 'trh_handle_hand_step3' );
function trh_handle_hand_step3() {
	trh_signup_check_nonce( 3 );
	$post_id = trh_require_hand_profile();

	$valid    = array_keys( trh_experience_labels() );
	$posted   = isset( $_POST['experience'] ) && is_array( $_POST['experience'] ) ? $_POST['experience'] : array();
	$posted   = array_map( 'sanitize_key', array_filter( wp_unslash( $posted ), 'is_scalar' ) );
	$selected = array_values( array_intersect( $posted, $valid ) );

	if ( count( $selected ) < TRH_EXPERIENCE_MIN ) {
		trh_signup_fail(
			3,
			array( sprintf( 'Please check at least %d things you have experience with, so owners know what you can handle.', TRH_EXPERIENCE_MIN ) )
		);
	}

	$years = max( 0, min( 60, (int) trh_post_raw( 'experience_years' ) ) );
	$about = sanitize_textarea_field( trh_post_raw( 'about' ) );

	update_post_meta( $post_id, 'trh_experience', $selected );
	update_post_meta( $post_id, 'trh_experience_years', $years );

	$headline = trh_hand_headline( $post_id, $years );
	wp_update_post(
		array(
			'ID'           => $post_id,
			'post_content' => $about,
			'post_excerpt' => $headline,
		)
	);
	update_post_meta( $post_id, 'trh_headline', $headline );

	// Tag the animal taxonomy from the checklist so the directory filters work.
	wp_set_object_terms( $post_id, trh_animals_from_experience( $selected ), 'animal' );

	$already_complete = trh_hand_is_complete( $post_id );
	update_post_meta( $post_id, 'trh_signup_step', 3 );
	$score = trh_recalculate_trust_score( $post_id );

	if ( ! $already_complete ) {
		trh_notify_admin_new_hand( $post_id, 'completed' );
		trh_email_hand_complete( $post_id, $score );
		wp_safe_redirect( add_query_arg( 'welcome', '1', trh_dashboard_url() ) );
		exit;
	}

	wp_safe_redirect( add_query_arg( 'saved', '1', trh_dashboard_url() ) );
	exit;
}

/**
 * Where to go after a saved step: onward through the wizard, or straight back
 * to the dashboard when the Hand came from there to top up their score.
 */
function trh_signup_after_url( $next_step ) {
	$after = trh_post_text( 'after' );
	if ( 'dashboard' === $after ) {
		return add_query_arg( 'saved', '1', trh_dashboard_url() );
	}
	return trh_signup_url( $next_step );
}

/** A short headline for the directory card, derived from what they told us. */
function trh_hand_headline( $post_id, $years ) {
	$location = trh_hand_field( $post_id, 'location' );
	if ( $years >= 1 ) {
		$headline = sprintf( '%d %s with horses and farm animals', $years, 1 === $years ? 'year' : 'years' );
	} else {
		$headline = 'Hands-on horse and farm animal care';
	}
	return $location ? $headline . ' in ' . $location : $headline;
}

/**
 * Map checklist keys onto the `animal` taxonomy terms the directory filters by.
 *
 * @param string[] $selected
 * @return string[] Term names.
 */
function trh_animals_from_experience( $selected ) {
	$map = array(
		'cattle'   => 'Cattle',
		'goats'    => 'Goats/Sheep',
		'pigs'     => 'Pigs',
		'poultry'  => 'Poultry',
	);
	$terms = array();
	foreach ( $map as $key => $term ) {
		if ( in_array( $key, $selected, true ) ) {
			$terms[] = $term;
		}
	}

	// Anything from the horse or medical groups means they handle horses.
	$horse_keys = array_merge(
		array_keys( trh_experience_groups()['Horse handling']['items'] ),
		array_keys( trh_experience_groups()['Babies & breeding']['items'] )
	);
	if ( array_intersect( $horse_keys, $selected ) ) {
		array_unshift( $terms, 'Horses' );
	}
	if ( count( $terms ) >= 3 ) {
		$terms[] = 'Mixed Farm';
	}
	return $terms ? $terms : array( 'Horses' );
}

/* -------------------------------------------------------------------------
 * Uploads
 * ---------------------------------------------------------------------- */

/**
 * Validate and store one uploaded file as an attachment on the profile.
 *
 * Capabilities are deliberately not consulted: a Hand has no upload_files cap,
 * and this handler is the only path in, so the allowlist below is the whole
 * policy. Resumes get a random filename because attachment URLs are public and
 * a resume carries personal contact details.
 *
 * @param string $field  $_FILES key.
 * @param string $kind   'resume' or 'photo'.
 * @param int    $post_id Profile post the attachment belongs to.
 * @return int|WP_Error|false Attachment ID, an error, or false when no file was sent.
 */
function trh_store_hand_upload( $field, $kind, $post_id ) {
	if ( empty( $_FILES[ $field ] ) || ! isset( $_FILES[ $field ]['error'] ) ) {
		return false;
	}
	$file = $_FILES[ $field ];
	if ( UPLOAD_ERR_NO_FILE === (int) $file['error'] ) {
		return false;
	}
	if ( UPLOAD_ERR_INI_SIZE === (int) $file['error'] || UPLOAD_ERR_FORM_SIZE === (int) $file['error'] ) {
		return new WP_Error( 'too_big', 'That file is larger than this site accepts. Please upload a smaller one.' );
	}
	if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
		return new WP_Error( 'upload_failed', 'That file did not finish uploading. Please try again.' );
	}

	if ( 'resume' === $kind ) {
		$mimes = array(
			'pdf'  => 'application/pdf',
			'doc'  => 'application/msword',
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'odt'  => 'application/vnd.oasis.opendocument.text',
			'rtf'  => 'application/rtf',
			'txt'  => 'text/plain',
		);
		$max   = TRH_MAX_RESUME_BYTES;
		$label = 'A resume needs to be a PDF, Word, or text file.';
	} else {
		$mimes = array(
			'jpg|jpeg' => 'image/jpeg',
			'png'      => 'image/png',
			'webp'     => 'image/webp',
			'gif'      => 'image/gif',
		);
		$max   = TRH_MAX_PHOTO_BYTES;
		$label = 'A profile picture needs to be a JPG, PNG, WebP, or GIF image.';
	}

	if ( (int) $file['size'] > $max ) {
		return new WP_Error( 'too_big', sprintf( 'That file is %s. Please keep it under %s.', size_format( (int) $file['size'] ), size_format( $max ) ) );
	}

	$checked = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $mimes );
	if ( empty( $checked['ext'] ) || empty( $checked['type'] ) ) {
		return new WP_Error( 'bad_type', $label );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$overrides = array(
		'test_form' => false,
		'mimes'     => $mimes,
	);
	if ( 'resume' === $kind ) {
		$overrides['unique_filename_callback'] = 'trh_opaque_filename';
	}

	$moved = wp_handle_upload( $file, $overrides );
	if ( ! is_array( $moved ) || ! empty( $moved['error'] ) ) {
		return new WP_Error( 'upload_failed', is_array( $moved ) && ! empty( $moved['error'] ) ? $moved['error'] : 'We could not save that file.' );
	}

	$title         = 'resume' === $kind ? trh_hand_name( $post_id ) . ' resume' : trh_hand_name( $post_id );
	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => $moved['type'],
			'post_title'     => $title,
			'post_content'   => '',
			'post_status'    => 'inherit',
			'post_author'    => (int) trh_hand_field( $post_id, 'user_id' ),
		),
		$moved['file'],
		$post_id,
		true
	);
	if ( is_wp_error( $attachment_id ) ) {
		wp_delete_file( $moved['file'] );
		return $attachment_id;
	}

	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $moved['file'] ) );

	return (int) $attachment_id;
}

/**
 * Filename callback that throws away the original name. Attachment URLs are
 * guessable by design; "jane-doe-resume.pdf" should not be.
 */
function trh_opaque_filename( $dir, $name, $ext ) {
	return 'rh-' . wp_generate_password( 20, false, false ) . $ext;
}

/* -------------------------------------------------------------------------
 * Notifications
 * ---------------------------------------------------------------------- */

/** Tell the site owner a Hand started or finished signing up. */
function trh_notify_admin_new_hand( $post_id, $stage ) {
	$name  = trh_hand_name( $post_id );
	$lines = array(
		'started' === $stage
			? $name . ' started creating a Hand profile on The Ranch Hand.'
			: $name . ' finished all three signup steps.',
		'',
		'Email:       ' . trh_hand_field( $post_id, 'email' ),
		'Phone:       ' . trh_hand_field( $post_id, 'phone' ),
		'Location:    ' . trh_hand_field( $post_id, 'location' ),
		'Trust Score: ' . trh_trust_score( $post_id ) . ' / ' . trh_trust_max(),
		'',
		// Built by hand rather than with get_edit_post_link(), which returns null
		// here: the current user is the Hand who just signed up and has no
		// edit_post capability.
		'Review and publish: ' . admin_url( 'post.php?post=' . (int) $post_id . '&action=edit' ),
	);

	wp_mail(
		get_option( 'admin_email' ),
		'[The Ranch Hand] Hand signup ' . $stage . ': ' . $name,
		implode( "\n", $lines )
	);
}

/** Welcome the Hand right after their account exists. */
function trh_email_hand_welcome( $post_id ) {
	$email = trh_hand_field( $post_id, 'email' );
	if ( ! is_email( $email ) ) {
		return;
	}
	$lines = array(
		'Welcome to The Ranch Hand, ' . trh_hand_field( $post_id, 'first_name' ) . '.',
		'',
		'Your account is created. You can pick your profile back up any time here:',
		trh_dashboard_url(),
		'',
		'Your username is: ' . trh_hand_field( $post_id, 'username' ),
		'Sign in with that username (or this email address) and the password you chose.',
		'',
		'The Ranch Hand',
	);
	wp_mail( $email, 'Welcome to The Ranch Hand', implode( "\n", $lines ) );
}

/** Confirm the finished profile and the score they earned. */
function trh_email_hand_complete( $post_id, $score ) {
	$email = trh_hand_field( $post_id, 'email' );
	if ( ! is_email( $email ) ) {
		return;
	}
	$lines = array(
		'Nice work, ' . trh_hand_field( $post_id, 'first_name' ) . '. Your Hand profile is in.',
		'',
		'Trust Score earned: ' . (int) $score . ' points.',
		'',
		'We review every profile by hand before it goes live in the sitter directory.',
		'You will hear from us shortly. In the meantime you can add to your profile',
		'and raise your Trust Score here:',
		trh_dashboard_url(),
		'',
		'The Ranch Hand',
	);
	wp_mail( $email, 'Your Ranch Hand profile is in', implode( "\n", $lines ) );
}

/* -------------------------------------------------------------------------
 * Front-end login / logout
 *
 * These deliberately avoid wp-login.php: managed WordPress.com hosting can
 * intercept that URL with its own SSO screen, which would strand a Hand who
 * only ever had a front-end account.
 * ---------------------------------------------------------------------- */

add_action( 'admin_post_nopriv_trh_hand_login', 'trh_handle_hand_login' );
add_action( 'admin_post_trh_hand_login', 'trh_handle_hand_login' );
function trh_handle_hand_login() {
	if ( ! wp_verify_nonce( trh_post_text( 'trh_login_nonce' ), 'trh_hand_login' ) ) {
		wp_safe_redirect( add_query_arg( 'login', 'expired', trh_dashboard_url() ) );
		exit;
	}

	$user = wp_signon(
		array(
			'user_login'    => trh_post_text( 'log' ),
			'user_password' => trh_post_raw( 'pwd' ),
			'remember'      => ! empty( $_POST['rememberme'] ),
		),
		is_ssl()
	);

	if ( is_wp_error( $user ) ) {
		wp_safe_redirect( add_query_arg( 'login', 'failed', trh_dashboard_url() ) );
		exit;
	}

	wp_set_current_user( $user->ID );
	wp_safe_redirect( trh_dashboard_url() );
	exit;
}

add_action( 'admin_post_trh_hand_logout', 'trh_handle_hand_logout' );
function trh_handle_hand_logout() {
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'trh_hand_logout' ) ) {
		wp_safe_redirect( trh_dashboard_url() );
		exit;
	}
	wp_logout();
	wp_safe_redirect( home_url( '/' ) );
	exit;
}

/** URL that logs the current Hand out. */
function trh_hand_logout_url() {
	return wp_nonce_url( add_query_arg( 'action', 'trh_hand_logout', admin_url( 'admin-post.php' ) ), 'trh_hand_logout' );
}
