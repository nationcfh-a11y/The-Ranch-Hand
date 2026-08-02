<?php
/**
 * The Ranch Hand theme bootstrap.
 *
 * @package The_Ranch_Hand
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TRH_VERSION', '1.0.1' );

require get_template_directory() . '/inc/cpt.php';
require get_template_directory() . '/inc/seed.php';
require get_template_directory() . '/inc/forms.php';

/* -------------------------------------------------------------------------
 * Theme setup
 * ---------------------------------------------------------------------- */
add_action( 'after_setup_theme', 'trh_setup' );
function trh_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', array( 'height' => 48, 'width' => 200, 'flex-width' => true, 'flex-height' => true ) );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );

	register_nav_menus(
		array(
			'primary' => 'Primary Navigation',
			'footer_company' => 'Footer: Company',
		)
	);
}

/* -------------------------------------------------------------------------
 * Styles + fonts
 * ---------------------------------------------------------------------- */
add_action( 'wp_enqueue_scripts', 'trh_assets' );
function trh_assets() {
	// Bitter (display) + Nunito Sans (body), same as the React app.
	wp_enqueue_style(
		'trh-fonts',
		'https://fonts.googleapis.com/css2?family=Bitter:wght@500;600;700&family=Nunito+Sans:wght@400;600;700;800&display=swap',
		array(),
		null
	);
	wp_enqueue_style( 'trh-theme', get_template_directory_uri() . '/assets/css/theme.css', array( 'trh-fonts' ), TRH_VERSION );
	wp_enqueue_script( 'trh-theme', get_template_directory_uri() . '/assets/js/theme.js', array(), TRH_VERSION, true );
}

