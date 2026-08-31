<?php
/**
 * Header + sticky navigation.
 *
 * One file, three headers (see trh_header_mode()):
 *
 *   signup - the logo and the Hand's Trust Score, nothing else. Anything that
 *            could pull them off the wizard mid-signup does not belong here.
 *   app    - a signed-in Hand's own tool: where they work, plus their score.
 *   site   - the marketing header, for owners and anyone not signed in.
 *
 * @package The_Ranch_Hand
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$trh_mode      = trh_header_mode();
$trh_is_hand   = is_user_logged_in() && (bool) trh_hand_profile_id();
$trh_dir       = trh_directory_url();
$trh_ranch     = trh_page_url( 'ranch-signup' );
$trh_acct_url  = trh_dashboard_url();
$trh_acct_text = $trh_is_hand ? 'My Dashboard' : 'Sign In';
$trh_app_nav   = 'app' === $trh_mode ? trh_app_nav_items() : array();
$trh_bare      = 'signup' === $trh_mode;
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
<body <?php body_class( 'trh-header-' . $trh_mode ); ?>>
<?php wp_body_open(); ?>

<header class="site-header<?php echo $trh_bare ? ' site-header-bare' : ''; ?>">
	<nav class="container-rh nav" aria-label="Primary">
		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="The Ranch Hand home">
			<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/rh-mark.png' ); ?>" alt="" />
			<span style="display:flex;flex-direction:column;">
				<span class="brand-name">The Ranch Hand</span>
				<span class="brand-sub">EQUINE &amp; FARM CARE</span>
			</span>
		</a>

		<div class="nav-right">
			<?php if ( 'app' === $trh_mode ) : ?>
				<div class="nav-links">
					<?php foreach ( $trh_app_nav as $trh_item ) : ?>
						<a class="nav-link<?php echo is_page( $trh_item['match'] ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( $trh_item['url'] ); ?>"><?php echo esc_html( $trh_item['label'] ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php elseif ( 'site' === $trh_mode ) : ?>
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
						<a class="nav-link" href="<?php echo esc_url( $trh_ranch ); ?>">Register Your Ranch</a>
						<a class="nav-link" href="<?php echo esc_url( home_url( '/become-a-caretaker/' ) ); ?>">Become A Hand</a>
					<?php endif; ?>
					<a class="nav-link" href="<?php echo esc_url( $trh_acct_url ); ?>"><?php echo esc_html( $trh_acct_text ); ?></a>
					<a class="btn btn-primary btn-sm" href="<?php echo esc_url( $trh_dir ); ?>">Find a Sitter</a>
				</div>
			<?php endif; ?>

			<?php if ( $trh_is_hand ) { trh_trust_board(); } ?>

			<?php if ( ! $trh_bare ) : ?>
				<button class="nav-toggle" id="trh-nav-toggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="trh-mobile-menu">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/></svg>
				</button>
			<?php endif; ?>
		</div>
	</nav>

	<?php if ( ! $trh_bare ) : ?>
		<div class="mobile-menu" id="trh-mobile-menu">
			<div class="container-rh mobile-menu-inner">
				<?php if ( 'app' === $trh_mode ) : ?>
					<?php foreach ( $trh_app_nav as $trh_item ) : ?>
						<a class="nav-link<?php echo is_page( $trh_item['match'] ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( $trh_item['url'] ); ?>"><?php echo esc_html( $trh_item['label'] ); ?></a>
					<?php endforeach; ?>
					<a class="nav-link nav-link-quiet" href="<?php echo esc_url( trh_hand_logout_url() ); ?>">Sign out</a>
				<?php else : ?>
					<a class="nav-link" href="<?php echo esc_url( $trh_ranch ); ?>">Register Your Ranch</a>
					<a class="nav-link" href="<?php echo esc_url( home_url( '/become-a-caretaker/' ) ); ?>">Become A Hand</a>
					<a class="nav-link" href="<?php echo esc_url( $trh_acct_url ); ?>"><?php echo esc_html( $trh_acct_text ); ?></a>
					<a class="btn btn-primary" style="margin-top:.5rem;" href="<?php echo esc_url( $trh_dir ); ?>">Find a Sitter</a>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>
</header>

<main id="content">
