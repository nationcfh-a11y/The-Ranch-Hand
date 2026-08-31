<?php
/**
 * Ranch Hand profiles: the account role, the profile data model, the Trust
 * Score engine, and the experience checklist.
 *
 * A "Hand" is a person who signs up to care for animals. Signing up creates
 * two linked records:
 *
 *   1. a WordPress user with the `trh_hand` role (so they can log back in and
 *      keep editing their profile), and
 *   2. a `caretaker` post authored by that user, held at post_status `pending`
 *      until an admin reviews and publishes it. Because it is the same CPT the
 *      directory already renders, an approved Hand appears on /sitters/ with no
 *      extra work.
 *
 * The Trust Score is a points total stored on the profile post. Every point is
 * derived from what is actually on the profile (see trh_trust_components()), so
 * it can be recalculated from scratch at any time and never drifts.
 *
 * Form handling for the three-step signup lives in inc/hand-signup.php.
 *
 * @package The_Ranch_Hand
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Role slug for a Hand account. */
const TRH_HAND_ROLE = 'trh_hand';

/* -------------------------------------------------------------------------
 * Role + pages (both one-time, option-guarded DB writes)
 * ---------------------------------------------------------------------- */

add_action( 'after_setup_theme', 'trh_ensure_hand_role' );
/**
 * Create the Hand role. Deliberately minimal: `read` only, so a Hand can log
 * in and use the front-end dashboard but has no wp-admin powers. Uploads are
 * performed by the theme's own validated handler, not by the user's caps.
 */
function trh_ensure_hand_role() {
	if ( get_option( 'trh_hand_role_v1' ) ) {
		return;
	}
	remove_role( TRH_HAND_ROLE ); // keep re-runs idempotent
	add_role( TRH_HAND_ROLE, 'Ranch Hand', array( 'read' => true ) );
	update_option( 'trh_hand_role_v1', 1 );
}

add_action( 'after_setup_theme', 'trh_ensure_hand_pages' );
/**
 * Create the two pages the signup flow needs. Their templates resolve by slug
 * (page-hand-signup.php, page-dashboard.php), so post_content stays empty.
 */
function trh_ensure_hand_pages() {
	if ( get_option( 'trh_hand_pages_v1' ) ) {
		return;
	}

	$pages = array(
		'hand-signup' => 'Create Your Hand Profile',
		'dashboard'   => 'Dashboard',
	);

	foreach ( $pages as $slug => $title ) {
		if ( get_page_by_path( $slug, OBJECT, 'page' ) ) {
			continue;
		}
		wp_insert_post(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'post_title'     => $title,
				'post_name'      => $slug,
				'post_content'   => '',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			)
		);
	}

	update_option( 'trh_hand_pages_v1', 1 );
}

/**
 * Permalink of one of our pages by slug, falling back to a pretty path if the
 * page was deleted (so links degrade to a 404 rather than to the homepage).
 */
function trh_page_url( $slug ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	if ( $page ) {
		$link = get_permalink( $page );
		if ( $link ) {
			return $link;
		}
	}
	return home_url( '/' . $slug . '/' );
}

/** URL of a signup step (1-3). */
function trh_signup_url( $step = 1 ) {
	$base = trh_page_url( 'hand-signup' );
	$step = max( 1, min( 3, (int) $step ) );
	return 1 === $step ? $base : add_query_arg( 'step', $step, $base );
}

/** URL of the Hand dashboard. */
function trh_dashboard_url() {
	return trh_page_url( 'dashboard' );
}

/**
 * Keep the wizard and the dashboard out of the page cache.
 *
 * Both are wrong to cache: the wizard's forms carry nonces, which expire, and
 * the dashboard is per-person. Managed WordPress.com hosting puts Batcache in
 * front of anonymous traffic (look for the `x-ac: ... HIT/STALE` header), and it
 * skips any response that sends no-cache headers.
 */
