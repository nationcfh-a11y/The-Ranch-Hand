<?php
/**
 * Site pages the theme owns: Contact, Privacy Policy, Terms of Service.
 *
 * These three are linked from the footer, so they have to exist or the links
 * 404. The theme was already activated on the live site, which means
 * after_switch_theme (used by seed.php) will never fire again. So this runs on
 * after_setup_theme behind an option guard, the same pattern as
 * migrate-emdash.php: it creates the pages once, then no-ops forever.
 *
 * Contact gets its layout from page-contact.php (a template), so its
 * post_content stays empty. Privacy Policy and Terms ship their text as real
 * post_content rendered by page.php, so the owner (or a lawyer) can edit the
 * wording in wp-admin without a code change and a deploy.
 *
 * Bump the option key (_v2, _v3) if these ever need to be re-created.
 *
 * @package The_Ranch_Hand
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', 'trh_ensure_site_pages' );
function trh_ensure_site_pages() {
	if ( get_option( 'trh_site_pages_v1' ) ) {
		return;
	}

	$pages = array(
		'contact'        => array( 'title' => 'Contact', 'content' => '' ),
		'privacy-policy' => array( 'title' => 'Privacy Policy', 'content' => trh_privacy_policy_content() ),
		'terms'          => array( 'title' => 'Terms of Service', 'content' => trh_terms_content() ),
	);

	$ids = array();

	foreach ( $pages as $slug => $page ) {
		$existing = get_page_by_path( $slug, OBJECT, 'page' );

		if ( $existing ) {
			$ids[ $slug ] = $existing->ID;
			// Never clobber a page the owner already published and may have edited.
			if ( 'publish' === $existing->post_status ) {
				continue;
			}
			// WordPress ships a draft "Privacy Policy" page on new sites. Fill it in.
			wp_update_post(
				array(
					'ID'           => $existing->ID,
					'post_status'  => 'publish',
					'post_title'   => $page['title'],
					'post_content' => $page['content'],
				)
			);
			continue;
		}

		$id = wp_insert_post(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'post_title'     => $page['title'],
				'post_name'      => $slug,
				'post_content'   => $page['content'],
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			$ids[ $slug ] = $id;
		}
	}

	// Point WordPress's built-in privacy tooling at our page.
	if ( ! empty( $ids['privacy-policy'] ) ) {
		update_option( 'wp_page_for_privacy_policy', (int) $ids['privacy-policy'] );
	}

	update_option( 'trh_site_pages_v1', 1 );
}

/**
 * Privacy policy copy, tailored to what this site actually does today:
 * three lead-capture forms, Jetpack stats, no accounts, no payments.
 */
function trh_privacy_policy_content() {
	$contact = esc_url( home_url( '/contact/' ) );
	$date    = date_i18n( 'F j, Y' );

	return <<<HTML
<p><em>Last updated: {$date}</em></p>

<p>The Ranch Hand ("we", "us") connects horse and farm animal owners with experienced sitters and caretakers. This policy explains what personal information we collect through this website, why we collect it, and what we do with it.</p>

<h2>Information you give us</h2>
<p>We collect information only when you choose to send it through one of our forms:</p>
<ul>
<li><strong>Booking requests.</strong> When you contact a sitter through their profile, we collect your name, email address, the service and dates you asked about, and any care details you write in the message.</li>
<li><strong>Caretaker applications.</strong> When you apply to become a caretaker, we collect your name, email address, phone number, location, and the experience you describe.</li>
<li><strong>Contact messages.</strong> When you use our contact form, we collect your name, email address, phone number if you provide one, and your message.</li>
</ul>
<p>You do not need an account to use this site, and we do not ask for payment details. We are not able to accept payments through this website.</p>

<h2>Information collected automatically</h2>
<p>Our host and analytics tools record standard technical data when you visit: IP address, browser and device type, pages viewed, and the site you arrived from. We use this to understand traffic and keep the site working, not to identify you personally.</p>

<h2>How we use your information</h2>
<ul>
<li>To respond to your booking request, application, or question.</li>
<li>To introduce you to a sitter you asked about, or to a horse or farm owner looking for care.</li>
<li>To review caretaker applications.</li>
<li>To operate, secure, and improve the site.</li>
</ul>

<h2>Who we share it with</h2>
<p>We share your information only where it is necessary to do what you asked:</p>
<ul>
<li><strong>With the sitter you contacted.</strong> A booking request is meant to reach a caretaker, so the details you submit are shared with that person.</li>
<li><strong>With service providers.</strong> Our website hosting, email delivery, and analytics providers process data on our behalf.</li>
<li><strong>Where the law requires it</strong>, or to protect the safety, rights, or property of people or animals.</li>
</ul>
<p>We do not sell your personal information, and we do not share it with advertisers.</p>

<h2>Cookies</h2>
<p>This site uses cookies for basic functionality and for anonymous traffic statistics. You can block or delete cookies in your browser settings. Some parts of the site may not work correctly if you do.</p>

<h2>How long we keep it</h2>
<p>Booking requests, applications, and contact messages are stored so we can follow up and keep a record of introductions we have made. You can ask us to delete yours at any time.</p>

<h2>Your choices</h2>
<p>You can ask us to show you the information we hold about you, correct it, or delete it. Write to us through our <a href="{$contact}">contact page</a> and we will respond. If you are in a place with specific privacy rights, such as the EU, the UK, or California, those rights apply and we will honor them.</p>

<h2>Children</h2>
<p>This site is intended for adults. We do not knowingly collect information from anyone under 18. If you believe a child has sent us information, contact us and we will delete it.</p>

<h2>Security</h2>
<p>We take reasonable steps to protect the information you send us. No website can promise perfect security, so please do not send sensitive information such as financial or medical details through our forms.</p>

<h2>Changes to this policy</h2>
<p>If we change this policy we will update the date at the top of this page.</p>

<h2>Contact us</h2>
<p>Questions about privacy, or about the information we hold on you, can be sent through our <a href="{$contact}">contact page</a>.</p>
HTML;
}

