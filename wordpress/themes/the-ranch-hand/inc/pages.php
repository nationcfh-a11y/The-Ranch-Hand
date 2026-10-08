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

/** Shown at the top of both legal pages. Change it whenever the text changes. */
const TRH_LEGAL_UPDATED = 'October 8, 2026';

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
 * One-time rewrite of the two legal pages for accounts, job-posting plans, and
 * the Google Sheet mirror (October 2026). Unlike the creator above, this DOES
 * overwrite the published text on purpose: the v1 copy promised "no accounts,
 * no payments", which stopped being true. Runs once, then no-ops. To push a
 * future revision, edit the copy below and bump the option key to _v3.
 */
add_action( 'after_setup_theme', 'trh_update_legal_pages_v2', 20 );
function trh_update_legal_pages_v2() {
	if ( get_option( 'trh_legal_pages_v2' ) ) {
		return;
	}

	$pages = array(
		'privacy-policy' => trh_privacy_policy_content(),
		'terms'          => trh_terms_content(),
	);

	foreach ( $pages as $slug => $content ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $page ) {
			wp_update_post(
				array(
					'ID'           => $page->ID,
					'post_status'  => 'publish',
					'post_content' => $content,
				)
			);
		}
	}

	update_option( 'trh_legal_pages_v2', 1 );
}

/**
 * Privacy policy copy, tailored to what the site actually does: Ranch and Hand
 * accounts, Hand profiles reviewed before going public, profile PDFs, the
 * Google Sheet mirror, lead forms, job-posting plans, and Jetpack stats.
 */