add_action( 'template_redirect', 'trh_hand_nocache' );
function trh_hand_nocache() {
	if ( ! is_page( array( 'hand-signup', 'dashboard' ) ) ) {
		return;
	}
	nocache_headers();
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
}

/** Keep the signup wizard and the private dashboard out of search engines. */
add_filter( 'wp_robots', 'trh_hand_robots' );
function trh_hand_robots( $robots ) {
	if ( is_page( array( 'hand-signup', 'dashboard' ) ) ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
	}
	return $robots;
}

/* -------------------------------------------------------------------------
 * Reference data
 * ---------------------------------------------------------------------- */

/** US states + DC + PR, keyed by the postal code stored on the profile. */
function trh_us_states() {
	return array(
		'AL' => 'Alabama',       'AK' => 'Alaska',         'AZ' => 'Arizona',
		'AR' => 'Arkansas',      'CA' => 'California',     'CO' => 'Colorado',
		'CT' => 'Connecticut',   'DE' => 'Delaware',       'DC' => 'District of Columbia',
		'FL' => 'Florida',       'GA' => 'Georgia',        'HI' => 'Hawaii',
		'ID' => 'Idaho',         'IL' => 'Illinois',       'IN' => 'Indiana',
		'IA' => 'Iowa',          'KS' => 'Kansas',         'KY' => 'Kentucky',
		'LA' => 'Louisiana',     'ME' => 'Maine',          'MD' => 'Maryland',
		'MA' => 'Massachusetts', 'MI' => 'Michigan',       'MN' => 'Minnesota',
		'MS' => 'Mississippi',   'MO' => 'Missouri',       'MT' => 'Montana',
		'NE' => 'Nebraska',      'NV' => 'Nevada',         'NH' => 'New Hampshire',
		'NJ' => 'New Jersey',    'NM' => 'New Mexico',     'NY' => 'New York',
		'NC' => 'North Carolina','ND' => 'North Dakota',   'OH' => 'Ohio',
		'OK' => 'Oklahoma',      'OR' => 'Oregon',         'PA' => 'Pennsylvania',
		'PR' => 'Puerto Rico',   'RI' => 'Rhode Island',   'SC' => 'South Carolina',
		'SD' => 'South Dakota',  'TN' => 'Tennessee',      'TX' => 'Texas',
		'UT' => 'Utah',          'VT' => 'Vermont',        'VA' => 'Virginia',
		'WA' => 'Washington',    'WV' => 'West Virginia',  'WI' => 'Wisconsin',
		'WY' => 'Wyoming',
	);
}

/** Social networks a Hand can link, keyed by the meta sub-key. */
function trh_social_networks() {
	return array(
		'instagram' => array( 'label' => 'Instagram', 'icon' => '📸', 'placeholder' => 'instagram.com/yourhandle' ),
		'facebook'  => array( 'label' => 'Facebook',  'icon' => '👍', 'placeholder' => 'facebook.com/yourprofile' ),
		'tiktok'    => array( 'label' => 'TikTok',    'icon' => '🎬', 'placeholder' => 'tiktok.com/@yourhandle' ),
		'youtube'   => array( 'label' => 'YouTube',   'icon' => '▶️', 'placeholder' => 'youtube.com/@yourchannel' ),
		'linkedin'  => array( 'label' => 'LinkedIn',  'icon' => '💼', 'placeholder' => 'linkedin.com/in/you' ),
		'website'   => array( 'label' => 'Website',   'icon' => '🌐', 'placeholder' => 'yourbarn.com' ),
	);
}

/**
 * The experience checklist, grouped the way an intake form at a vet or
 * doctor's office is: short, scannable statements you tick off.
 *
 * Keys are stored on the profile (meta trh_experience) and must stay stable.
 * Add new items freely; never rename an existing key.
 *
 * @return array<string, array{icon:string, items:array<string,string>}>
 */
