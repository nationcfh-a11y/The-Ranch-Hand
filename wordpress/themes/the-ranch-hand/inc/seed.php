<?php
/**
 * One-time demo seed. On theme activation, if no caretakers exist yet, insert
 * the 12 sample caretakers ported from server/src/seed.js so the site looks
 * alive immediately. Everything here is editable/deletable in wp-admin →
 * Caretakers, so the owner can swap in real people.
 *
 * @package The_Ranch_Hand
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', 'trh_ensure_pages' );
/**
 * Ensure the pages the nav links to exist, so their templates resolve and the
 * links do not 404:
 *   - become-a-caretaker -> page-become-a-caretaker.php ("Become A Hand")
 *   - ranch-signup       -> page-ranch-signup.php ("Register Your Ranch")
 *   - ranch-plans        -> page-ranch-plans.php (job-posting tiers)
 *
 * This runs on after_setup_theme behind an option guard rather than on
 * after_switch_theme: the theme is already active on the live site, so
 * after_switch_theme never fires again and the pages would never be created on
 * a plain deploy. Same pattern (and same reason) as inc/pages.php and the hand
 * pages in inc/hands.php. Bump the option key (_v4) if these ever need
 * re-creating.
 */
function trh_ensure_pages() {
	if ( get_option( 'trh_theme_pages_v3' ) ) {
		return;
	}

	$pages = array(
		'become-a-caretaker' => 'Become a Caretaker',
		'ranch-signup'       => 'Register Your Ranch',
		'ranch-plans'        => 'Ranch Job Plans',
	);

	foreach ( $pages as $slug => $title ) {
		if ( get_page_by_path( $slug, OBJECT, 'page' ) ) {
			continue; // Already there (never clobber an edited page).
		}
		wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => '', // content comes from the page template
			)
		);
	}

	update_option( 'trh_theme_pages_v3', 1 );
}

add_action( 'after_switch_theme', 'trh_seed_caretakers' );
function trh_seed_caretakers() {
	// Guard: only seed once, and only when the directory is empty.
	if ( get_option( 'trh_seeded' ) ) {
		return;
	}
	$existing = get_posts( array( 'post_type' => 'caretaker', 'numberposts' => 1, 'fields' => 'ids', 'post_status' => 'any' ) );
	if ( ! empty( $existing ) ) {
		update_option( 'trh_seeded', 1 );
		return;
	}

	$caretakers = trh_seed_data();
	foreach ( $caretakers as $c ) {
		$post_id = wp_insert_post(
			array(
				'post_type'    => 'caretaker',
				'post_status'  => 'publish',
				'post_title'   => $c['name'],
				'post_content' => $c['bio'],
				'post_excerpt' => $c['headline'],
			)
		);
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			continue;
		}

		update_post_meta( $post_id, 'trh_location', $c['location'] );
		update_post_meta( $post_id, 'trh_experience_years', $c['exp'] );
		update_post_meta( $post_id, 'trh_headline', $c['headline'] );
		update_post_meta( $post_id, 'trh_availability_notes', $c['availability'] );
		update_post_meta( $post_id, 'trh_photo_url', 'https://i.pravatar.cc/600?img=' . $c['img'] );
		update_post_meta( $post_id, 'trh_rating', $c['rating'] );
		update_post_meta( $post_id, 'trh_review_count', $c['reviews'] );
		update_post_meta( $post_id, 'trh_rates', $c['services'] );

		// Taxonomy terms.
		wp_set_object_terms( $post_id, $c['animals'], 'animal' );
		$service_labels = array_map( 'trh_service_label', array_keys( $c['services'] ) );
		wp_set_object_terms( $post_id, $service_labels, 'service' );
	}

	update_option( 'trh_seeded', 1 );
	// Flush rewrites so /sitters/ archive + single URLs resolve on first load.
	trh_register_caretaker_cpt();
	flush_rewrite_rules();
}