function trh_privacy_policy_content() {
	$contact = esc_url( home_url( '/contact/' ) );
	$terms   = esc_url( home_url( '/terms/' ) );
	$date    = TRH_LEGAL_UPDATED;

	return <<<HTML
<p><em>Last updated: {$date}</em></p>

<p>The Ranch Hand ("The Ranch Hand," "we," "us," or "our") runs theranchhand.farm, a website that connects horse, livestock, and farm owners ("Ranches") with people who care for animals ("Hands"). This Privacy Policy explains what personal information we collect, how we use it, who we share it with, and the choices you have. It applies to everyone who visits the site or uses our services, whether or not you create an account.</p>
<p>By using the site you acknowledge this policy. It is part of our <a href="{$terms}">Terms of Service</a>.</p>

<h2>1. Information you give us</h2>

<h3>Ranch accounts</h3>
<p>When you register a ranch, we collect your name, email address, phone number (optional), ranch or farm name, location, property size, the animals you keep and anything you tell us about them, the help you need and how often, your start date, what you are looking for in a Hand, any other notes you add, and the username and password you choose. If you choose a job-posting plan, we also record which plan you chose and the details of the job you want posted.</p>

<h3>Hand accounts</h3>
<p>When you sign up as a Hand, we collect:</p>
<ul>
<li><strong>Contact and account details:</strong> first and last name, phone number, email address, city, state, ZIP code, and the username and password you choose.</li>
<li><strong>Proof of experience:</strong> your resume, profile picture, links to your social media accounts, and anything you write about yourself.</li>
<li><strong>References:</strong> the name, relationship to you, phone number, and email address of each reference you list. Before you give us someone's details, please make sure they have agreed to be contacted.</li>
<li><strong>Experience:</strong> the experience checklist you complete, your years of experience, and any notes for owners.</li>
</ul>
<p>We use what you provide to calculate your Trust Score (see section 4).</p>

<h3>Forms and messages</h3>
<p>When you send a booking request, a note about becoming a Hand, or a message through our contact page, we collect what you put in the form. That is usually your name, email address, phone number, location, the service and dates you are asking about, and your message.</p>

<h3>Payments</h3>
<p>We do not currently accept card payments on the site. When online checkout launches, a third-party payment processor will handle your card details. We will receive confirmation of payment, the amount, and the plan purchased, but not your full card number.</p>

<h3>Your password</h3>
<p>Your password is stored only in scrambled (hashed) form. We cannot see it, and we never put it in any spreadsheet, document, or email.</p>

<h2>2. Information collected automatically</h2>
<p>When you visit, our hosting provider and its built-in statistics tools record standard technical data: your IP address, browser and device type, the pages you view, the time of your visit, and the site that sent you here. We use it to keep the site running, secure it, and understand our traffic.</p>
<p><strong>Cookies.</strong> We use cookies to keep you signed in (including the "Keep me signed in" option), to protect our forms from abuse, and to count visits. We do not use advertising cookies, and we do not track you across other websites. You can block or delete cookies in your browser, but you will not be able to sign in if you do. Our site does not respond to browser "Do Not Track" signals, because there is no settled standard for them.</p>

<h2>3. Information we get from others</h2>
<p>If you list someone as a reference, we may contact them about you. If you send us links to your social media accounts, we may look at what is publicly visible on them. If a Ranch or Hand you have worked with tells us about that experience, we may keep a record of it.</p>

<h2>4. How we use your information</h2>
<ul>
<li>To create and run your account, and to let you sign in.</li>
<li>To review Hand profiles before they are published, including checking references and the information you give us.</li>
<li>To calculate and display Trust Scores. A Trust Score is worked out from the information on a Hand's profile. It is not a background check, a certification, or an endorsement.</li>
<li>To match Ranches with Hands, pass along booking requests and job posts, and introduce the two sides to each other.</li>
<li>To set up job-posting plans, process payments, and keep business and tax records.</li>
<li>To send you emails about your account, your profile, your jobs, and your requests. If we ever send marketing emails, each one will include a way to unsubscribe.</li>
<li>To answer your questions and give you support.</li>
<li>To keep the site and its users safe: preventing spam, fraud, and abuse, and enforcing our Terms.</li>
<li>To understand how the site is used, so we can improve it.</li>
<li>To comply with the law.</li>
</ul>

<h2>5. What other people can see</h2>
<p><strong>Hand profiles.</strong> Once we approve a Hand profile, it appears in our public directory. It shows the Hand's name, profile picture, town and state, experience, the animals they work with, their services and rates, their availability, what they have written about themselves, and may include their Trust Score. A Hand's phone number, email address, resume, references, and street address are <strong>not</strong> shown publicly. We share a Hand's contact details with a Ranch only once a job is accepted or the Hand agrees to be introduced.</p>
<p><strong>Ranch details and job posts.</strong> When a Ranch posts a job, the job details, the Ranch's general location, and its first name or ranch name may be shown to Hands. On some plans the job may also be shared on our social media accounts. We do not publish a Ranch's phone number, email address, or exact address. We share them with a Hand only once a job is arranged.</p>
<p><strong>Booking requests.</strong> A booking request is meant to reach a particular Hand, so we share the details you send with that Hand.</p>
<p>Anything you choose to share directly with another user, such as your address, your gate code, or your vet's details, is up to you. Once you share it, that person can see it.</p>

<h2>6. Who we share your information with</h2>
<p>We do not sell your personal information. We do not share it with advertisers, and we do not share it for cross-site targeted advertising. We share it only as follows:</p>
<ul>
<li><strong>With other users</strong>, as described in section 5.</li>
<li><strong>With our service providers</strong>, who process data on our behalf and only for the services they provide to us:
<ul>
<li><em>WordPress.com (Automattic Inc.)</em> hosts the site, stores account data and uploaded files, sends site email, and provides visitor statistics through Jetpack.</li>
<li><em>Google</em> hosts the internal spreadsheet where we track signups. It holds each person's account ID, name, username, chosen plan, and a link to their profile document, and it is visible only to our team.</li>
<li><em>A payment processor</em>, once online checkout launches.</li>
</ul></li>
<li><strong>For legal reasons</strong>: to comply with a law, court order, or legal process; to enforce our Terms; or to protect the safety, rights, or property of any person, animal, or The Ranch Hand.</li>
<li><strong>In a business transfer</strong>: if The Ranch Hand is sold, merged, or reorganized, your information may be transferred to the new owner. That owner will still be bound by this policy.</li>
<li><strong>With your permission</strong>, in any other case.</li>
</ul>

<h2>7. Profile documents</h2>
<p>To review signups, we put together the answers each Ranch and Hand gives us in a single PDF profile document. These documents are for our team. They are stored with our hosting provider, are not linked anywhere on the public site, and are deleted when the account is deleted.</p>

<h2>8. How long we keep your information</h2>
<p>We keep your account and profile information for as long as your account is open. Messages, booking requests, and records of introductions and payments are kept for as long as we need them to run the service, settle disputes, and meet legal, tax, and accounting requirements. When you delete your account, we delete or anonymize your personal information within a reasonable time, unless we are required to keep it or need it to resolve a dispute or prevent fraud. Copies in backups are removed as the backups are replaced.</p>

<h2>9. Your choices and rights</h2>
<ul>
<li><strong>Access and correction.</strong> Hands can view and edit most of their profile from their dashboard. Anyone can ask us for a copy of the information we hold on them, or ask us to correct it.</li>
<li><strong>Deletion.</strong> You can ask us to delete your account and your personal information at any time.</li>
<li><strong>Email.</strong> You can opt out of marketing emails at any time. We may still send messages you need about your account or a transaction.</li>
</ul>
<p>To make any of these requests, write to us through our <a href="{$contact}">contact page</a>. We may need to confirm your identity first. Depending on where you live (for example California, Colorado, Virginia, or another U.S. state with its own privacy law, or the EU or UK), you may have additional rights, such as the right to appeal a decision we make about your request. We will honor those rights, and we will not treat you differently for using them.</p>

<h2>10. Security</h2>
<p>We use reasonable safeguards to protect your information. These include encrypted (HTTPS) connections, hashed passwords, limited team access, and secure hosting. No website or storage system is completely secure, so we cannot guarantee that your information will never be accessed, lost, or changed without permission. Please use a strong password that you do not use anywhere else, and do not send us sensitive information we have not asked for, such as Social Security numbers, bank details, or medical records. If a security breach affects your personal information, we will notify you as the law requires.</p>

<h2>11. Children</h2>
<p>The Ranch Hand is for adults. You must be 18 or older to create an account. We do not knowingly collect personal information from anyone under 18. If we learn that we have, we will delete it. If you believe a child has given us information, please contact us.</p>

<h2>12. Where your information is stored</h2>
<p>The Ranch Hand is based in the United States, and our service providers store data in the United States. If you use the site from another country, your information will be transferred to and processed in the United States.</p>

<h2>13. Links to other sites</h2>
<p>The site links to other sites, such as a Hand's social media profiles. Their own privacy practices apply, and we are not responsible for them.</p>

<h2>14. Changes to this policy</h2>
<p>We may update this policy as the site changes. When we do, we will change the date at the top of this page. If a change is significant, we will also notify account holders by email or on the site before it takes effect.</p>

<h2>15. Contact us</h2>
<p>Questions about this policy, or about the information we hold on you, can be sent through our <a href="{$contact}">contact page</a>.</p>
HTML;
}