function trh_experience_groups() {
	return array(
		'Horse handling' => array(
			'icon'  => '🐴',
			'items' => array(
				'blanketing'        => 'Blanketing & changing sheets',
				'catch_lead'        => 'Catching, haltering & leading',
				'turnout'           => 'Turnout & bringing in',
				'lunging'           => 'Exercising / lunging',
				'riding'            => 'Tacking up & riding',
				'difficult_animals' => 'Handling difficult or pushy animals',
				'stallions'         => 'Handling stallions',
				'trailering'        => 'Trailer loading & hauling',
				'farrier_holds'     => 'Picking hooves & holding for the farrier',
				'clipping'          => 'Bathing, clipping & grooming',
			),
		),
		'Medical & first aid' => array(
			'icon'  => '💉',
			'items' => array(
				'oral_meds'   => 'Giving oral medications & dewormer',
				'injections'  => 'Intramuscular (IM) injections',
				'wounds'      => 'Cleaning & dressing wounds',
				'wrapping'    => 'Standing wraps & bandaging',
				'poultice'    => 'Poultices & cold hosing',
				'soaking'     => 'Soaking hooves & abscesses',
				'vitals'      => 'Taking vitals (temp, pulse, gut sounds)',
				'colic'       => 'Recognizing & responding to colic',
				'eye_meds'    => 'Eye medications & ointments',
				'stall_rest'  => 'Stall rest & post-surgery care',
				'rehab_walk'  => 'Hand-walking rehab & lameness watch',
				'emergency'   => 'Calling the vet & handling emergencies',
			),
		),
		'Babies & breeding' => array(
			'icon'  => '🍼',
			'items' => array(
				'foals'        => 'Foals & weanlings',
				'foal_watch'   => 'Mares in foal / foal watch',
				'bottle_feed'  => 'Bottle feeding orphans',
				'birthing'     => 'Foaling, calving, lambing & kidding',
			),
		),
		'Special needs' => array(
			'icon'  => '💛',
			'items' => array(
				'blind'          => 'Blind or vision-impaired animals',
				'seniors'        => 'Senior animals & soaked feed / mash',
				'metabolic'      => "Metabolic care (Cushing's, IR, laminitis)",
				'muzzles'        => 'Grazing muzzles & slow feeders',
				'anxious'        => 'Anxious, rescue or mistreated animals',
				'mobility'       => 'Limited-mobility & arthritic animals',
			),
		),
		'Other animals' => array(
			'icon'  => '🐄',
			'items' => array(
				'cattle'   => 'Cattle',
				'goats'    => 'Goats & sheep',
				'pigs'     => 'Pigs',
				'poultry'  => 'Poultry & coop care',
				'camelids' => 'Llamas & alpacas',
				'dogs_cats'=> 'Dogs, cats & barn cats',
			),
		),
		'Barn & property' => array(
			'icon'  => '🧰',
			'items' => array(
				'mucking'    => 'Mucking stalls & bedding',
				'water'      => 'Water tanks, waterers & winter de-icing',
				'feed_chart' => 'Mixing feed to a written chart',
				'hay'        => 'Hay handling & stacking',
				'fencing'    => 'Fence checks & basic repairs',
				'equipment'  => 'Tractor, ATV or skid steer',
				'pasture'    => 'Pasture rotation, mowing & dragging',
				'outages'    => 'Generators & power-outage protocols',
			),
		),
		'Training & credentials' => array(
			'icon'  => '🎓',
			'items' => array(
				'equine_first_aid' => 'Equine first aid / CPR certified',
				'vet_tech'         => 'Vet tech or vet assistant experience',
				'instructor'       => 'Certified riding instructor',
				'bodywork'         => 'Equine massage or bodywork',
				'four_h'           => '4-H or FFA background',
				'own_transport'    => "Own transportation & valid driver's license",
				'overnights'       => 'Comfortable staying overnight on-site',
				'vet_transport'    => 'Can haul to the vet in an emergency',
			),
		),
	);
}

