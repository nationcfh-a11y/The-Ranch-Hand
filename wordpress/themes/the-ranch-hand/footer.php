<?php
/**
 * Footer.
 *
 * @package The_Ranch_Hand
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$trh_dir = trh_directory_url();
?>
</main>

<footer class="site-footer">
	<div class="container-rh footer footer-grid">
		<div class="footer-brand">
			<div class="brand">
				<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/rh-mark.png' ); ?>" alt="" style="height:2.25rem;width:auto;" />
				<span class="brand-name">The Ranch Hand</span>
			</div>
			<p style="margin-top:.75rem;max-width:20rem;font-size:.875rem;line-height:1.6;">
				Trusted horse sitters, farm sitters, and livestock caretakers. Book a barn sitter, equine sitter, or ranch sitter who knows the difference between a halter and a hackamore.
			</p>
		</div>

		<div>
			<h4>Services</h4>
			<ul class="footer-list">
				<?php
				$svc = trh_services();
				$i = 0;
				foreach ( $svc as $key => $s ) {
					if ( $i++ >= 5 ) { break; }
					$url = add_query_arg( 'service', $key, $trh_dir );
					echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $s['label'] ) . '</a></li>';
				}
				?>
			</ul>
		</div>

		<div>
			<h4>Animals</h4>
			<ul class="footer-list">
				<?php
				foreach ( trh_animals() as $name => $a ) {
					$url = add_query_arg( 'animal', rawurlencode( $name ), $trh_dir );
					echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a></li>';
				}
				?>
			</ul>
		</div>

		<div>
			<h4>Company</h4>
			<ul class="footer-list">
				<li><a href="<?php echo esc_url( trh_page_url( 'ranch-signup' ) ); ?>">Register Your Ranch</a></li>
					<li><a href="<?php echo esc_url( home_url( '/become-a-caretaker/' ) ); ?>">Become a Caretaker</a></li>
				<li><a href="<?php echo esc_url( $trh_dir ); ?>">Find a Sitter</a></li>
				<?php if ( has_nav_menu( 'footer_company' ) ) : ?>
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'footer_company',
							'container'      => false,
							'items_wrap'     => '%3$s',
							'walker'         => new TRH_Link_Walker(),
							'fallback_cb'    => false,
						)
					);
					?>
				<?php endif; ?>
			</ul>
		</div>
	</div>

	<div class="footer-bottom">
		<div class="container-rh inner">
			<p>© <?php echo esc_html( gmdate( 'Y' ) ); ?> The Ranch Hand. Trusted equine, livestock &amp; ranch care.</p>
			<p>Find a sitter who knows horses.</p>
			<nav class="footer-legal" aria-label="Legal">
				<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Contact</a>
				<a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">Privacy Policy</a>
				<a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>">Terms of Service</a>
			</nav>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