/**
 * Terms of service copy. The core point is that The Ranch Hand introduces
 * owners to sitters and is not itself a party to the care arrangement.
 */
function trh_terms_content() {
	$contact = esc_url( home_url( '/contact/' ) );
	$privacy = esc_url( home_url( '/privacy-policy/' ) );
	$date    = date_i18n( 'F j, Y' );

	return <<<HTML
<p><em>Last updated: {$date}</em></p>

<p>These terms govern your use of The Ranch Hand website. By using the site, or by sending us a booking request, application, or message, you agree to them. If you do not agree, please do not use the site.</p>

<h2>What The Ranch Hand does</h2>
<p>The Ranch Hand is a directory. We help horse and farm animal owners find sitters and caretakers, and we pass along the requests you send. We are an introduction service, and nothing more than that.</p>
<p>We are <strong>not</strong> a party to any arrangement you make with a sitter or an owner. We do not supervise, direct, or control the care that is provided. Any agreement about dates, duties, rates, or payment is between you and the other person.</p>

<h2>Sitters are independent</h2>
<p>Caretakers listed on this site are independent individuals, not our employees, agents, or partners. They set their own rates, their own schedules, and their own terms.</p>

<h2>Reviewing profiles is your responsibility</h2>
<p>We review the applications we receive and ask caretakers to describe their experience honestly. We are not able to independently verify every claim, certification, or review, and we do not run background checks unless we say so explicitly.</p>
<p>Before you leave animals in someone's care, please do your own checking. Meet the person, ask for references, confirm their experience with your species and situation, and put your expectations in writing. The same is true for caretakers deciding whether to accept a job.</p>

<h2>Payments</h2>
<p>No money changes hands through this website. We do not process payments, hold funds, or handle deposits. Any rates shown are what a caretaker has told us they charge, and are for guidance only. You arrange payment directly with the other person.</p>

<h2>Who can use this site</h2>
<p>You must be at least 18 years old and able to enter into a binding agreement.</p>

<h2>What you send us</h2>
<p>You are responsible for the accuracy of what you submit, and you confirm you have the right to send it. Do not submit anything false, misleading, unlawful, or belonging to someone else. We may edit or remove any listing or submission, and may decline or discontinue anyone's listing, at our discretion.</p>

<h2>Acceptable use</h2>
<p>Please do not use this site to harass anyone, to scrape or harvest contact details, to send unsolicited commercial messages, to impersonate another person, or to interfere with how the site runs.</p>

<h2>Animal care and emergencies</h2>
<p>Nothing on this site is veterinary advice. Owners are responsible for giving caretakers accurate feeding, medication, and emergency instructions, along with veterinary contact details and authorization for emergency treatment. In an emergency, contact a veterinarian.</p>

<h2>Disclaimers</h2>
<p>The site is provided as it is, without warranties of any kind. We do not promise that listings are accurate, current, or complete, that the site will be uninterrupted or error free, or that any introduction will lead to a satisfactory arrangement.</p>

<h2>Limitation of liability</h2>
<p>To the fullest extent the law allows, The Ranch Hand is not liable for any indirect, incidental, special, or consequential damages, or for any loss, injury, illness, or death of an animal or a person, arising out of your use of the site or out of any arrangement made through it. Our total liability for any claim is limited to the amount you paid us, which for use of this website is zero.</p>
<p>Some places do not allow certain limitations, so parts of this section may not apply to you.</p>

<h2>Indemnification</h2>
<p>You agree to cover our reasonable costs if a claim is brought against us because of your use of the site, something you submitted, or a dispute between you and another user.</p>

<h2>Links to other sites</h2>
<p>Where we link to another website, we are not responsible for its content or its practices.</p>

<h2>Privacy</h2>
<p>Our <a href="{$privacy}">privacy policy</a> explains how we handle your personal information and forms part of these terms.</p>

<h2>Governing law</h2>
<p>These terms are governed by the laws of the state in which The Ranch Hand operates, without regard to its conflict of law rules. Disputes will be brought in the courts of that state.</p>

<h2>Changes to these terms</h2>
<p>We may update these terms. Continuing to use the site after we do means you accept the new version. The date at the top shows when they last changed.</p>

<h2>Contact</h2>
<p>Questions about these terms can be sent through our <a href="{$contact}">contact page</a>.</p>
HTML;
}
