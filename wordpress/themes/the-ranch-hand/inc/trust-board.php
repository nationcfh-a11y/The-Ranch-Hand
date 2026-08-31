<?php
/**
 * Trust Score Board: the award ledger behind post-signup points, and the live
 * score pill in the site header.
 *
 * The signup Trust Score in inc/hands.php is *derived*: it is re-added from the
 * profile on every save, so it can never drift or double-award. That is exactly
 * why it cannot hold points a Hand earns later. A finished job, an owner's
 * review, a watched sponsor spot: none of them correspond to a profile field,
 * so a recalculation would wipe them.
 *
 * Earned points therefore live in their own append-only ledger on the profile
 * post (`trh_trust_ledger`), and the stored score becomes:
 *
 *     trh_trust_score() = derived profile points + ledger total
 *
 * Recalculating is still safe: the derived half is rebuilt from scratch, the
 * ledger half is summed. Neither is ever incremented in place.
 *
 * Every point source, now and after the booking engine ships, calls one thing:
 *
 *     trh_award_trust_points( $profile_id, 'review_received', 15, 'Miller job' );
 *
 * The board itself is a pill in the header showing a signed-in Hand their score
 * on every page. When the score rises, the pill flashes "+15" and counts up to
 * the new total. It knows what a Hand has already been shown from a per-user
 * watermark (`trh_trust_seen`), so an award that lands while they are away is
 * still celebrated on their next visit, and never celebrated twice.
 *
 * @package The_Ranch_Hand
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Post meta holding the award ledger (array of entries, oldest first). */
const TRH_TRUST_LEDGER_META = 'trh_trust_ledger';

/** User meta holding the score a Hand has already been shown. */
const TRH_TRUST_SEEN_META = 'trh_trust_seen';

/** Ledger entries kept per profile. Older ones fall off the front. */
const TRH_TRUST_LEDGER_MAX = 250;

/** REST namespace for the board's polling endpoints. */
const TRH_TRUST_REST_NS = 'ranch-hand/v1';

/** How often an open page asks the server whether the score moved (ms). */
const TRH_TRUST_POLL_MS = 30000;

/* -------------------------------------------------------------------------
 * Award types
 * ---------------------------------------------------------------------- */

/**
 * Every way a Hand earns points *after* signup.
 *
 * `points` is only the default the admin box pre-fills and the booking engine
 * can fall back to. Any award may carry its own amount, so a five-star review
 * can be worth more than a three-star one without a new type here.
 *
 * @return array<string, array{label:string, points:int, blurb:string}>
 */
function trh_trust_award_types() {
	return array(
		'job_completed'   => array(
			'label'  => 'Job completed',
			'points' => 20,
			'blurb'  => 'Finished a booking for an owner.',
		),
		'review_received' => array(
			'label'  => 'Review from an owner',
			'points' => 15,
			'blurb'  => 'An owner reviewed the work you did.',
		),
		'repeat_client'   => array(
			'label'  => 'Repeat client',
			'points' => 10,
			'blurb'  => 'An owner booked you a second time.',
		),
		'ad_watched'      => array(
			'label'  => 'Sponsor spot watched',
			'points' => 5,
			'blurb'  => 'Watched a short spot from a Ranch Hand sponsor.',
		),
		'verified'        => array(
			'label'  => 'Identity verified',
			'points' => 25,
			'blurb'  => 'We confirmed who you are.',
		),
		'bonus'           => array(
			'label'  => 'Bonus from The Ranch Hand',
			'points' => 10,
			'blurb'  => 'A bonus awarded by hand.',
		),
	);
}

/** Human label for an award type, falling back to the raw key. */
function trh_trust_award_label( $type ) {
	$types = trh_trust_award_types();
	return isset( $types[ $type ] ) ? $types[ $type ]['label'] : (string) $type;
}

/* -------------------------------------------------------------------------
 * The ledger
 * ---------------------------------------------------------------------- */

/**
 * Award ledger for a profile, oldest first.
 *
 * Entries are normalised on read so a hand-edited or half-written meta value
 * can never break the score maths.
 *
 * @return array<int, array{id:string, type:string, points:int, note:string, time:int}>
 */
function trh_trust_ledger( $post_id ) {
	$raw = get_post_meta( (int) $post_id, TRH_TRUST_LEDGER_META, true );
	if ( ! is_array( $raw ) ) {
		return array();
	}

	$ledger = array();
	foreach ( $raw as $entry ) {
		if ( ! is_array( $entry ) || empty( $entry['type'] ) ) {
			continue;
		}
		$ledger[] = array(
			'id'     => isset( $entry['id'] ) ? (string) $entry['id'] : '',
			'type'   => (string) $entry['type'],
			'points' => isset( $entry['points'] ) ? (int) $entry['points'] : 0,
			'note'   => isset( $entry['note'] ) ? (string) $entry['note'] : '',
			'time'   => isset( $entry['time'] ) ? (int) $entry['time'] : 0,
		);
	}
	return $ledger;
}

