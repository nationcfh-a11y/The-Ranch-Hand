/* The Ranch Hand: minimal theme interactions (mobile menu toggle). */
(function () {
	'use strict';
	document.addEventListener('DOMContentLoaded', function () {
		var toggle = document.getElementById('trh-nav-toggle');
		var menu = document.getElementById('trh-mobile-menu');
		if (!toggle || !menu) return;

		toggle.addEventListener('click', function () {
			var open = menu.classList.toggle('is-open');
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		});

		// Close the menu when a link inside it is followed.
		menu.addEventListener('click', function (e) {
			if (e.target.closest('a')) {
				menu.classList.remove('is-open');
				toggle.setAttribute('aria-expanded', 'false');
			}
		});
	});
})();

/* The account menu behind the header badge. */
(function () {
	'use strict';
	document.addEventListener('DOMContentLoaded', function () {
		var account = document.querySelector('[data-account]');
		if (!account) return;

		var button = account.querySelector('#trh-trust-board');
		var menu = account.querySelector('[data-account-menu]');
		if (!button || !menu) return;

		var closeTimer = null;
		// A menu opened by hover should not slam shut the moment the pointer
		// crosses the gap between the badge and the panel.
		var CLOSE_DELAY = 220;

		function open() {
			clearTimeout(closeTimer);
			menu.hidden = false;
			button.setAttribute('aria-expanded', 'true');
		}

		function close() {
			menu.hidden = true;
			button.setAttribute('aria-expanded', 'false');
		}

		function closeSoon() {
			clearTimeout(closeTimer);
			closeTimer = setTimeout(close, CLOSE_DELAY);
		}

		// Hover for a mouse, click for touch and keyboard. Click also pins it
		// open, so it survives the pointer leaving.
		account.addEventListener('mouseenter', open);
		account.addEventListener('mouseleave', closeSoon);

		button.addEventListener('click', function (e) {
			e.preventDefault();
			if (menu.hidden) { open(); } else { close(); }
		});

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && !menu.hidden) {
				close();
				button.focus();
			}
		});

		document.addEventListener('click', function (e) {
			if (!menu.hidden && !account.contains(e.target)) close();
		});

		// Tabbing out of the menu closes it, the same as clicking away.
		account.addEventListener('focusout', function (e) {
			if (!account.contains(e.relatedTarget)) close();
		});
	});
})();

/* My Profile: show the picture they just picked, before they save it. */
(function () {
	'use strict';
	document.addEventListener('DOMContentLoaded', function () {
		var input = document.getElementById('trh-photo');
		var current = document.querySelector('.profile-photo-current');
		if (!input || !current) return;

		input.addEventListener('change', function () {
			var file = input.files && input.files[0];
			if (!file) return;

			var img = current;
			// The placeholder is a <span> of initials until there is something
			// to show, so swap in a real <img> the first time.
			if (current.tagName !== 'IMG') {
				img = document.createElement('img');
				img.className = 'profile-photo-current';
				img.alt = '';
				current.replaceWith(img);
				current = img;
			}
			img.src = URL.createObjectURL(file);
			img.onload = function () { URL.revokeObjectURL(img.src); };

			// Choosing a new picture and removing the old one are contradictory.
			var remove = document.querySelector('input[name="remove_photo"]');
			if (remove) remove.checked = false;
		});
	});
})();