/** Flat key => label map for every checklist item. */
function trh_experience_labels() {
	static $flat = null;
	if ( null !== $flat ) {
		return $flat;
	}
	$flat = array();
	foreach ( trh_experience_groups() as $group ) {
		foreach ( $group['items'] as $key => $label ) {
			$flat[ $key ] = $label;
		}
	}
	return $flat;
}

/* -------------------------------------------------------------------------
 * Trust Score
 * ---------------------------------------------------------------------- */

/**
 * Every way a Hand earns Trust Score points during signup.
 *
 * The four non-bonus rows add up to exactly 100: finishing the required parts
 * of signup lands you on 100. The two bonus rows (photo, socials) are the
 * "+15 points" nudges shown next to those fields, taking a fully finished
 * profile to 130.
 *
 * @return array<string, array{points:int, label:string, blurb:string, step:int, bonus:bool}>
 */
function trh_trust_components() {
	return array(
		'basics'     => array(
			'points' => 40,
			'label'  => 'Contact details & location',
			'blurb'  => 'Your name, phone, email, and where you work.',
			'step'   => 1,
			'bonus'  => false,
		),
		'resume'     => array(
			'points' => 20,
			'label'  => 'Resume on file',
			'blurb'  => 'Owners can see your work history at a glance.',
			'step'   => 2,
			'bonus'  => false,
		),
		'references' => array(
			'points' => 20,
			'label'  => 'References added',
			'blurb'  => 'People who will vouch for how you handle animals.',
			'step'   => 2,
			'bonus'  => false,
		),
		'experience' => array(
			'points' => 20,
			'label'  => 'Experience checklist',
			'blurb'  => 'Tick off the care you have actually done.',
			'step'   => 3,
			'bonus'  => false,
		),
		'photo'      => array(
			'points' => 15,
			'label'  => 'Profile picture',
			'blurb'  => 'Profiles with a photo get picked far more often.',
			'step'   => 2,
			'bonus'  => true,
		),
		'socials'    => array(
			'points' => 15,
			'label'  => 'Social accounts connected',
			'blurb'  => 'A real presence online is one more reason to trust you.',
			'step'   => 2,
			'bonus'  => true,
		),
	);
}

/** Highest score reachable from signup alone (130). */
function trh_trust_max() {
	return array_sum( wp_list_pluck( trh_trust_components(), 'points' ) );
}

/** Points from the required path alone (100). */
function trh_trust_base() {
	$total = 0;
	foreach ( trh_trust_components() as $c ) {
		if ( empty( $c['bonus'] ) ) {
			$total += $c['points'];
		}
	}
	return $total;
}

/** Minimum checklist ticks that earn the experience points. */
const TRH_EXPERIENCE_MIN = 3;

/**
 * Which components a profile has actually earned, read straight off the post.
 *
 * @return array<string, bool> component key => earned
 */
function trh_trust_earned( $post_id ) {
	$post_id = (int) $post_id;
	if ( ! $post_id ) {
		// No profile yet (step 1). Nothing is earned, and never fall through to
		// has_post_thumbnail(0), which would read the current page instead.
		return array_map( '__return_false', trh_trust_components() );
	}

	$has_basics = trh_hand_field( $post_id, 'first_name' )
		&& trh_hand_field( $post_id, 'phone' )
		&& trh_hand_field( $post_id, 'location' );

	return array(
		'basics'     => (bool) $has_basics,
		'resume'     => (bool) trh_hand_field( $post_id, 'resume_id' ),
		'references' => count( trh_hand_references( $post_id ) ) > 0,
		'experience' => count( trh_hand_experience( $post_id ) ) >= TRH_EXPERIENCE_MIN,
		'photo'      => has_post_thumbnail( $post_id ),
		'socials'    => count( trh_hand_socials( $post_id ) ) > 0,
	);
}

