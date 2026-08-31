<?php
/**
 * Lead capture. Until the real booking engine exists (phase 2), the site
 * collects a few kinds of leads (booking requests, caretaker applications,
 * contact messages, and ranch signups), stores each as a private "trh_lead"
 * post (so nothing is ever lost) and emails the site admin. Ranch signups are
 * additionally mirrored into the Google Sheet (inc/sheet.php). Fully
 * self-contained; no third-party form plugin.
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
	// Radius is stored as an int; '' (not submitted) is kept out of the record.
	$search_radius = ( isset( $_POST['search_radius'] ) && '' !== $_POST['search_radius'] )
		? absint( wp_unslash( $_POST['search_radius'] ) )
		: '';

	// Ranch signup extras (empty for the other lead types).
	$farm_name      = isset( $_POST['farm_name'] ) ? sanitize_text_field( wp_unslash( $_POST['farm_name'] ) ) : '';
	$acres          = isset( $_POST['acres'] ) ? sanitize_text_field( wp_unslash( $_POST['acres'] ) ) : '';
	$animals        = trh_lead_checkbox_list( 'animals' );
	$animal_details = isset( $_POST['animal_details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['animal_details'] ) ) : '';
	$needs          = trh_lead_checkbox_list( 'needs' );
	$frequency      = isset( $_POST['frequency'] ) ? sanitize_text_field( wp_unslash( $_POST['frequency'] ) ) : '';
	$start          = isset( $_POST['start'] ) ? sanitize_text_field( wp_unslash( $_POST['start'] ) ) : '';
	$looking_for    = isset( $_POST['looking_for'] ) ? sanitize_textarea_field( wp_unslash( $_POST['looking_for'] ) ) : '';
	$notes          = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';

	if ( empty( $name ) || empty( $email ) || ! is_email( $email ) ) {
		trh_redirect_back( 'invalid' );
	}

	$is_application = ( 'application' === $type );
	$is_contact     = ( 'contact' === $type );
	$is_ranch       = ( 'ranch' === $type );
	if ( $is_application ) {
		$prefix = 'Application: ';
	} elseif ( $is_contact ) {
		$prefix = 'Contact: ';
	} elseif ( $is_ranch ) {
		$prefix = 'Ranch signup: ';
	} else {
		$prefix = 'Booking request: ';
	}
	$title = $prefix . $name;

	$lead_id = wp_insert_post(
		array(
			'post_type'   => 'trh_lead',
			'post_status' => 'private',
			'post_title'  => $title,
		)
	);
	if ( $lead_id && ! is_wp_error( $lead_id ) ) {
		$fields = compact(
			'type', 'name', 'email', 'phone', 'location', 'service', 'dates', 'caretaker', 'message', 'search_radius',
			'farm_name', 'acres', 'animals', 'animal_details', 'needs', 'frequency', 'start', 'looking_for', 'notes'
		);
		foreach ( $fields as $k => $v ) {
			if ( '' !== $v ) {
				update_post_meta( $lead_id, 'trh_' . $k, $v );
			}
		}
	}

	// Ranch signups also flow into the "Ranch" tab of the Google Sheet. Fail-soft
	// (see inc/sheet.php): the Lead above stays the record of truth regardless.
	// Keys are the Ranch tab's column headers.
	if ( $is_ranch ) {
		// Compile the answers into a PDF attached to the lead, then link to it
		// from the sheet's "User Info" column (parallel to the Hand profile PDF).
		$ranch_pdf_url = ( $lead_id && ! is_wp_error( $lead_id ) )
			? trh_store_ranch_pdf(
				(int) $lead_id,
				compact( 'name', 'email', 'phone', 'location', 'search_radius', 'farm_name', 'acres', 'animals', 'animal_details', 'needs', 'frequency', 'start', 'looking_for', 'notes' )
			)
			: '';

		// Everything is in the PDF now, so you can slim the Ranch tab down to
		// ID / Full Name / User Info if you like. The detail columns below only
		// fill in if you keep (or add) columns with those exact header names.
		trh_mirror_to_sheet(
			'Ranch',
			array(
				'ID'                 => ( $lead_id && ! is_wp_error( $lead_id ) ) ? (string) (int) $lead_id : '',
				'Full Name'          => $name,
				'Email'              => $email,
				'Role'               => 'owner',
				'Location'           => $location,
				'Search Radius (mi)' => ( '' !== $search_radius ) ? (string) $search_radius : '',
				'Phone'              => $phone,
				'Ranch Name'         => $farm_name,
				'Property Size'      => $acres,
				'Animals'            => $animals,
				'Animal Details'     => $animal_details,
				'Care Needed'        => $needs,
				'How Often'          => $frequency,
				'Start'              => $start,
				'Looking For'        => $looking_for,
				'Notes'              => $notes,
				'User Info'          => trh_sheet_link( $ranch_pdf_url, 'View Profile' ),
			)
		);
	}

	// Notify the admin (fail-soft; a mail error never blocks the thank-you).
	if ( $is_application ) {
		$human = 'caretaker application';
	} elseif ( $is_contact ) {
		$human = 'contact message';
	} elseif ( $is_ranch ) {
		$human = 'ranch signup';
	} else {
		$human = 'booking request';
	}
	$lines = array(
		'A new ' . $human . ' came in from The Ranch Hand:',
		'',
		'Name:     ' . $name,
		'Email:    ' . $email,
	);
	if ( $phone )               { $lines[] = 'Phone:    ' . $phone; }
	if ( $location )            { $lines[] = 'Location: ' . $location; }
	if ( '' !== $search_radius ) { $lines[] = 'Radius:   ' . $search_radius . ' mi'; }
	if ( $caretaker )           { $lines[] = 'Sitter:   ' . $caretaker; }
	if ( $service )             { $lines[] = ( $is_contact ? 'Subject:  ' : 'Service:  ' ) . $service; }
	if ( $dates )               { $lines[] = 'Dates:    ' . $dates; }
	// Ranch-specific answers.
	if ( $farm_name )      { $lines[] = 'Ranch:    ' . $farm_name; }
	if ( $acres )          { $lines[] = 'Size:     ' . $acres; }
	if ( $animals )        { $lines[] = 'Animals:  ' . $animals; }
	if ( $animal_details ) { $lines[] = 'Animal details: ' . $animal_details; }
	if ( $needs )          { $lines[] = 'Needs:    ' . $needs; }
	if ( $frequency )      { $lines[] = 'How often: ' . $frequency; }
	if ( $start )          { $lines[] = 'Start:    ' . $start; }
	if ( $looking_for )    { $lines[] = ''; $lines[] = 'Looking for:'; $lines[] = $looking_for; }
	if ( $notes )          { $lines[] = ''; $lines[] = 'Notes:'; $lines[] = $notes; }
	if ( $message )        { $lines[] = ''; $lines[] = 'Message:'; $lines[] = $message; }

	wp_mail(
		get_option( 'admin_email' ),
		'[The Ranch Hand] ' . $title,
		implode( "\n", $lines ),
		array( 'Reply-To: ' . $name . ' <' . $email . '>' )
	);

	// A registered ranch is sent on to choose a job-posting plan.
	if ( $is_ranch ) {
		wp_safe_redirect( add_query_arg( 'registered', '1', trh_page_url( 'ranch-plans' ) ) );
		exit;
	}

	trh_redirect_back( 'ok' );
}

/**
 * Compile a ranch signup's answers into a PDF attached to the lead, named
 * "<ranch-or-name>-<lead ID>.pdf". Parallels the Hand profile PDF. Returns the
 * public URL, or '' on failure.
 *
 * @param int   $lead_id The trh_lead post ID.
 * @param array $d       Sanitized answers (name, email, phone, location, ...).
 */
