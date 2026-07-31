/* The Ranch Hand — minimal theme interactions (mobile menu toggle). */
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