/**
 * Terms of service copy. The core points: The Ranch Hand is a venue that
 * introduces Ranches and Hands, is not a party to the care arrangement, does
 * not employ Hands or vet them beyond what it says, and limits its liability
 * for the inherent risks of working with large animals. Tennessee law.
 */
function trh_terms_content() {
	$contact = esc_url( home_url( '/contact/' ) );
	$privacy = esc_url( home_url( '/privacy-policy/' ) );
	$plans   = esc_url( home_url( '/ranch-plans/' ) );
	$date    = TRH_LEGAL_UPDATED;

	return <<<HTML
<p><em>Last updated: {$date}</em></p>

<p>These Terms of Service ("Terms") are a binding agreement between you and The Ranch Hand ("The Ranch Hand," "we," "us," or "our"). They govern your use of theranchhand.farm and any related services (together, the "Service"). By visiting the site, creating an account, checking the box to agree, posting a job, or sending us a request or message, you agree to these Terms and to our <a href="{$privacy}">Privacy Policy</a>. If you do not agree, do not use the Service.</p>
<p><strong>Please read sections 10 through 16 carefully. They limit our liability, require you to accept the risks of working with animals, and explain how disputes are resolved.</strong></p>

<h2>1. Definitions</h2>
<ul>
<li>A <strong>"Ranch"</strong> is a person or business that owns or is responsible for animals and uses the Service to find care for them.</li>
<li>A <strong>"Hand"</strong> is a person who uses the Service to offer animal care, farm, or ranch services.</li>
<li>A <strong>"Care Arrangement"</strong> is any agreement between a Ranch and a Hand for services, whether it is made through the Service or after an introduction we made.</li>
<li><strong>"User Content"</strong> is anything you submit to the Service, including profile information, photos, resumes, job posts, messages, and reviews.</li>
</ul>

<h2>2. What The Ranch Hand is, and is not</h2>
<p>The Ranch Hand is an online venue. It helps Ranches and Hands find each other. We list Hand profiles, display job posts, pass along requests, and make introductions.</p>
<p><strong>We are not a party to any Care Arrangement.</strong> We do not provide animal care. We do not employ, supervise, direct, or control Hands or the work they do. We do not guarantee that a Hand will be available, that a job will be filled, or that any arrangement will work out. Every Care Arrangement, including its duties, schedule, rates, payment, and any insurance, is solely between the Ranch and the Hand.</p>
<p><strong>Hands are not our employees.</strong> Hands are independent individuals or businesses, not employees, agents, joint venturers, or partners of The Ranch Hand. Hands decide whether to accept work, set their own rates and schedules, and use their own judgment and methods. Hands are responsible for their own taxes, licenses, permits, insurance, and compliance with the law. Nothing in these Terms creates an employment relationship between The Ranch Hand and anyone. Whether a Hand is an employee or a contractor of a particular Ranch is a matter between the two of them.</p>

<h2>3. Eligibility and accounts</h2>
<ul>
<li>You must be at least 18 years old and able to enter into a binding contract. If you use the Service for a business, you confirm that you are authorized to bind that business.</li>
<li>Everything you tell us must be true, accurate, and up to date. You may hold only one account of each type, and only for yourself.</li>
<li>You are responsible for keeping your password secret and for everything that happens under your account. Tell us right away if you think someone else has used it.</li>
<li>We review Hand profiles before they appear publicly. We may approve, decline, edit, hide, or remove any profile, job post, or account at our discretion, with or without notice. We may also suspend or close any account, including for violating these Terms, for safety concerns, or for inactivity.</li>
</ul>

<h2>4. Vetting, Trust Scores, and what we do not verify</h2>
<p>We ask Hands to describe their experience honestly, and we review profiles before publishing them. We may contact references. However:</p>
<ul>
<li><strong>We do not run criminal background checks, identity checks, or license or certification checks</strong> unless we expressly say so for a particular Hand.</li>
<li>A <strong>Trust Score</strong> is a points total calculated from the information on a Hand's profile, such as whether they uploaded a resume, added references, or linked social accounts. It measures how complete a profile is. It is <strong>not</strong> a background check, a rating of skill or character, a certification, a guarantee, or an endorsement by The Ranch Hand.</li>
<li>Reviews, ratings, and profile information come from users. We do not guarantee that they are accurate.</li>
</ul>
<p><strong>You are responsible for your own decisions.</strong> Before you trust someone with your animals or your property, or before you accept work on someone else's property, do your own checking. Meet in person, call the references, ask about the animals and the job, check insurance, and put the details of the arrangement in writing.</p>

<h2>5. Responsibilities of Ranches</h2>
<ul>
<li>Give Hands complete and accurate information about your animals, including temperament, health, medications, feeding, known dangers (such as biting, kicking, aggression, or contagious conditions), and the condition of your property and equipment.</li>
<li>Provide written care instructions, emergency contacts, your veterinarian's contact details, and authorization for emergency veterinary treatment.</li>
<li>Keep your property, facilities, and equipment reasonably safe, and warn Hands about any hazards.</li>
<li>Carry any insurance you consider appropriate, and confirm whether your own policies cover people working on your property.</li>
<li>Pay Hands as agreed. Comply with any labor, tax, or employment laws that apply to you as the person engaging the Hand.</li>
</ul>

<h2>6. Responsibilities of Hands</h2>
<ul>
<li>Describe your experience, skills, and availability honestly. Accept only work you are qualified and physically able to do safely.</li>
<li>Follow the Ranch's instructions, care for animals humanely, and contact the Ranch and a veterinarian promptly if an animal is sick or injured.</li>
<li>Respect the Ranch's property and privacy. Do not bring other people or animals onto the property without permission.</li>
<li>Obtain any licenses, permits, or insurance you need. Pay your own taxes.</li>
</ul>

<h2>7. Fees and payment</h2>
<p><strong>Job-posting plans.</strong> Ranches may buy plans to post jobs and reach more Hands, along with add-ons such as Boosts. Current plans and prices are listed on our <a href="{$plans}">plans page</a> and may change at any time. A price change does not affect a plan you have already paid for. Plans describe how widely we will show your job and which Hands it will reach. <strong>A plan does not guarantee any number of applicants, a hire, or the quality of any Hand.</strong> If no Hands meet a plan's Trust Score or distance requirements, we may show your job to the closest available Hands instead, or offer you a different plan.</p>
<p><strong>How you pay.</strong> Until online checkout launches, we will contact you to arrange payment after you choose a plan. Once it launches, payments will be handled by a third-party payment processor under its own terms. You agree to pay all fees for the plans and add-ons you buy, plus any applicable taxes.</p>
<p><strong>Refunds.</strong> Fees are non-refundable once your job has been posted, except where the law requires otherwise. If we cannot post your job, or we remove it for reasons that are not your fault, we will refund the fee for that posting. We may remove a job post that violates these Terms without a refund.</p>
<p><strong>Payment for animal care.</strong> Unless we expressly offer a payment service in the future, payments for care services are made directly between the Ranch and the Hand, and we have no part in them. Rates shown on profiles are what Hands have told us they charge, and are for guidance only. We may introduce new fees, such as service fees on bookings. We will tell you about any new fee before you are charged it.</p>

<h2>8. Your content and how we may use it</h2>
<p>You keep ownership of your User Content. By submitting it, you give The Ranch Hand a non-exclusive, worldwide, royalty-free, transferable license to host, store, copy, display, adapt (for example, resizing a photo), and distribute it to operate, improve, and promote the Service. This includes showing your profile in our directory and, for jobs posted on plans that include it, sharing your job on our social media. This license ends when your content is removed from the Service, except for copies already shared, copies in backups, and copies we must keep for legal reasons.</p>
<p>You confirm that you own your User Content or have permission to share it. That includes having your references' permission to give us their contact details. You also confirm that your User Content does not violate anyone's rights or any law. Do not submit anything false, misleading, defamatory, obscene, discriminatory, or unlawful. Reviews must reflect real experiences. Fake reviews and reviews written in exchange for payment are prohibited.</p>

<h2>9. Acceptable use</h2>
<p>You agree not to:</p>
<ul>
<li>harass, threaten, defraud, or discriminate against anyone, or mistreat or neglect any animal;</li>
<li>impersonate anyone, or create a false or duplicate account;</li>
<li>post a job for anything illegal, or for anything other than real animal, farm, or ranch work;</li>
<li>scrape, harvest, or collect other users' information, or use it for anything other than a Care Arrangement;</li>
<li>send spam or unsolicited advertising;</li>
<li>upload viruses or malicious code, probe our security, or interfere with how the Service runs;</li>
<li>copy, resell, or build a competing service from our content or our directory.</li>
</ul>

<h2>10. Assumption of risk</h2>
<p><strong>Working with horses, livestock, and other farm animals is inherently dangerous.</strong> Animals can be unpredictable, even when they are familiar or well trained. They can kick, bite, strike, buck, bolt, crush, or trample, and they can carry diseases that pass to people. Farm and ranch work also involves heavy equipment, machinery, fencing, uneven ground, water, weather, and other hazards. Any of these can cause property damage, serious injury, illness, or death, to people and to animals.</p>
<p>You understand and voluntarily accept these risks, whether you are a Ranch or a Hand. You are solely responsible for deciding whether a job, an animal, or a property is safe for you, and for taking reasonable precautions. Ranches and Hands are encouraged to sign their own written agreements, including any releases or warning notices required or recommended under the laws of their state (such as Tennessee's equine activity law, Tenn. Code Ann. Title 44, Chapter 20).</p>

<h2>11. Release</h2>
<p>To the fullest extent the law allows, The Ranch Hand is not responsible for the acts, omissions, or conduct of any Ranch, Hand, or other user, whether online or offline. <strong>You release The Ranch Hand and its owners, officers, employees, contractors, and agents from all claims, demands, and damages of every kind, known and unknown, that arise out of or relate to any Care Arrangement, or to any dispute or interaction between you and another user.</strong> This includes claims for injury, illness, or death of any person or animal, damage to or loss of property, and non-payment. If you are a California resident, you waive California Civil Code § 1542, which says: "A general release does not extend to claims that the creditor or releasing party does not know or suspect to exist in his or her favor at the time of executing the release and that, if known by him or her, would have materially affected his or her settlement with the debtor or released party." Residents of other states waive any similar law.</p>

<h2>12. Disclaimers</h2>
<p><strong>The Service is provided "as is" and "as available," without warranties of any kind, whether express, implied, or statutory.</strong> These include warranties of merchantability, fitness for a particular purpose, title, non-infringement, and accuracy. We do not warrant that any profile, Trust Score, review, rate, or job post is accurate or complete. We do not warrant that any user is qualified, honest, insured, or safe, or that the Service will be uninterrupted, secure, or error-free. Nothing on the Service is veterinary, legal, tax, or employment advice. In an animal emergency, call a veterinarian.</p>

<h2>13. Limitation of liability</h2>
<p>To the fullest extent the law allows:</p>
<ul>
<li>The Ranch Hand will not be liable for any indirect, incidental, special, consequential, exemplary, or punitive damages, or for lost profits, revenue, data, or goodwill, even if we were told such damages were possible.</li>
<li>The Ranch Hand will not be liable for any bodily injury, illness, death, emotional distress, or property damage, including the injury, illness, death, loss, or theft of any animal, that arises out of a Care Arrangement or the conduct of any user.</li>
<li><strong>Our total liability to you for all claims relating to the Service is limited to the greater of (a) the amount you paid us in the 12 months before the event that gave rise to the claim, or (b) one hundred U.S. dollars (\$100).</strong></li>
</ul>
<p>These limits apply to every legal theory, whether in contract, tort (including negligence), strict liability, or otherwise, and they apply even if a remedy fails of its essential purpose. Some places do not allow certain exclusions or limits. Where that is the case, our liability is limited to the smallest amount the law allows.</p>

<h2>14. Indemnification</h2>
<p>You agree to defend, indemnify, and hold harmless The Ranch Hand and its owners, officers, employees, contractors, and agents from all claims, losses, liabilities, damages, costs, and expenses, including reasonable attorneys' fees, that arise out of or relate to any of the following: your use of the Service; your User Content; any Care Arrangement you enter; your animals or your property; your violation of these Terms or of any law; or your violation of anyone else's rights.</p>

<h2>15. Disputes between users</h2>
<p>Disputes between Ranches and Hands are for them to resolve. We may, if we choose, try to help, but we have no obligation to do so. Any help we give does not make us responsible for the outcome.</p>

<h2>16. Governing law and resolving disputes with us</h2>
<p><strong>Talk to us first.</strong> If you have a dispute with The Ranch Hand, contact us through our <a href="{$contact}">contact page</a> and describe it. We will try in good faith to resolve it informally within 30 days. Neither side may start a lawsuit until those 30 days have passed, except to seek urgent injunctive relief.</p>
<p><strong>Governing law.</strong> These Terms and any dispute relating to the Service are governed by the laws of the <strong>State of Tennessee</strong> and applicable U.S. federal law, without regard to conflict-of-law rules.</p>
<p><strong>Where disputes are heard.</strong> Any lawsuit must be brought exclusively in the state or federal courts located in Tennessee, and you and we consent to their personal jurisdiction. Either side may instead bring an individual claim in small claims court where it qualifies.</p>
<p><strong>Individual claims only.</strong> To the fullest extent the law allows, you and we each agree to bring claims only on an individual basis, and not as a plaintiff or class member in any class, collective, consolidated, or representative action. You and we also waive any right to a jury trial.</p>
<p><strong>Time limit.</strong> To the fullest extent the law allows, any claim relating to the Service must be filed within one (1) year after it arises. If it is not, it is permanently barred.</p>

<h2>17. Our content</h2>
<p>The Service, including its design, text, logos, graphics, and software, belongs to The Ranch Hand or its licensors and is protected by intellectual property laws. You may use it only to use the Service as intended. "The Ranch Hand" name and logo may not be used without our written permission. If you believe content on the Service infringes your copyright, contact us with the details and we will review it.</p>

<h2>18. Third-party services and links</h2>
<p>The Service links to, and relies on, services run by others, such as social media sites, our hosting provider, and payment processors. Those services have their own terms, and we are not responsible for them.</p>

<h2>19. Ending your use</h2>
<p>You may stop using the Service, and ask us to delete your account, at any time. We may suspend or end your access at any time and for any reason, including a violation of these Terms. Sections 4 and 7 through 21, and any other terms that by their nature should survive, remain in effect after your account ends.</p>

<h2>20. Changes to these Terms</h2>
<p>We may update these Terms. When we do, we will change the date at the top of this page. If a change is significant, we will also notify account holders by email or on the site before it takes effect. Continuing to use the Service after a change takes effect means you accept the new Terms.</p>

<h2>21. General</h2>
<ul>
<li>These Terms, together with our Privacy Policy and any plan terms shown when you buy, are the entire agreement between you and us about the Service.</li>
<li>If any part of these Terms is found unenforceable, that part will be enforced to the greatest extent possible, and the rest will remain in effect.</li>
<li>If we do not enforce a right, that does not waive it.</li>
<li>You may not transfer your rights under these Terms. We may transfer ours, for example in a sale or reorganization of the business.</li>
<li>We are not responsible for delays or failures caused by events beyond our reasonable control.</li>
<li>Section headings are for convenience only. "Including" means "including without limitation."</li>
</ul>

<h2>22. Contact</h2>
<p>Questions about these Terms can be sent through our <a href="{$contact}">contact page</a>.</p>
HTML;
}

/**
 * The "Resources" pages in the header: Our Mission, About Us, Blog.
 *
 * Our Mission and Blog are created if missing. About already exists on the
 * live site as WordPress's stock sample page ("This is an example of a
 * page..."), so it is renamed "About Us" and filled in, but only while it
 * still holds that sample text; real copy written in wp-admin is never
 * touched. The stock "Hello world!" post is moved back to draft so the new
 * Blog does not open on it. Runs once; bump the key to re-run.
 */
add_action( 'after_setup_theme', 'trh_ensure_resource_pages_v1', 20 );
function trh_ensure_resource_pages_v1() {
	if ( get_option( 'trh_resource_pages_v1' ) ) {
		return;
	}

	$pages = array(
		'our-mission' => array( 'title' => 'Our Mission', 'content' => trh_mission_content() ),
		'about'       => array( 'title' => 'About Us', 'content' => trh_about_content() ),
		'blog'        => array( 'title' => 'Blog', 'content' => '' ), // page-blog.php renders it
	);

	foreach ( $pages as $slug => $page ) {
		$existing = get_page_by_path( $slug, OBJECT, 'page' );

		if ( ! $existing ) {
			wp_insert_post(
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
			continue;
		}

		if ( 'about' === $slug && false !== strpos( $existing->post_content, 'This is an example of a page' ) ) {
			wp_update_post(
				array(
					'ID'             => $existing->ID,
					'post_status'    => 'publish',
					'post_title'     => $page['title'],
					'post_content'   => $page['content'],
					'comment_status' => 'closed',
				)
			);
		}
	}

	$hello = get_page_by_path( 'hello-world', OBJECT, 'post' );
	if ( $hello && 'publish' === $hello->post_status ) {
		wp_update_post( array( 'ID' => $hello->ID, 'post_status' => 'draft' ) );
	}

	update_option( 'trh_resource_pages_v1', 1 );
}

/** Our Mission copy. Editable afterwards in wp-admin → Pages. */
function trh_mission_content() {
	$hands = esc_url( home_url( '/become-a-caretaker/' ) );
	$ranch = esc_url( home_url( '/ranch-signup/' ) );

	return <<<HTML
<p class="lead"><strong>When you can't be there, a Ranch Hand can.</strong></p>

<p>Our mission is to make sure no horse, herd, or flock goes without good care, and that the people who know how to give it get the recognition and the work they deserve.</p>

<h2>Why we exist</h2>
<p>If you keep animals, you know the feeling. A wedding, a funeral, a work trip, a week of the flu, and suddenly the question is not whether you can go, but who will feed, water, turn out, and check on everything while you are gone. Asking a neighbor only goes so far. A dog sitter does not know how to spot colic, pull a calf, or handle a horse that does not want to be caught.</p>
<p>At the same time, there are a lot of people who grew up in barns and on working ranches, who can read an animal at a glance, and who would gladly take on the work. They just have not had a good way to be found.</p>
<p>The Ranch Hand brings those two groups together.</p>

<h2>What we stand for</h2>
<h3>Real experience over a nice profile</h3>
<p>We ask Hands to show what they have actually done: the animals they have handled, the care they have given, and the people who will vouch for them. Our Trust Score rewards that proof, so the most complete and best-supported profiles rise to the top.</p>
<h3>Honesty on both sides</h3>
<p>Ranches tell Hands the truth about their animals, their property, and the job. Hands tell Ranches the truth about what they can and cannot do. Good arrangements start there.</p>
<h3>The animals come first</h3>
<p>Every decision we make about the site comes back to one question: does this help an animal get better care?</p>
<h3>Respect for the work</h3>
<p>Animal care is skilled, physical, often early-morning work. Hands set their own rates and their own schedules, and we want them paid fairly for it.</p>

<h2>Where we are headed</h2>
<p>We are starting with the basics: helping Ranches find experienced Hands nearby, and helping Hands build a profile that shows what they know. From there we are building job posts, reviews from real jobs, and the tools that make arranging care simple from start to finish.</p>

<h2>Be part of it</h2>
<p>Know your way around a barn? <a href="{$hands}">Become a Hand</a>. Need someone you can trust with your animals? <a href="{$ranch}">Register your ranch</a>.</p>
HTML;
}

/** About Us copy. Editable afterwards in wp-admin → Pages. */
function trh_about_content() {
	$mission = esc_url( home_url( '/our-mission/' ) );
	$contact = esc_url( home_url( '/contact/' ) );
	$hands   = esc_url( home_url( '/become-a-caretaker/' ) );
	$ranch   = esc_url( home_url( '/ranch-signup/' ) );

	return <<<HTML
<p class="lead"><strong>The Ranch Hand connects horse, livestock, and farm owners with experienced people who can care for their animals.</strong></p>

<p>We built The Ranch Hand because finding someone you trust to look after a barn full of horses, a herd of cattle, or a mixed farm is hard, and it should not be. General pet-sitting sites are built for dogs and cats. Farm and ranch animals need people who know them.</p>

<h2>Who we serve</h2>
<h3>Ranches</h3>
<p>Horse owners, cattle and small-livestock producers, hobby farms, and everyone in between. Whether you need someone for one weekend or a few days every week, you can <a href="{$ranch}">register your ranch</a>, tell us about your animals and what you need, and post the job.</p>
<h3>Hands</h3>
<p>Barn managers, ranch-raised folks, vet techs, retired horsemen and horsewomen, and anyone with real hands-on animal experience. Hands <a href="{$hands}">build a profile</a> in three short steps, set their own rates, and choose the work that fits their week.</p>

<h2>How it works</h2>
<ol>
<li><strong>Hands build a profile</strong> with their experience, resume, references, and the animals and care they know. We review every profile before it goes live.</li>
<li><strong>Each Hand earns a Trust Score</strong> based on how much proof is on their profile, so Ranches can see at a glance who has shown the most.</li>
<li><strong>Ranches post what they need</strong> and connect with Hands nearby.</li>
<li><strong>The Ranch and the Hand agree on the details</strong>, including dates, duties, and pay, directly with each other.</li>
</ol>

<h2>What makes us different</h2>
<ul>
<li><strong>Built only for farm and ranch animals.</strong> Horses, cattle, goats, sheep, pigs, poultry, and mixed farms. Nothing else.</li>
<li><strong>Experience you can check.</strong> Our 54-item experience checklist covers everything from blanketing and hoof care to foal watch and medication, and Hands list references who can vouch for them.</li>
<li><strong>Every profile reviewed.</strong> A person on our team looks at every Hand profile before it appears in the directory.</li>
</ul>

<h2>Our mission</h2>
<p>When you can't be there, a Ranch Hand can. <a href="{$mission}">Read more about what we stand for.</a></p>

<h2>Get in touch</h2>
<p>Questions, ideas, or want to partner with us? <a href="{$contact}">Send us a note</a>. We read every message.</p>
HTML;
}
