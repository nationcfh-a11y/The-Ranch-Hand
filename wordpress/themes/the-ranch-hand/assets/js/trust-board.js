/*
 * Trust Score Board: the live score pill in the header.
 *
 * Two ways a rise reaches the Hand, both ending in the same animation:
 *
 *   1. On load, the server has already told us how many points they have not
 *      been shown (data-pending), so we celebrate straight away.
 *   2. While the page sits open we ask the server every 30s whether the score
 *      moved, so an award that lands mid-session shows up without a refresh.
 *
 * Once the count-up finishes we tell the server what we showed, which moves the
 * per-user watermark forward. That is what keeps a rise from being celebrated
 * twice, and what keeps one that lands while they are away from being missed.
 */
(function () {
	'use strict';

	var cfg = window.TRH_TRUST || {};
	var board = document.getElementById('trh-trust-board');
	if (!board || !cfg.root) return;

	var numEl = board.querySelector('[data-trust-num]');
	var popEl = board.querySelector('[data-trust-pop]');
	var liveEl = board.querySelector('[data-trust-live]');
	if (!numEl || !popEl) return;

	var calm = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var POLL_MS = Math.max(10000, cfg.pollMs || 30000);

	// What the pill is currently showing. The inline primer in the header has
	// already rewound the text to the watermark, so read it back rather than
	// trusting data-score, which holds the new total.
	var shown = parseInt(numEl.textContent, 10) || 0;
	var busy = false;
	var stopped = false;

	/* --- server ---------------------------------------------------------- */

	function api(path, options) {
		var opts = options || {};
		opts.credentials = 'same-origin';
		opts.headers = Object.assign({ 'X-WP-Nonce': cfg.nonce }, opts.headers || {});
		return fetch(cfg.root + path, opts).then(function (res) {
			// An expired nonce (a tab left open overnight) can never recover on
			// its own. Give up quietly; the next page load brings a fresh one.
			if (res.status === 401 || res.status === 403) {
				stopped = true;
				throw new Error('auth');
			}
			if (!res.ok) throw new Error('http ' + res.status);
			return res.json();
		});
	}

	function markSeen(score) {
		api('trust/seen', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ score: score })
		}).catch(function () { /* the watermark retries on the next load */ });
	}

	/* --- animation ------------------------------------------------------- */

	function countUp(from, to, ms, done) {
		var start = null;
		var span = to - from;

		function frame(now) {
			if (start === null) start = now;
			var t = Math.min(1, (now - start) / ms);
			var eased = 1 - Math.pow(1 - t, 3);
			numEl.textContent = String(Math.round(from + span * eased));
			if (t < 1) {
				requestAnimationFrame(frame);
			} else {
				numEl.textContent = String(to);
				done();
			}
		}
		requestAnimationFrame(frame);
	}

	/**
	 * Show the old total, flash "+N", then count the total up to the new one.
	 */
	function celebrate(from, to, reason) {
		if (busy || to <= from) return;
		busy = true;

		var gain = to - from;
		numEl.textContent = String(from);
		popEl.textContent = '+' + gain;
		board.setAttribute('aria-label', 'Trust Score: ' + to + ' points. See your breakdown.');
		if (liveEl) {
			liveEl.textContent = (reason ? reason + '. ' : '') +
				'Trust Score up ' + gain + ' points, now ' + to + '.';
		}

		// Restart the float even if it is already mid-flight.
		popEl.classList.remove('is-live');
		void popEl.offsetWidth;
		popEl.classList.add('is-live');
		board.classList.add('is-rising');

		function settle() {
			shown = to;
			busy = false;
			board.classList.remove('is-rising');
			markSeen(to);
		}

		if (calm) {
			numEl.textContent = String(to);
			settle();
		} else {
			// Let the "+N" clear the pill before the number starts moving, so
			// the two read as cause and effect rather than one blur.
			setTimeout(function () { countUp(from, to, 900, settle); }, 420);
		}
		setTimeout(function () { popEl.classList.remove('is-live'); }, 2600);
	}

	/* --- the two paths in --------------------------------------------- */

	var pending = parseInt(board.dataset.pending, 10) || 0;
	if (pending > 0) {
		var target = parseInt(board.dataset.score, 10) || shown + pending;
		setTimeout(function () { celebrate(shown, target); }, 600);
	}

	function poll() {
		if (stopped || busy || document.hidden) return;
		api('trust').then(function (data) {
			if (data && typeof data.score === 'number' && data.score > shown) {
				celebrate(shown, data.score, data.reason);
			} else if (data && typeof data.score === 'number') {
				shown = data.score;
				numEl.textContent = String(shown);
			}
		}).catch(function () { /* offline or a blip; the next tick tries again */ });
	}

	setInterval(poll, POLL_MS);

	// Coming back to a backgrounded tab is the most likely moment for the score
	// to be stale, so check then too rather than waiting out the interval.
	document.addEventListener('visibilitychange', function () {
		if (!document.hidden) poll();
	});
})();