function trh_store_ranch_pdf( $lead_id, $d ) {
	require_once get_template_directory() . '/inc/lib/trh-pdf.php';

	$get  = static function ( $k ) use ( $d ) {
		return isset( $d[ $k ] ) ? (string) $d[ $k ] : '';
	};
	$name = $get( 'name' );
	$farm = $get( 'farm_name' );

	$pdf = new TRH_Simple_PDF();
	$pdf->title( ( $farm ? $farm : ( $name ? $name : 'Ranch' ) ) . "  \xC2\xB7  #" . $lead_id );
	$pdf->meta( 'Ranch / Owner' );
	$pdf->meta( 'Registered on ' . date_i18n( 'F j, Y' ) . '.' );

	$pdf->name( $name ? $name : 'Ranch owner' );
	if ( $get( 'email' ) ) {
		$pdf->field( 'Email', $get( 'email' ) );
	}
	if ( $get( 'phone' ) ) {
		$pdf->field( 'Phone', $get( 'phone' ) );
	}
	if ( $get( 'location' ) ) {
		$pdf->field( 'Location', $get( 'location' ) );
	}
	if ( '' !== $get( 'search_radius' ) ) {
		$pdf->field( 'A Hand can travel', $get( 'search_radius' ) . ' miles' );
	}

	$pdf->section( 'About the ranch' );
	if ( $farm ) {
		$pdf->field( 'Ranch name', $farm );
	}
	if ( $get( 'acres' ) ) {
		$pdf->field( 'Property size', $get( 'acres' ) );
	}
	if ( $get( 'animals' ) ) {
		$pdf->field( 'Animals', $get( 'animals' ) );
	}
	if ( $get( 'animal_details' ) ) {
		$pdf->group( 'About the animals' );
		$pdf->body( $get( 'animal_details' ) );
	}

	$pdf->section( 'What they need' );
	if ( $get( 'needs' ) ) {
		$pdf->field( 'Help needed', $get( 'needs' ) );
	}
	if ( $get( 'frequency' ) ) {
		$pdf->field( 'How often', $get( 'frequency' ) );
	}
	if ( $get( 'start' ) ) {
		$pdf->field( 'Start', $get( 'start' ) );
	}
	if ( $get( 'looking_for' ) ) {
		$pdf->group( 'Looking for in a Ranch Hand' );
		$pdf->body( $get( 'looking_for' ) );
	}
	if ( $get( 'notes' ) ) {
		$pdf->group( 'Notes' );
		$pdf->body( $get( 'notes' ) );
	}

	$base     = $farm ? $farm : ( $name ? $name : 'ranch' );
	$filename = sanitize_file_name( $base . '-' . $lead_id . '.pdf' );
	$upload   = wp_upload_bits( $filename, null, $pdf->output() );
	if ( ! empty( $upload['error'] ) || empty( $upload['file'] ) ) {
		error_log( '[The Ranch Hand] Ranch PDF save failed: ' . ( isset( $upload['error'] ) ? $upload['error'] : 'unknown' ) );
		return '';
	}

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => 'application/pdf',
			'post_title'     => ( $farm ? $farm : $name ) . ' — Ranch registration',
			'post_status'    => 'inherit',
		),
		$upload['file'],
		$lead_id,
		true
	);
	if ( is_wp_error( $attachment_id ) ) {
		wp_delete_file( $upload['file'] );
		return '';
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
	update_post_meta( $lead_id, 'trh_ranch_pdf_id', $attachment_id );

	return wp_get_attachment_url( $attachment_id );
}