/**
 * Recalculate and store the Trust Score.
 *
 * Two halves, both idempotent: the signup points are re-derived from the
 * profile, and the points earned since are summed from the award ledger (see
 * inc/trust-board.php). Nothing is ever incremented in place, so calling this
 * twice cannot double-award anything.
 *
 * @return int The stored score.
 */
function trh_recalculate_trust_score( $post_id ) {
	$components = trh_trust_components();
	$earned     = trh_trust_earned( $post_id );
	$score      = 0;
	$awards     = array();

	foreach ( $components as $key => $component ) {
		if ( ! empty( $earned[ $key ] ) ) {
			$score           += $component['points'];
			$awards[ $key ]   = $component['points'];
		}
	}

	$score += trh_trust_ledger_total( $post_id );

	update_post_meta( $post_id, 'trh_trust_score', $score );
	update_post_meta( $post_id, 'trh_trust_awards', $awards );

	return $score;
}

/** Stored Trust Score for a profile. */
function trh_trust_score( $post_id ) {
	return (int) get_post_meta( $post_id, 'trh_trust_score', true );
}

/**
 * Rows for the score breakdown UI: component data plus earned state and the
 * signup step that fills it in.
 *
 * @return array<int, array>
 */
function trh_trust_rows( $post_id ) {
	$earned = trh_trust_earned( $post_id );
	$rows   = array();
	foreach ( trh_trust_components() as $key => $component ) {
		$component['key']    = $key;
		$component['earned'] = ! empty( $earned[ $key ] );
		$rows[]              = $component;
	}
	return $rows;
}

/** Points still sitting on the table. */
function trh_trust_available( $post_id ) {
	$left = 0;
	foreach ( trh_trust_rows( $post_id ) as $row ) {
		if ( ! $row['earned'] ) {
			$left += $row['points'];
		}
	}
	return $left;
}

/* -------------------------------------------------------------------------
 * Profile accessors
 * ---------------------------------------------------------------------- */

/** The caretaker post ID owned by a user, or 0. */
function trh_hand_profile_id( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	if ( ! $user_id ) {
		return 0;
	}
	$post_id = (int) get_user_meta( $user_id, 'trh_caretaker_id', true );
	if ( ! $post_id ) {
		return 0;
	}
	$post = get_post( $post_id );
	if ( ! $post || 'caretaker' !== $post->post_type || 'trash' === $post->post_status ) {
		return 0;
	}
	return $post_id;
}

/** Read a trh_-prefixed profile meta field. */
function trh_hand_field( $post_id, $key, $default = '' ) {
	$value = get_post_meta( $post_id, 'trh_' . $key, true );
	return ( '' === $value || null === $value ) ? $default : $value;
}

/** Linked social URLs: sub-key => URL, empty ones dropped. */
function trh_hand_socials( $post_id ) {
	$socials = get_post_meta( $post_id, 'trh_socials', true );
	if ( ! is_array( $socials ) ) {
		return array();
	}
	return array_filter( array_map( 'strval', $socials ) );
}

/**
 * References: a list of ['name','relationship','phone','email'] rows.
 *
 * @return array<int, array<string,string>>
 */
function trh_hand_references( $post_id ) {
	$refs = get_post_meta( $post_id, 'trh_references', true );
	if ( ! is_array( $refs ) ) {
		return array();
	}
	$out = array();
	foreach ( $refs as $ref ) {
		if ( is_array( $ref ) && ! empty( $ref['name'] ) ) {
			$out[] = $ref;
		}
	}
	return $out;
}

/** Ticked experience keys, filtered to ones the checklist still defines. */
function trh_hand_experience( $post_id ) {
	$keys = get_post_meta( $post_id, 'trh_experience', true );
	if ( ! is_array( $keys ) ) {
		return array();
	}
	return array_values( array_intersect( $keys, array_keys( trh_experience_labels() ) ) );
}

