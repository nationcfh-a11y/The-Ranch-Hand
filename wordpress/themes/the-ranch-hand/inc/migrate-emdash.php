<?php
/**
 * One-time content migration: strip em dashes (U+2014) from data already
 * stored in the database.
 *
 * The theme templates are em-dash-free in code, but caretaker bios/headlines
 * were seeded into the database on first activation (see seed.php), and any
 * Customizer hero text the owner saved lives in theme_mods. Neither is touched
 * by re-deploying the theme, so this migration rewrites them in place.
 *
 * Strategy:
 *   1. For each of the 12 known seed caretakers, restore the corrected bio and
 *      headline from trh_seed_data() (matched by name). Precise, no guessing.
 *   2. Safety sweep: any remaining em dash in caretaker content or the hero
 *      theme_mods gets a sensible mechanical replacement.
 *
 * Guarded by an option so it runs once and then no-ops. Bump the option key
 * (…_v2, _v3) if the migration ever needs to run again.
 *
 * @package The_Ranch_Hand
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', 'trh_migrate_emdash_content' );
function trh_migrate_emdash_content() {
	if ( get_option( 'trh_emdash_fixed_v1' ) ) {
		return;
	}

	// 1. Restore the corrected seed text for known caretakers (matched by name).
	if ( function_exists( 'trh_seed_data' ) ) {
		foreach ( trh_seed_data() as $c ) {
			$matches = get_posts(
				array(
					'post_type'   => 'caretaker',
					'title'       => $c['name'],
					'numberposts' => 1,
					'post_status' => 'any',
					'fields'      => 'ids',
				)
			);
			if ( empty( $matches ) ) {
				continue;
			}
			$post_id = $matches[0];
			wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => $c['bio'],
					'post_excerpt' => $c['headline'],
				)
			);
			update_post_meta( $post_id, 'trh_headline', $c['headline'] );
			if ( isset( $c['availability'] ) ) {
				update_post_meta( $post_id, 'trh_availability_notes', $c['availability'] );
			}
		}
	}

	// 2. Safety sweep for any em dash still present in caretaker content
	//    (e.g. owner-added or edited profiles the name match missed).
	$all = get_posts(
		array(
			'post_type'   => 'caretaker',
			'numberposts' => -1,
			'post_status' => 'any',
			'fields'      => 'ids',
		)
	);
	foreach ( $all as $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			continue;
		}
		$new_content = trh_strip_emdash( $post->post_content );
		$new_excerpt = trh_strip_emdash( $post->post_excerpt );
		$new_title   = trh_strip_emdash( $post->post_title );
		if ( $new_content !== $post->post_content || $new_excerpt !== $post->post_excerpt || $new_title !== $post->post_title ) {
			wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => $new_content,
					'post_excerpt' => $new_excerpt,
					'post_title'   => $new_title,
				)
			);
		}
		$headline = get_post_meta( $post_id, 'trh_headline', true );
		if ( is_string( $headline ) && false !== strpos( $headline, "\xE2\x80\x94" ) ) {
			update_post_meta( $post_id, 'trh_headline', trh_strip_emdash( $headline ) );
		}
		$avail = get_post_meta( $post_id, 'trh_availability_notes', true );
		if ( is_string( $avail ) && false !== strpos( $avail, "\xE2\x80\x94" ) ) {
			update_post_meta( $post_id, 'trh_availability_notes', trh_strip_emdash( $avail ) );
		}
	}

	// 3. Sweep Customizer hero fields the owner may have saved with em dashes.
	foreach ( array( 'trh_hero_eyebrow', 'trh_hero_title', 'trh_hero_subtitle' ) as $key ) {
		$val = get_theme_mod( $key );
		if ( is_string( $val ) && false !== strpos( $val, "\xE2\x80\x94" ) ) {
			set_theme_mod( $key, trh_strip_emdash( $val ) );
		}
	}

	update_option( 'trh_emdash_fixed_v1', 1 );
}

/**
 * Mechanical em-dash removal for content we don't have a corrected copy of.
 * A spaced em dash becomes a comma; any bare one becomes a hyphen.
 */
function trh_strip_emdash( $text ) {
	if ( ! is_string( $text ) || '' === $text ) {
		return $text;
	}
	$em = "\xE2\x80\x94"; // U+2014 in UTF-8.
	$text = str_replace( array( " $em ", "$em " , " $em" ), ', ', $text );
	$text = str_replace( $em, '-', $text );
	return $text;
}