/** Sanitize a checkbox-array POST field into a comma-separated string. */
function trh_lead_checkbox_list( $key ) {
	if ( empty( $_POST[ $key ] ) || ! is_array( $_POST[ $key ] ) ) {
		return '';
	}
	$values = array_map( 'sanitize_text_field', array_map( 'wp_unslash', $_POST[ $key ] ) );
	$values = array_filter( array_map( 'trim', $values ) );
	return implode( ', ', $values );
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

/**
 * Render a success/error notice from the ?trh_lead= flag. Call at top of forms.
 *
 * @param string $success_message Optional custom text for the success case
 *                                (e.g. a signup welcome instead of the default
 *                                "your request is in" wording).
 */
function trh_lead_notice( $success_message = '' ) {
	if ( empty( $_GET['trh_lead'] ) ) {
		return;
	}
	$status = sanitize_key( wp_unslash( $_GET['trh_lead'] ) );
	if ( 'ok' === $status ) {
		$message = $success_message ? $success_message : 'Thank you, your request is in! We\'ll be in touch by email shortly.';
		echo '<div class="notice notice-success">' . esc_html( $message ) . '</div>';
	} elseif ( 'invalid' === $status ) {
		echo '<div class="notice notice-error">Please add your name and a valid email so we can reach you.</div>';
	} elseif ( 'error' === $status ) {
		echo '<div class="notice notice-error">Something went wrong sending your request. Please try again.</div>';
	}
}