/** Attachment URL of an uploaded resume, or ''. */
function trh_hand_resume_url( $post_id ) {
	$attachment_id = (int) trh_hand_field( $post_id, 'resume_id' );
	if ( ! $attachment_id ) {
		return '';
	}
	$url = wp_get_attachment_url( $attachment_id );
	return $url ? $url : '';
}

/** Highest signup step the Hand has completed (0-3). */
function trh_hand_step_done( $post_id ) {
	return (int) trh_hand_field( $post_id, 'signup_step', 0 );
}

/** True once all three steps have been submitted. */
function trh_hand_is_complete( $post_id ) {
	return trh_hand_step_done( $post_id ) >= 3;
}

/** Human label + tone for the review state of a profile. */
function trh_hand_status( $post_id ) {
	$status = get_post_status( $post_id );
	if ( 'publish' === $status ) {
		return array( 'label' => 'Live in the directory', 'tone' => 'sage' );
	}
	if ( ! trh_hand_is_complete( $post_id ) ) {
		return array( 'label' => 'Profile unfinished', 'tone' => 'hay' );
	}
	return array( 'label' => 'Under review', 'tone' => 'hay' );
}

/** Display name for a Hand profile. */
function trh_hand_name( $post_id ) {
	$first = trh_hand_field( $post_id, 'first_name' );
	$last  = trh_hand_field( $post_id, 'last_name' );
	$name  = trim( $first . ' ' . $last );
	return $name ? $name : get_the_title( $post_id );
}

/* -------------------------------------------------------------------------
 * Shared UI partials (used by the wizard and the dashboard)
 * ---------------------------------------------------------------------- */

/** A "+15 points" chip that sits next to an optional field's label. */
function trh_points_pill( $points, $earned = false ) {
	printf(
		'<span class="pts-pill%s">%s%d points</span>',
		$earned ? ' is-earned' : '',
		$earned ? '✓ ' : '+',
		(int) $points
	);
}