/** Preconnect to Google Fonts (matches the app's index.html). */
add_filter( 'wp_resource_hints', 'trh_resource_hints', 10, 2 );
function trh_resource_hints( $hints, $relation ) {
	if ( 'preconnect' === $relation ) {
		$hints[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' );
	}
	return $hints;
}

/* -------------------------------------------------------------------------
 * Customizer: a few easy-to-edit content knobs for the owner
 * ---------------------------------------------------------------------- */
add_action( 'customize_register', 'trh_customize' );
function trh_customize( $wp_customize ) {
	$wp_customize->add_section( 'trh_hero', array( 'title' => 'Ranch Hand: Homepage Hero', 'priority' => 30 ) );

	$fields = array(
		'trh_hero_eyebrow'  => array( 'label' => 'Hero eyebrow badge', 'default' => '🐴 Equine, livestock & ranch care, done right' ),
		'trh_hero_title'    => array( 'label' => 'Hero headline', 'default' => 'Find a horse sitter or farm sitter you can trust.' ),
		'trh_hero_subtitle' => array( 'label' => 'Hero subheadline', 'default' => 'The Ranch Hand connects horse and farm-animal owners with experienced, reviewed sitters. Book horse sitting, farm sitting, and barn sitting services with an equine, livestock, or ranch sitter for overnights, feeding, turnout, mucking, and meds.', 'type' => 'textarea' ),
		'trh_hero_image'    => array( 'label' => 'Hero background image URL', 'default' => 'https://images.unsplash.com/photo-1553284965-83fd3e82fa5a?auto=format&fit=crop&w=1600&q=70' ),
	);

	foreach ( $fields as $id => $f ) {
		$wp_customize->add_setting( $id, array( 'default' => $f['default'], 'sanitize_callback' => 'wp_kses_post' ) );
		$wp_customize->add_control(
			$id,
			array(
				'label'   => $f['label'],
				'section' => 'trh_hero',
				'type'    => isset( $f['type'] ) ? $f['type'] : 'text',
			)
		);
	}
}

function trh_opt( $key, $fallback = '' ) {
	$v = get_theme_mod( $key, $fallback );
	return $v !== '' ? $v : $fallback;
}

/* -------------------------------------------------------------------------
 * Template helpers
 * ---------------------------------------------------------------------- */

/** Render a hay-star rating row (mirrors the React <Stars/> component). */
function trh_stars( $value, $count = null ) {
	$value = (float) $value;
	$full  = (int) floor( $value + 0.001 );
	$half  = ( $value - $full ) >= 0.5;
	echo '<span class="stars" aria-label="' . esc_attr( number_format( $value, 1 ) ) . ' out of 5 stars">';
	for ( $i = 1; $i <= 5; $i++ ) {
		if ( $i <= $full ) {
			echo '<span class="star" aria-hidden="true">★</span>';
		} elseif ( $half && $i === $full + 1 ) {
			echo '<span class="star" aria-hidden="true">★</span>';
		} else {
			echo '<span class="star empty" aria-hidden="true">☆</span>';
		}
	}
	echo ' <span class="num">' . esc_html( number_format( $value, 1 ) ) . '</span>';
	if ( null !== $count ) {
		echo ' <span class="count">(' . esc_html( (int) $count ) . ')</span>';
	}
	echo '</span>';
}

/** Render a single caretaker card (used on homepage + directory). */
function trh_caretaker_card( $post_id ) {
	$photo    = trh_caretaker_photo( $post_id );
	$name     = get_the_title( $post_id );
	$location = trh_caretaker_location( $post_id );
	$headline = trh_caretaker_headline( $post_id );
	$from     = trh_caretaker_from_price( $post_id );
	$rating   = trh_caretaker_rating( $post_id );
	$reviews  = trh_caretaker_review_count( $post_id );
	$animals  = wp_get_object_terms( $post_id, 'animal', array( 'fields' => 'names' ) );
	$link     = get_permalink( $post_id );
	?>
	<a class="card card-hover ck-card" href="<?php echo esc_url( $link ); ?>">
		<img class="ck-photo" src="<?php echo esc_url( $photo ); ?>" alt="<?php echo esc_attr( $name ); ?>, caretaker in <?php echo esc_attr( $location ); ?>" loading="lazy" />
		<div class="ck-body">
			<div class="flex-between" style="align-items:flex-start;">
				<div>
					<div class="ck-name"><?php echo esc_html( $name ); ?></div>
					<div class="ck-loc">📍 <?php echo esc_html( $location ); ?></div>
				</div>
			</div>
			<?php trh_stars( $rating, $reviews ); ?>
			<p class="ck-headline"><?php echo esc_html( $headline ); ?></p>
			<div class="ck-tags">
				<?php foreach ( array_slice( (array) $animals, 0, 3 ) as $a ) : ?>
					<span class="badge badge-service"><?php echo esc_html( $a ); ?></span>
				<?php endforeach; ?>
			</div>
			<div class="ck-foot">
				<span class="ck-price">$<?php echo esc_html( (int) $from ); ?><span> / from</span></span>
				<span class="btn btn-primary btn-sm">View profile</span>
			</div>
		</div>
	</a>
	<?php
}

/** Format money like the app's money() helper. */
function trh_money( $n ) {
	return '$' . number_format( (float) $n, 0 );
}

/** URL of the caretaker directory archive. */
function trh_directory_url() {
	$url = get_post_type_archive_link( 'caretaker' );
	return $url ? $url : home_url( '/sitters/' );
}

/**
 * Minimal nav walker: renders top-level menu items as bare <a class="nav-link">
 * (no <ul>/<li>) so an admin-managed Primary menu matches the theme's styling.
 */
class TRH_Link_Walker extends Walker_Nav_Menu {
	public function start_lvl( &$output, $depth = 0, $args = null ) {}
	public function end_lvl( &$output, $depth = 0, $args = null ) {}
	public function end_el( &$output, $item, $depth = 0, $args = null ) {}
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		if ( $depth > 0 ) {
			return; // flat menu only
		}
		$classes = 'nav-link';
		if ( in_array( 'current-menu-item', (array) $item->classes, true ) ) {
			$classes .= ' is-active';
		}
		$output .= '<a class="' . esc_attr( $classes ) . '" href="' . esc_url( $item->url ) . '">' . esc_html( $item->title ) . '</a>';
	}
}
