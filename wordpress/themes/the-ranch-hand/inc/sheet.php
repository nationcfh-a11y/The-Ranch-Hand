<?php
/**
 * Mirror signups into the "The Ranch Hand Users" Google Sheet.
 *
 * The live WordPress.com site can't talk to the Google Sheets API directly (no
 * service-account credentials, no Composer), so instead it POSTs each signup to
 * a Google Apps Script Web App that is bound to the sheet and appends the row.
 * This mirrors the philosophy of the old Node server's sheets.js: the WordPress
 * "Leads" list is the source of truth, and the sheet is a fail-soft mirror. A
 * Sheets or Apps Script outage must never block or fail a signup.
 *
 * One-time setup:
 *   1. Open the "The Ranch Hand Users" sheet -> Extensions -> Apps Script.
 *   2. Paste the code from docs/ranch-sheet-apps-script.gs and Save.
 *   3. Deploy -> New deployment -> Web app; Execute as "Me"; Who has access
 *      "Anyone". Copy the /exec URL.
 *   4. wp-admin -> Appearance -> Customize -> Integrations: paste the URL
 *      (and, optionally, a matching secret token).
 *
 * @package The_Ranch_Hand
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** The Apps Script Web App URL, or '' when the mirror is not configured yet. */
function trh_sheet_webhook_url() {
	$url = defined( 'TRH_SHEET_WEBHOOK_URL' ) ? TRH_SHEET_WEBHOOK_URL : get_theme_mod( 'trh_sheet_webhook', '' );
	return apply_filters( 'trh_sheet_webhook_url', $url );
}

/** Optional shared secret the Apps Script checks, or '' to skip the check. */
function trh_sheet_token() {
	$token = defined( 'TRH_SHEET_TOKEN' ) ? TRH_SHEET_TOKEN : get_theme_mod( 'trh_sheet_token', '' );
	return apply_filters( 'trh_sheet_token', $token );
}

/**
 * Append one signup row to the sheet. Fail-soft: any error is logged and
 * swallowed so the visitor still gets their thank-you. The Lead saved in
 * wp-admin remains the record of truth if this never lands.
 *
 * @param array $row name, email, role, location, search_radius.
 */
function trh_mirror_signup_to_sheet( $row ) {
	$url = trh_sheet_webhook_url();
	if ( ! $url ) {
		return; // Mirror not set up yet; the Lead is already saved.
	}

	$response = wp_remote_post(
		$url,
		array(
			'timeout'     => 8,
			'redirection' => 5, // Apps Script /exec answers via a 302 to script.googleusercontent.com.
			'body'        => array(
				'token'         => trh_sheet_token(),
				'name'          => isset( $row['name'] ) ? $row['name'] : '',
				'email'         => isset( $row['email'] ) ? $row['email'] : '',
				'role'          => isset( $row['role'] ) ? $row['role'] : '',
				'location'      => isset( $row['location'] ) ? $row['location'] : '',
				'search_radius' => isset( $row['search_radius'] ) ? $row['search_radius'] : '',
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		error_log( '[The Ranch Hand] Sheet mirror failed: ' . $response->get_error_message() );
		return;
	}
	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( $code < 200 || $code >= 300 ) {
		error_log( '[The Ranch Hand] Sheet mirror HTTP ' . $code . ': ' . wp_remote_retrieve_body( $response ) );
	}
}