/** Circular Trust Score dial. $size is a CSS length. */
function trh_trust_dial( $score, $size = '9rem' ) {
	$score = (int) $score;
	$max   = trh_trust_max();
	$pct   = $max ? min( 100, round( ( $score / $max ) * 100 ) ) : 0;

	// Awards earned after signup push past the signup maximum. The ring simply
	// stays full rather than pretending 145 is "out of 130".
	$label = $score > $max
		? sprintf( 'Trust Score %d points', $score )
		: sprintf( 'Trust Score %d out of %d points', $score, $max );
	?>
	<div class="score-dial" style="--pct:<?php echo esc_attr( $pct ); ?>;--dial:<?php echo esc_attr( $size ); ?>;" role="img"
		aria-label="<?php echo esc_attr( $label ); ?>">
		<div class="score-dial-inner">
			<span class="score-dial-num"><?php echo esc_html( (int) $score ); ?></span>
			<span class="score-dial-cap">Trust Score</span>
		</div>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * wp-admin: let the owner review what a Hand submitted
 * ---------------------------------------------------------------------- */

add_action( 'add_meta_boxes', 'trh_hand_meta_box' );
function trh_hand_meta_box() {
	add_meta_box( 'trh_hand_details', 'Hand signup details', 'trh_render_hand_meta_box', 'caretaker', 'normal', 'high' );
}

function trh_render_hand_meta_box( $post ) {
	$user_id = (int) trh_hand_field( $post->ID, 'user_id' );
	if ( ! $user_id ) {
		echo '<p>This caretaker was created in wp-admin, not through the Hand signup flow.</p>';
		return;
	}

	$refs   = trh_hand_references( $post->ID );
	$keys   = trh_hand_experience( $post->ID );
	$labels = trh_experience_labels();
	$resume = trh_hand_resume_url( $post->ID );

	$earned = trh_trust_ledger_total( $post->ID );
	echo '<p><strong>Trust Score:</strong> ' . esc_html( trh_trust_score( $post->ID ) )
		. ( $earned
			? ' <span class="description">(' . esc_html( trh_trust_score( $post->ID ) - $earned ) . ' from signup, ' . esc_html( $earned ) . ' earned since)</span>'
			: ' / ' . esc_html( trh_trust_max() ) )
		. '</p>';
	echo '<p><strong>Signup completed:</strong> ' . ( trh_hand_is_complete( $post->ID ) ? 'yes, all 3 steps' : 'no, stopped after step ' . esc_html( trh_hand_step_done( $post->ID ) ) ) . '</p>';
	echo '<p><strong>Username:</strong> ' . esc_html( trh_hand_field( $post->ID, 'username' ) ) . '</p>';
	echo '<p><strong>Email:</strong> ' . esc_html( trh_hand_field( $post->ID, 'email' ) ) . ' &nbsp; <strong>Phone:</strong> ' . esc_html( trh_hand_field( $post->ID, 'phone' ) ) . '</p>';
	echo '<p><strong>Location:</strong> ' . esc_html( trh_hand_field( $post->ID, 'location' ) );
	$zip = trh_hand_field( $post->ID, 'zip' );
	if ( $zip ) {
		echo ' ' . esc_html( $zip );
	}
	echo '</p>';

	if ( $resume ) {
		echo '<p><strong>Resume:</strong> <a href="' . esc_url( $resume ) . '" target="_blank" rel="noopener">download</a></p>';
	}

	$socials = trh_hand_socials( $post->ID );
	if ( $socials ) {
		echo '<p><strong>Social:</strong> ';
		$parts = array();
		foreach ( $socials as $key => $url ) {
			$parts[] = '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( $key ) . '</a>';
		}
		echo wp_kses_post( implode( ' &middot; ', $parts ) ) . '</p>';
	}

	if ( $refs ) {
		echo '<p><strong>References</strong></p><ul style="margin-left:1.25em;list-style:disc;">';
		foreach ( $refs as $ref ) {
			$bits = array_filter(
				array(
					isset( $ref['name'] ) ? $ref['name'] : '',
					isset( $ref['relationship'] ) ? $ref['relationship'] : '',
					isset( $ref['phone'] ) ? $ref['phone'] : '',
					isset( $ref['email'] ) ? $ref['email'] : '',
				)
			);
			echo '<li>' . esc_html( implode( ' / ', $bits ) ) . '</li>';
		}
		echo '</ul>';
	}

	if ( $keys ) {
		echo '<p><strong>Experience (' . count( $keys ) . ')</strong><br />';
		$named = array();
		foreach ( $keys as $key ) {
			$named[] = isset( $labels[ $key ] ) ? $labels[ $key ] : $key;
		}
		echo esc_html( implode( ', ', $named ) ) . '</p>';
	}

	echo '<p class="description">Publish this post to put the Hand live in the /sitters/ directory. Rates and availability are still set here in wp-admin.</p>';
}

/** Trust Score column on the Caretakers list, so applications sort at a glance. */
add_filter( 'manage_caretaker_posts_columns', 'trh_caretaker_columns' );
function trh_caretaker_columns( $columns ) {
	$columns['trh_trust'] = 'Trust Score';
	return $columns;
}

add_action( 'manage_caretaker_posts_custom_column', 'trh_caretaker_column_content', 10, 2 );
function trh_caretaker_column_content( $column, $post_id ) {
	if ( 'trh_trust' !== $column ) {
		return;
	}
	if ( ! trh_hand_field( $post_id, 'user_id' ) ) {
		echo '<span aria-hidden="true">-</span>';
		return;
	}
	$earned = trh_trust_ledger_total( $post_id );
	echo esc_html( $earned ? trh_trust_score( $post_id ) . ' (+' . $earned . ' earned)' : trh_trust_score( $post_id ) . ' / ' . trh_trust_max() );
}
