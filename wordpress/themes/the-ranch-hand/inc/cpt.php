<?php
/**
 * Domain model for The Ranch Hand: the "Caretaker" custom post type, the
 * Animal and Service taxonomies, and the shared constants/helpers that mirror
 * server/src/constants.js + client/src/lib/constants.js from the original app.
 *
 * @package The_Ranch_Hand
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * Domain constants (ported 1:1 from the React/Express app)
 * ---------------------------------------------------------------------- */

/** The six services modeled. key => [label, unit, blurb]. */
function trh_services() {
	return array(
		'barn_sitting' => array( 'label' => 'Farm/Barn Sitting',          'unit' => 'per visit', 'blurb' => 'Daily visits while you are away.' ),
		'overnight'    => array( 'label' => 'Overnight Care',             'unit' => 'per night', 'blurb' => 'Caretaker stays overnight on-site.' ),
		'drop_in'      => array( 'label' => 'Drop-in Feeding & Watering', 'unit' => 'per visit', 'blurb' => 'Quick feed, water, and welfare check.' ),
		'turnout'      => array( 'label' => 'Turnout / Exercise',         'unit' => 'per visit', 'blurb' => 'Turnout, lunging, and exercise.' ),
		'mucking'      => array( 'label' => 'Mucking & Stall Cleaning',   'unit' => 'per stall', 'blurb' => 'Muck out and refresh bedding.' ),
		'medication'   => array( 'label' => 'Medication Administration',  'unit' => 'per visit', 'blurb' => 'Administer meds per your vet plan.' ),
	);
}

function trh_service_label( $key ) {
	$s = trh_services();
	return isset( $s[ $key ] ) ? $s[ $key ]['label'] : $key;
}

function trh_service_unit( $key ) {
	$s = trh_services();
	return isset( $s[ $key ] ) ? $s[ $key ]['unit'] : '';
}

/** Animal categories with emoji icons + blurbs (used on the homepage). */
function trh_animals() {
	return array(
		'Horses'      => array( 'icon' => '🐴', 'blurb' => 'Trail horses, show horses, seniors & green stock.' ),
		'Cattle'      => array( 'icon' => '🐄', 'blurb' => 'Beef & dairy herds, feeding rotations, water checks.' ),
		'Goats/Sheep' => array( 'icon' => '🐐', 'blurb' => 'Small ruminants, milking, kidding & lambing.' ),
		'Pigs'        => array( 'icon' => '🐖', 'blurb' => 'Pot-bellies to pasture pigs, feeding & wallows.' ),
		'Poultry'     => array( 'icon' => '🐓', 'blurb' => 'Chickens, ducks & turkeys; eggs & coop care.' ),
		'Mixed Farm'  => array( 'icon' => '🚜', 'blurb' => 'A little of everything — whole-farm sitting.' ),
	);
}

/** Simulated fee model (mirrors Rover's structure). */
const TRH_OWNER_SERVICE_FEE_RATE   = 0.07; // owner pays 7% on top
const TRH_CARETAKER_COMMISSION_RATE = 0.15; // platform keeps 15%

/* -------------------------------------------------------------------------
 * Custom post type + taxonomies
 * ---------------------------------------------------------------------- */

add_action( 'init', 'trh_register_caretaker_cpt' );
function trh_register_caretaker_cpt() {
	register_post_type(
		'caretaker',
		array(
			'labels'       => array(
				'name'               => 'Caretakers',
				'singular_name'      => 'Caretaker',
				'add_new_item'       => 'Add New Caretaker',
				'edit_item'          => 'Edit Caretaker',
				'new_item'           => 'New Caretaker',
				'view_item'          => 'View Caretaker',
				'search_items'       => 'Search Caretakers',
				'not_found'          => 'No caretakers found',
				'menu_name'          => 'Caretakers',
			),
			'public'       => true,
			'has_archive'  => true,
			'menu_icon'    => 'dashicons-groups',
			'rewrite'      => array( 'slug' => 'sitters', 'with_front' => false ),
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'show_in_rest' => true,
		)
	);

	// Animal specialties.
	register_taxonomy(
		'animal',
		'caretaker',
		array(
			'labels'            => array( 'name' => 'Animals', 'singular_name' => 'Animal' ),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'animal' ),
			'show_in_rest'      => true,
		)
	);

	// Services offered.
	register_taxonomy(
		'service',
		'caretaker',
		array(
			'labels'            => array( 'name' => 'Services', 'singular_name' => 'Service' ),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'service' ),
			'show_in_rest'      => true,
		)
	);
}

/* -------------------------------------------------------------------------
 * Per-caretaker meta accessors
 * ---------------------------------------------------------------------- */

/** Photo URL: featured image if set, else the stored photo_url meta, else a fallback. */
function trh_caretaker_photo( $post_id ) {
	if ( has_post_thumbnail( $post_id ) ) {
		return get_the_post_thumbnail_url( $post_id, 'large' );
	}
	$url = get_post_meta( $post_id, 'trh_photo_url', true );
	return $url ? $url : get_template_directory_uri() . '/assets/img/rh-mark.png';
}

function trh_caretaker_location( $post_id ) {
	return get_post_meta( $post_id, 'trh_location', true );
}

function trh_caretaker_experience( $post_id ) {
	return (int) get_post_meta( $post_id, 'trh_experience_years', true );
}

function trh_caretaker_headline( $post_id ) {
	return get_post_meta( $post_id, 'trh_headline', true );
}

function trh_caretaker_availability( $post_id ) {
	return get_post_meta( $post_id, 'trh_availability_notes', true );
}

function trh_caretaker_rating( $post_id ) {
	$r = get_post_meta( $post_id, 'trh_rating', true );
	return $r ? (float) $r : 0;
}

function trh_caretaker_review_count( $post_id ) {
	return (int) get_post_meta( $post_id, 'trh_review_count', true );
}

/** Rates map: service_key => price (numeric). */
function trh_caretaker_rates( $post_id ) {
	$rates = get_post_meta( $post_id, 'trh_rates', true );
	return is_array( $rates ) ? $rates : array();
}

/** Lowest rate across all services offered — used for "from $X" on cards. */
function trh_caretaker_from_price( $post_id ) {
	$rates = trh_caretaker_rates( $post_id );
	$vals  = array_filter( array_map( 'floatval', array_values( $rates ) ) );
	return $vals ? min( $vals ) : 0;
}
