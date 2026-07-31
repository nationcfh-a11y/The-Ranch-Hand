<?php
/**
 * Header + sticky navigation.
 *
 * @package The_Ranch_Hand
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$trh_dir = trh_directory_url();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<link rel="icon" href="<?php echo esc_url( get_template_directory_uri() . '/assets/img/favicon-32.png' ); ?>" type="image/png" sizes="32x32" />
	<link rel="apple-touch-icon" href="<?php echo esc_url( get_template_directory_uri() . '/assets/img/apple-touch-icon.png' ); ?>" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
	<nav class="container-rh nav" aria-label="Primary">
		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="The Ranch Hand home">
			<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/rh-mark.png' ); ?>" alt="" />
			<span style="display:flex;flex-direction:column;">
				<span class="brand-name">The Ranch Hand</span>
				<span class="brand-sub">EQUINE &amp; FARM CARE</span>
			</span>
		</a>

		<div class="nav-links">
			<?php if ( has_nav_menu( 'primary' ) ) : ?>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'items_wrap'     => '%3$s',
						'walker'         => new TRH_Link_Walker(),
						'fallback_cb'    => false,
					)
				);
				?>
			<?php else : ?>
				<a class="nav-link" href="<?php echo esc_url( $trh_dir ); ?>">Register Your Ranch</a>
				<a class="nav-link" href="<?php echo esc_url( home_url( '/become-a-caretaker/' ) ); ?>">Become A Hand</a>
			<?php endif; ?>
			<a class="btn btn-primary btn-sm" href="<?php echo esc_url( $trh_dir ); ?>">Find a Sitter</a>
		</div>

		<button class="nav-toggle" id="trh-nav-toggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="trh-mobile-menu">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/></svg>
		</button>
	</nav>

	<div class="mobile-menu" id="trh-mobile-menu">
		<div class="container-rh mobile-menu-inner">
			<a class="nav-link" href="<?php echo esc_url( $trh_dir ); ?>">Register Your Ranch</a>
			<a class="nav-link" href="<?php echo esc_url( home_url( '/become-a-caretaker/' ) ); ?>">Become A Hand</a>
			<a class="btn btn-primary" style="margin-top:.5rem;" href="<?php echo esc_url( $trh_dir ); ?>">Find a Sitter</a>
		</div>
	</div>
</header>

<main id="content">