/** Points earned after signup. Summed, never incremented, so it cannot drift. */
function trh_trust_ledger_total( $post_id ) {
	$total = 0;
	foreach ( trh_trust_ledger( $post_id ) as $entry ) {
		$total += $entry['points'];
	}
	return $total;
}

/** The ledger newest first, for the dashboard and the admin box. */
function trh_trust_ledger_recent( $post_id, $limit = 0 ) {
	$ledger = array_reverse( trh_trust_ledger( $post_id ) );
	return $limit > 0 ? array_slice( $ledger, 0, (int) $limit ) : $ledger;
}

/**
 * Award Trust Score points to a Hand. The one entry point for every point
 * source: the admin box today, the booking engine and ad player later.
 *
 * @param int         $post_id Caretaker profile post ID.
 * @param string      $type    Key from trh_trust_award_types().
 * @param int|null    $points  Amount, or null to use the type's default.
 * @param string      $note    Short human context ("Review from the Miller job").
 * @return int|WP_Error The Hand's new total score, or an error.
 */
function trh_award_trust_points( $post_id, $type, $points = null, $note = '' ) {
	$post_id = (int) $post_id;
	$types   = trh_trust_award_types();

	if ( ! $post_id || 'caretaker' !== get_post_type( $post_id ) ) {
		return new WP_Error( 'trh_no_profile', 'No such Hand profile.' );
	}
	if ( ! isset( $types[ $type ] ) ) {
		return new WP_Error( 'trh_bad_award_type', 'Unknown Trust Score award type: ' . $type );
	}

	$points = ( null === $points ) ? $types[ $type ]['points'] : (int) $points;
	if ( 0 === $points ) {
		return new WP_Error( 'trh_zero_award', 'An award has to be worth something.' );
	}

	$ledger   = trh_trust_ledger( $post_id );
	$ledger[] = array(
		'id'     => uniqid( 'a', true ),
		'type'   => $type,
		'points' => $points,
		'note'   => sanitize_text_field( $note ),
		'time'   => time(),
	);
	if ( count( $ledger ) > TRH_TRUST_LEDGER_MAX ) {
		$ledger = array_slice( $ledger, -TRH_TRUST_LEDGER_MAX );
	}

	update_post_meta( $post_id, TRH_TRUST_LEDGER_META, $ledger );
	$score = trh_recalculate_trust_score( $post_id );

	/**
	 * Fires after points land. The header board picks the change up on its own
	 * (it compares the stored score against the Hand's watermark), so this hook
	 * is for anything extra: an email, a push, a log.
	 */
	do_action( 'trh_trust_points_awarded', $post_id, $type, $points, $score );

	return $score;
}

/**
 * Remove one award, e.g. a review that turned out to be bogus.
 *
 * @return int|WP_Error The new total score.
 */
function trh_revoke_trust_award( $post_id, $award_id ) {
	$post_id = (int) $post_id;
	$ledger  = trh_trust_ledger( $post_id );
	$kept    = array();

	foreach ( $ledger as $entry ) {
		if ( $entry['id'] !== (string) $award_id ) {
			$kept[] = $entry;
		}
	}
	if ( count( $kept ) === count( $ledger ) ) {
		return new WP_Error( 'trh_no_award', 'No such award on this profile.' );
	}

	update_post_meta( $post_id, TRH_TRUST_LEDGER_META, $kept );
	return trh_recalculate_trust_score( $post_id );
}

/* -------------------------------------------------------------------------
 * The "already celebrated" watermark
 * ---------------------------------------------------------------------- */

/**
 * The score this Hand has already been shown.
 *
 * On the very first read the watermark is seeded to the current score, so a
 * Hand who signed up before the board existed is not greeted with a phantom
 * "+130" for points they earned weeks ago.
 */
function trh_trust_seen( $user_id, $score ) {
	$user_id = (int) $user_id;
	$stored  = get_user_meta( $user_id, TRH_TRUST_SEEN_META, true );

	if ( '' === $stored || null === $stored ) {
		update_user_meta( $user_id, TRH_TRUST_SEEN_META, (int) $score );
		return (int) $score;
	}
	return (int) $stored;
}

/**
 * Mark a score as celebrated. Only ever moves forward, and never past what the
 * profile actually holds, so a stale browser tab cannot skip a real award.
 */
function trh_trust_mark_seen( $user_id, $score, $actual ) {
	$user_id = (int) $user_id;
	$target  = min( (int) $score, (int) $actual );
	$stored  = (int) get_user_meta( $user_id, TRH_TRUST_SEEN_META, true );

	if ( $target > $stored ) {
		update_user_meta( $user_id, TRH_TRUST_SEEN_META, $target );
		return $target;
	}
	return $stored;
}

