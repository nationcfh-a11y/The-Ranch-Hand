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
 * Upsert one row into a tab of the sheet. The $row is keyed by the EXACT column
 * header names on that tab; the Apps Script matches an existing row by the
 * "Email" value (so the multi-step Hand signup fills one row as it progresses)
 * and otherwise appends. "ID" and "Signed Up" are managed by the script.
 *
 * Empty values are dropped before sending, so a later step can never blank a
 * column an earlier step already filled. Fail-soft: any error is logged and
 * swallowed. The wp-admin record (Lead or Hand profile) is the source of truth.
 *
 * @param string $tab 'Ranch' or 'Hand'.
 * @param array  $row Column header => value.
 */
function trh_mirror_to_sheet( $tab, $row ) {
	$url = trh_sheet_webhook_url();
	if ( ! $url ) {
		return; // Mirror not set up yet; the wp-admin record is already saved.
	}

	$row = array_filter(
		$row,
		static function ( $v ) {
			return '' !== $v && null !== $v;
		}
	);
	if ( empty( $row['Email'] ) ) {
		return; // Email is the row key; nothing to match or insert on without it.
	}

	$response = wp_remote_post(
		$url,
		array(
			'timeout'     => 10,
			'redirection' => 5, // Apps Script /exec answers via a 302 to script.googleusercontent.com.
			'body'        => array(
				'token'   => trh_sheet_token(),
				'tab'     => $tab,
				'payload' => wp_json_encode( $row ),
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