/** Ported caretaker data from server/src/seed.js (ratings/reviews added for display). */
function trh_seed_data() {
	return array(
		array( 'name' => 'Dale Whitaker', 'location' => 'Bozeman, MT', 'img' => 12, 'exp' => 18, 'rating' => 4.9, 'reviews' => 24,
			'headline' => 'Lifelong horseman with 18 years on working ranches.',
			'bio' => 'Grew up on a cattle and quarter-horse outfit outside Bozeman. I treat every animal like my own and send daily photo updates. Comfortable with green horses, seniors, and everything in between.',
			'animals' => array( 'Horses', 'Cattle', 'Mixed Farm' ),
			'services' => array( 'barn_sitting' => 45, 'overnight' => 110, 'turnout' => 30, 'mucking' => 18, 'medication' => 22 ),
			'availability' => 'Weekdays & weekends; book 3+ days ahead.' ),
		array( 'name' => 'Marisol Reyes', 'location' => 'Ocala, FL', 'img' => 5, 'exp' => 12, 'rating' => 5.0, 'reviews' => 31,
			'headline' => 'Equine vet tech who loves med-heavy cases.',
			'bio' => 'Certified vet tech specializing in horses. If your horse needs careful medication schedules, wound care, or special feeding, I am your sitter. Calm, detail-oriented, fully insured.',
			'animals' => array( 'Horses', 'Goats/Sheep' ),
			'services' => array( 'drop_in' => 35, 'barn_sitting' => 50, 'medication' => 28, 'turnout' => 32 ),
			'availability' => 'Available most days, including early mornings.' ),
		array( 'name' => 'Tucker Boone', 'location' => 'Stephenville, TX', 'img' => 13, 'exp' => 9, 'rating' => 4.7, 'reviews' => 18,
			'headline' => 'Rodeo background, confident with big herds.',
			'bio' => 'Team roper and ranch hand. I can manage large cattle operations, handle feeding rotations, and keep stalls spotless. No herd too big.',
			'animals' => array( 'Cattle', 'Horses', 'Mixed Farm' ),
			'services' => array( 'barn_sitting' => 40, 'overnight' => 95, 'mucking' => 15, 'turnout' => 28 ),
			'availability' => 'Flexible; overnights preferred.' ),
		array( 'name' => 'Hannah Pelletier', 'location' => 'Burlington, VT', 'img' => 9, 'exp' => 7, 'rating' => 4.8, 'reviews' => 15,
			'headline' => 'Goats, sheep & poultry specialist.',
			'bio' => 'I run a small homestead and adore small ruminants and birds. Kidding season, milking, coop care: I have done it all. Gentle with skittish animals.',
			'animals' => array( 'Goats/Sheep', 'Poultry', 'Pigs' ),
			'services' => array( 'drop_in' => 28, 'barn_sitting' => 38, 'mucking' => 14, 'medication' => 20 ),
			'availability' => 'Mornings and evenings daily.' ),
		array( 'name' => 'Cole Anderson', 'location' => 'Bend, OR', 'img' => 8, 'exp' => 14, 'rating' => 4.9, 'reviews' => 27,
			'headline' => 'Overnight specialist for nervous owners.',
			'bio' => 'I stay on-site so your animals are never alone. Experienced with horses and mixed farms. Light maintenance, security checks, and lots of TLC included.',
			'animals' => array( 'Horses', 'Mixed Farm', 'Cattle' ),
			'services' => array( 'overnight' => 120, 'barn_sitting' => 48, 'turnout' => 34, 'medication' => 25 ),
			'availability' => 'Overnights any night; 2-night minimum.' ),
		array( 'name' => 'Priya Nair', 'location' => 'Lexington, KY', 'img' => 16, 'exp' => 11, 'rating' => 5.0, 'reviews' => 22,
			'headline' => 'Thoroughbred barn manager, Bluegrass born.',
			'bio' => 'Managed a Lexington racing barn for a decade. Expert turnout, feeding programs, and conditioning. Your performance horses are in expert hands.',
			'animals' => array( 'Horses' ),
			'services' => array( 'barn_sitting' => 60, 'turnout' => 40, 'mucking' => 20, 'medication' => 30, 'overnight' => 140 ),
			'availability' => 'Weekdays; limited weekend slots.' ),
		array( 'name' => 'Jesse Hartman', 'location' => 'Boise, ID', 'img' => 11, 'exp' => 6, 'rating' => 4.6, 'reviews' => 12,
			'headline' => 'Reliable drop-ins and feedings, rain or shine.',
			'bio' => 'Dependable, punctual, and great with routines. I handle drop-in feeding, watering, and welfare checks for horses, pigs, and poultry across the Treasure Valley.',
			'animals' => array( 'Pigs', 'Poultry', 'Horses' ),
			'services' => array( 'drop_in' => 25, 'barn_sitting' => 36, 'mucking' => 13 ),
			'availability' => 'Available every day, twice daily.' ),
		array( 'name' => 'Savannah Brooks', 'location' => 'Asheville, NC', 'img' => 20, 'exp' => 8, 'rating' => 4.8, 'reviews' => 19,
			'headline' => 'Holistic care for horses & mixed farms.',
			'bio' => 'Natural-horsemanship approach with a soft, patient hand. Comfortable with barefoot trims coordination, herbal feeds, and rescue animals that need extra patience.',
			'animals' => array( 'Horses', 'Goats/Sheep', 'Mixed Farm' ),
			'services' => array( 'barn_sitting' => 44, 'overnight' => 100, 'turnout' => 30, 'medication' => 24, 'drop_in' => 30 ),
			'availability' => 'Most days; flexible scheduling.' ),
		array( 'name' => 'Garrett Maddox', 'location' => 'Cheyenne, WY', 'img' => 33, 'exp' => 22, 'rating' => 4.9, 'reviews' => 41,
			'headline' => 'Old-school cowboy, 22 years with cattle & horses.',
			'bio' => 'If it has hooves, I have cared for it. Two decades on Wyoming ranches. Winter-hardy, early riser, and unflappable in a storm. Big operations welcome.',
			'animals' => array( 'Cattle', 'Horses', 'Mixed Farm' ),
			'services' => array( 'barn_sitting' => 42, 'overnight' => 105, 'mucking' => 16, 'turnout' => 29, 'medication' => 23 ),
			'availability' => 'Year-round; overnights a specialty.' ),
		array( 'name' => 'Emily Sato', 'location' => 'Petaluma, CA', 'img' => 25, 'exp' => 5, 'rating' => 4.7, 'reviews' => 14,
			'headline' => 'Poultry & pig pro on the dairy coast.',
			'bio' => 'Sonoma County small-farm sitter. Chickens, ducks, turkeys, and pigs are my jam. Egg collection, coop cleaning, and friendly company for your flock.',
			'animals' => array( 'Poultry', 'Pigs', 'Goats/Sheep' ),
			'services' => array( 'drop_in' => 26, 'barn_sitting' => 34, 'mucking' => 12 ),
			'availability' => 'Daily morning visits; some evenings.' ),
		array( 'name' => 'Wyatt Coleman', 'location' => 'Franklin, TN', 'img' => 51, 'exp' => 10, 'rating' => 4.8, 'reviews' => 20,
			'headline' => 'Show-barn turnout & exercise specialist.',
			'bio' => 'I keep show horses fit and happy. Structured turnout, lunging, hand-walking, and meticulous stall care. Great with hot-blooded horses that need a steady routine.',
			'animals' => array( 'Horses', 'Cattle' ),
			'services' => array( 'turnout' => 38, 'barn_sitting' => 50, 'mucking' => 19, 'overnight' => 115, 'medication' => 26 ),
			'availability' => 'Weekdays & weekends by request.' ),
		array( 'name' => 'Nora Kelly', 'location' => 'Missoula, MT', 'img' => 47, 'exp' => 13, 'rating' => 5.0, 'reviews' => 33,
			'headline' => 'Whole-farm sitter: horses to honeybees.',
			'bio' => 'Run a diversified family farm and love the variety. Horses, cattle, goats, pigs, poultry: I keep the whole place humming while you are away. Daily detailed reports.',
			'animals' => array( 'Mixed Farm', 'Horses', 'Cattle', 'Goats/Sheep', 'Poultry' ),
			'services' => array( 'barn_sitting' => 46, 'overnight' => 108, 'drop_in' => 30, 'turnout' => 31, 'mucking' => 17, 'medication' => 24 ),
			'availability' => 'Available most days; loves long stays.' ),
	);
}