/* -------------------------------------------------------------------------
 * REST: what the open page polls
 * ---------------------------------------------------------------------- */

add_action( 'rest_api_init', 'trh_trust_rest_routes' );
function trh_trust_rest_routes() {
	register_rest_route(
		TRH_TRUST_REST_NS,
		'/trust',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'trh_trust_rest_get',
			'permission_callback' => 'trh_trust_rest_can',
		)
	);

	register_rest_route(
		TRH_TRUST_REST_NS,
		'/trust/seen',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'trh_trust_rest_seen',
			'permission_callback' => 'trh_trust_rest_can',
			'args'                => array(
				'score' => array(
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);
}

/** Only a signed-in Hand, and only ever about their own profile. */
function trh_trust_rest_can() {
	return is_user_logged_in() && (bool) trh_hand_profile_id();
}

/** Current score plus how much of it the Hand has not been shown yet. */
function trh_trust_rest_get() {
	$profile = trh_hand_profile_id();
	$score   = trh_trust_score( $profile );
	$seen    = trh_trust_seen( get_current_user_id(), $score );
	$latest  = trh_trust_ledger_recent( $profile, 1 );

	return rest_ensure_response(
		array(
			'score'   => $score,
			'seen'    => $seen,
			'pending' => max( 0, $score - $seen ),
			'reason'  => $latest ? trh_trust_award_label( $latest[0]['type'] ) : '',
		)
	);
}

/** Called by the board once it has finished animating a rise. */
function trh_trust_rest_seen( WP_REST_Request $request ) {
	$profile = trh_hand_profile_id();
	$seen    = trh_trust_mark_seen( get_current_user_id(), $request->get_param( 'score' ), trh_trust_score( $profile ) );

	return rest_ensure_response( array( 'seen' => $seen ) );
}

/* -------------------------------------------------------------------------
 * The board in the header
 * ---------------------------------------------------------------------- */

add_action( 'wp_enqueue_scripts', 'trh_trust_board_assets', 20 );
function trh_trust_board_assets() {
	if ( ! is_user_logged_in() || ! trh_hand_profile_id() ) {
		return;
	}

	wp_enqueue_script(
		'trh-trust-board',
		get_template_directory_uri() . '/assets/js/trust-board.js',
		array(),
		TRH_VERSION,
		true
	);
	wp_localize_script(
		'trh-trust-board',
		'TRH_TRUST',
		array(
			'root'   => esc_url_raw( rest_url( TRH_TRUST_REST_NS . '/' ) ),
			'nonce'  => wp_create_nonce( 'wp_rest' ),
			/**
			 * How often an open page checks for a rise, in ms. Filterable so a
			 * local preview can watch it happen without a 30-second wait.
			 */
			'pollMs' => (int) apply_filters( 'trh_trust_poll_ms', TRH_TRUST_POLL_MS ),
		)
	);
}

/**
 * Render the header pill. Silent for anyone who is not a signed-in Hand.
 *
 * The markup prints the *true* score, and the inline primer below rewinds it to
 * the watermark before the browser paints, so the animation starts from the old
 * number without a flash and a Hand with JS off still sees the right total.
 */
function trh_trust_board() {
	$profile = trh_hand_profile_id();
	if ( ! $profile ) {
		return;
	}

	$score   = trh_trust_score( $profile );
	$seen    = trh_trust_seen( get_current_user_id(), $score );
	$pending = max( 0, $score - $seen );
	?>
	<a class="trust-board" id="trh-trust-board" href="<?php echo esc_url( trh_dashboard_url() . '#trust-score' ); ?>"
		data-score="<?php echo esc_attr( $score ); ?>" data-pending="<?php echo esc_attr( $pending ); ?>"
		aria-label="<?php echo esc_attr( sprintf( 'Trust Score: %d points. See your breakdown.', $score ) ); ?>">
		<span class="trust-board-icon" aria-hidden="true">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
				<path d="M12 2.5 4.5 5.5v6c0 4.6 3.2 8.6 7.5 10 4.3-1.4 7.5-5.4 7.5-10v-6L12 2.5Z" />
				<path d="m8.75 11.75 2.25 2.25 4.25-4.5" />
			</svg>
		</span>
		<span class="trust-board-num" data-trust-num><?php echo esc_html( $score ); ?></span>
		<span class="trust-board-cap">Trust</span>
		<span class="trust-board-pop" data-trust-pop aria-hidden="true"></span>
		<span class="trust-board-live" data-trust-live role="status" aria-live="polite"></span>
	</a>
	<?php
	// Runs during parse, before first paint: rewind the number to the last one
	// the Hand saw so the count-up in trust-board.js has somewhere to start.
	if ( $pending > 0 ) {
		wp_print_inline_script_tag(
			"(function(){var b=document.getElementById('trh-trust-board');if(!b)return;" .
			"var n=b.querySelector('[data-trust-num]');" .
			"if(n)n.textContent=String((+b.dataset.score||0)-(+b.dataset.pending||0));})();"
		);
	}
}

/* -------------------------------------------------------------------------
 * wp-admin: award points by hand until the booking engine can do it
 * ---------------------------------------------------------------------- */

add_action( 'add_meta_boxes', 'trh_trust_award_meta_box' );
function trh_trust_award_meta_box() {
	add_meta_box( 'trh_trust_awards', 'Trust Score awards', 'trh_render_trust_award_box', 'caretaker', 'side', 'default' );
}

function trh_render_trust_award_box( $post ) {
	$types  = trh_trust_award_types();
	$ledger = trh_trust_ledger_recent( $post->ID );
	$earned = trh_trust_ledger_total( $post->ID );

	wp_nonce_field( 'trh_trust_award_' . $post->ID, 'trh_trust_award_nonce' );

	echo '<p><strong>' . esc_html( trh_trust_score( $post->ID ) ) . '</strong> total &mdash; '
		. esc_html( trh_trust_score( $post->ID ) - $earned ) . ' from the profile, '
		. esc_html( $earned ) . ' earned since.</p>';

	echo '<p><label for="trh_award_type"><strong>Award points</strong></label><br />';
	echo '<select name="trh_award[type]" id="trh_award_type" style="width:100%;">';
	echo '<option value="">&mdash; nothing to award &mdash;</option>';
	foreach ( $types as $key => $type ) {
		printf(
			'<option value="%s" data-points="%d">%s (+%d)</option>',
			esc_attr( $key ),
			(int) $type['points'],
			esc_html( $type['label'] ),
			(int) $type['points']
		);
	}
	echo '</select></p>';

	echo '<p><label for="trh_award_points">Points <span class="description">(blank = the default above)</span></label><br />';
	echo '<input type="number" name="trh_award[points]" id="trh_award_points" style="width:100%;" placeholder="default" /></p>';

	echo '<p><label for="trh_award_note">Note <span class="description">(shown to the Hand)</span></label><br />';
	echo '<input type="text" name="trh_award[note]" id="trh_award_note" style="width:100%;" maxlength="120" placeholder="Review from the Miller job" /></p>';

	echo '<p class="description">Points land when you press Update. The Hand sees the rise in their header within '
		. esc_html( round( TRH_TRUST_POLL_MS / 1000 ) ) . ' seconds, or on their next visit.</p>';

	if ( ! $ledger ) {
		return;
	}

	echo '<hr /><p><strong>Awarded so far</strong></p><ul style="margin:0;">';
	foreach ( $ledger as $entry ) {
		printf(
			'<li style="margin-bottom:.5em;"><label><input type="checkbox" name="trh_award_revoke[]" value="%s" /> <strong>+%d</strong> %s%s<br /><span class="description">%s</span></label></li>',
			esc_attr( $entry['id'] ),
			(int) $entry['points'],
			esc_html( trh_trust_award_label( $entry['type'] ) ),
			$entry['note'] ? ' &mdash; ' . esc_html( $entry['note'] ) : '',
			esc_html( $entry['time'] ? wp_date( 'M j, Y', $entry['time'] ) : '' )
		);
	}
	echo '</ul><p class="description">Tick an award and press Update to take it back.</p>';
}

add_action( 'save_post_caretaker', 'trh_save_trust_award', 10, 2 );
/**
 * Apply whatever the award box asked for. Runs on the post's own save, so there
 * is no second form and no second nonce to keep straight.
 */
function trh_save_trust_award( $post_id, $post ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( ! isset( $_POST['trh_trust_award_nonce'] )
		|| ! wp_verify_nonce( sanitize_key( $_POST['trh_trust_award_nonce'] ), 'trh_trust_award_' . $post_id ) ) {
		return;
	}

	if ( ! empty( $_POST['trh_award_revoke'] ) && is_array( $_POST['trh_award_revoke'] ) ) {
		foreach ( wp_unslash( $_POST['trh_award_revoke'] ) as $award_id ) {
			trh_revoke_trust_award( $post_id, sanitize_text_field( $award_id ) );
		}
	}

	$award = isset( $_POST['trh_award'] ) && is_array( $_POST['trh_award'] ) ? wp_unslash( $_POST['trh_award'] ) : array();
	$type  = isset( $award['type'] ) ? sanitize_key( $award['type'] ) : '';
	if ( ! $type ) {
		return;
	}

	$points = ( isset( $award['points'] ) && '' !== trim( $award['points'] ) ) ? (int) $award['points'] : null;
	trh_award_trust_points( $post_id, $type, $points, isset( $award['note'] ) ? $award['note'] : '' );
}
