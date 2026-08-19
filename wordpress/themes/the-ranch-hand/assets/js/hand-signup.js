/* ==========================================================================
   The Ranch Hand: Hand signup wizard interactions.

   Four small, independent pieces, all progressive enhancements. With JS off the
   form still submits: the city field is a plain text input and the state field
   is a plain <select>.

     1. City combobox. Type a town, pick from a dropdown that confirms which
        state you mean ("Columbia" -> Columbia, TN / MO / SC ...). The 29k-city
        dataset is fetched lazily, on first interaction with the field only, so
        it never costs anything on page load.
     2. Password show/hide.
     3. Experience checklist: card highlight + a live "n checked" counter.
     4. Profile picture preview.
   ========================================================================== */
(function () {
	'use strict';

	var CFG = window.TRH_SIGNUP || {};
	var MAX_RESULTS = 8;

	/* ---------------------------------------------------------------------
	 * 1. City combobox
	 * ------------------------------------------------------------------ */

	// Well-known cities (state capitals + the largest metros) float to the top
	// of an otherwise alphabetical list of same-named towns, so "Columbia"
	// offers Columbia, SC before Columbia, CT.
	var MAJOR = ('Montgomery AL|Birmingham AL|Juneau AK|Anchorage AK|Phoenix AZ|Tucson AZ|Mesa AZ|Little Rock AR|Sacramento CA|' +
		'Los Angeles CA|San Diego CA|San Jose CA|San Francisco CA|Fresno CA|Long Beach CA|Oakland CA|Denver CO|Colorado Springs CO|' +
		'Hartford CT|Bridgeport CT|Dover DE|Wilmington DE|Washington DC|Tallahassee FL|Jacksonville FL|Miami FL|Tampa FL|Orlando FL|' +
		'St. Petersburg FL|Atlanta GA|Augusta GA|Columbus GA|Savannah GA|Honolulu HI|Boise ID|Springfield IL|Chicago IL|Aurora IL|' +
		'Indianapolis IN|Fort Wayne IN|Des Moines IA|Cedar Rapids IA|Topeka KS|Wichita KS|Kansas City KS|Frankfort KY|Louisville KY|' +
		'Lexington KY|Baton Rouge LA|New Orleans LA|Shreveport LA|Augusta ME|Portland ME|Annapolis MD|Baltimore MD|Boston MA|' +
		'Worcester MA|Springfield MA|Lansing MI|Detroit MI|Grand Rapids MI|St. Paul MN|Minneapolis MN|Jackson MS|Jefferson City MO|' +
		'Kansas City MO|St. Louis MO|Springfield MO|Columbia MO|Helena MT|Billings MT|Bozeman MT|Missoula MT|Lincoln NE|Omaha NE|' +
		'Carson City NV|Las Vegas NV|Reno NV|Concord NH|Manchester NH|Trenton NJ|Newark NJ|Jersey City NJ|Santa Fe NM|Albuquerque NM|' +
		'Albany NY|New York NY|Buffalo NY|Rochester NY|Syracuse NY|Raleigh NC|Charlotte NC|Greensboro NC|Durham NC|Bismarck ND|' +
		'Fargo ND|Columbus OH|Cleveland OH|Cincinnati OH|Toledo OH|Oklahoma City OK|Tulsa OK|Salem OR|Portland OR|Eugene OR|' +
		'Harrisburg PA|Philadelphia PA|Pittsburgh PA|Allentown PA|San Juan PR|Providence RI|Columbia SC|Charleston SC|Greenville SC|' +
		'Pierre SD|Sioux Falls SD|Rapid City SD|Nashville TN|Memphis TN|Knoxville TN|Chattanooga TN|Franklin TN|Murfreesboro TN|' +
		'Columbia TN|Austin TX|Houston TX|San Antonio TX|Dallas TX|Fort Worth TX|El Paso TX|Lubbock TX|Amarillo TX|Salt Lake City UT|' +
		'Provo UT|Montpelier VT|Burlington VT|Richmond VA|Virginia Beach VA|Norfolk VA|Roanoke VA|Olympia WA|Seattle WA|Spokane WA|' +
		'Tacoma WA|Charleston WV|Huntington WV|Madison WI|Milwaukee WI|Green Bay WI|Cheyenne WY|Casper WY|Sheridan WY').split('|');

	var majorSet = {};
	MAJOR.forEach(function (entry) {
		var cut = entry.lastIndexOf(' ');
		majorSet[entry.slice(0, cut).toLowerCase() + '|' + entry.slice(cut + 1)] = true;
	});

	var index = null;        // [{c: 'Columbia', s: 'TN', l: 'columbia'}, ...]
	var loading = null;      // in-flight promise
	var stateNames = CFG.states || {};

	function loadCities() {
		if (index) return Promise.resolve(index);
		if (loading) return loading;
		if (!CFG.citiesUrl) return Promise.reject(new Error('no dataset'));

		loading = fetch(CFG.citiesUrl, { credentials: 'omit' })
			.then(function (res) {
				if (!res.ok) throw new Error('HTTP ' + res.status);
				return res.json();
			})
			.then(function (byState) {
				var flat = [];
				Object.keys(byState).forEach(function (state) {
					byState[state].forEach(function (city) {
						flat.push({ c: city, s: state, l: city.toLowerCase() });
					});
				});
				index = flat;
				return index;
			});
		return loading;
	}

	// Split "columbia, tn" / "columbia tn" into a city query and a state filter.
	function parseQuery(raw) {
		var q = raw.trim().toLowerCase();
		var m = q.match(/^(.*?)[,\s]+([a-z]{2})$/);
		if (m && stateNames[m[2].toUpperCase()]) {
			return { city: m[1].trim(), state: m[2].toUpperCase() };
		}
		return { city: q, state: '' };
	}

	function search(raw) {
		var parsed = parseQuery(raw);
		var q = parsed.city;
		if (!index || q.length < 2) return [];

		var starts = [];
		var contains = [];
		for (var i = 0; i < index.length; i++) {
			var row = index[i];
			if (parsed.state && row.s !== parsed.state) continue;
			var at = row.l.indexOf(q);
			if (at === 0) {
				starts.push(row);
			} else if (at > 0 && contains.length < 400) {
				contains.push(row);
			}
		}

		var rank = function (row) {
			var score = 0;
			if (row.l === q) score -= 4;                                     // exact name
			if (majorSet[row.l + '|' + row.s]) score -= 2;                   // well-known place
			return score;
		};
		starts.sort(function (a, b) {
			var d = rank(a) - rank(b);
			if (d) return d;
			if (a.l !== b.l) return a.l < b.l ? -1 : 1;
			return a.s < b.s ? -1 : 1;
		});

		return starts.concat(contains).slice(0, MAX_RESULTS);
	}

	function initCombo(wrap) {
		var input = wrap.querySelector('input[type="text"]');
		var list = wrap.querySelector('.combo-list');
		var stateSelect = document.getElementById('hs-state');
		if (!input || !list) return;

		var rows = [];
		var active = -1;
		var debounce;

		function close() {
			list.hidden = true;
			list.innerHTML = '';
			rows = [];
			active = -1;
			input.setAttribute('aria-expanded', 'false');
			input.removeAttribute('aria-activedescendant');
		}

		function choose(i) {
			var row = rows[i];
			if (!row) return;
			input.value = row.c;
			if (stateSelect) {
				stateSelect.value = row.s;
				stateSelect.dispatchEvent(new Event('change', { bubbles: true }));
			}
			close();
		}

		function highlight(i) {
			var options = list.querySelectorAll('.combo-option');
			for (var n = 0; n < options.length; n++) {
				options[n].classList.toggle('is-active', n === i);
				options[n].setAttribute('aria-selected', n === i ? 'true' : 'false');
			}
			active = i;
			if (options[i]) {
				input.setAttribute('aria-activedescendant', options[i].id);
				if (options[i].scrollIntoView) options[i].scrollIntoView({ block: 'nearest' });
			}
		}

		function render(results) {
			rows = results;
			active = -1;
			if (!results.length) {
				close();
				return;
			}
			var html = '';
			results.forEach(function (row, i) {
				html += '<li class="combo-option" id="' + list.id + '-o' + i + '" role="option" aria-selected="false" data-i="' + i + '">' +
					'<span class="combo-city">' + escapeHtml(row.c) + '</span>' +
					'<span class="combo-state">' + escapeHtml(stateNames[row.s] || row.s) + ' (' + escapeHtml(row.s) + ')</span>' +
					'</li>';
			});
			list.innerHTML = html;
			list.hidden = false;
			input.setAttribute('aria-expanded', 'true');
		}

		function update() {
			var raw = input.value;
			if (raw.trim().length < 2) {
				close();
				return;
			}
			loadCities().then(function () {
				// Ignore a stale response if the field moved on since.
				if (input.value !== raw) return;
				render(search(raw));
			}).catch(close);
		}

		input.addEventListener('focus', function () { loadCities().catch(function () {}); });
		input.addEventListener('input', function () {
			clearTimeout(debounce);
			debounce = setTimeout(update, 90);
		});

		input.addEventListener('keydown', function (e) {
			if (list.hidden) {
				if (e.key === 'ArrowDown') update();
				return;
			}
			if (e.key === 'ArrowDown') {
				e.preventDefault();
				highlight(active + 1 >= rows.length ? 0 : active + 1);
			} else if (e.key === 'ArrowUp') {
				e.preventDefault();
				highlight(active - 1 < 0 ? rows.length - 1 : active - 1);
			} else if (e.key === 'Enter') {
				if (active > -1) {
					e.preventDefault();
					choose(active);
				}
			} else if (e.key === 'Escape') {
				close();
			}
		});

		list.addEventListener('mousedown', function (e) {
			var option = e.target.closest('.combo-option');
			if (!option) return;
			e.preventDefault(); // keep focus on the input
			choose(parseInt(option.getAttribute('data-i'), 10));
		});

		document.addEventListener('click', function (e) {
			if (!wrap.contains(e.target)) close();
		});
	}

	function escapeHtml(s) {
		return String(s).replace(/[&<>"']/g, function (ch) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
		});
	}

	/* ---------------------------------------------------------------------
	 * 2-4. Small enhancements
	 * ------------------------------------------------------------------ */

	function initPasswordToggles() {
		document.querySelectorAll('[data-trh-pw]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var field = document.getElementById(btn.getAttribute('data-trh-pw'));
				if (!field) return;
				var show = field.type === 'password';
				field.type = show ? 'text' : 'password';
				btn.textContent = show ? 'Hide' : 'Show';
				btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
			});
		});
	}

	function initChecklist() {
		var items = document.querySelectorAll('.check-item input[type="checkbox"]');
		if (!items.length) return;
		var counter = document.querySelector('[data-trh-counter] strong');

		function recount() {
			if (!counter) return;
			var on = document.querySelectorAll('.check-item input[type="checkbox"]:checked').length;
			counter.textContent = on;
		}

		items.forEach(function (box) {
			box.addEventListener('change', function () {
				box.closest('.check-item').classList.toggle('is-checked', box.checked);
				recount();
			});
		});
		recount();
	}

	function initPhotoPreview() {
		document.querySelectorAll('[data-trh-preview]').forEach(function (holder) {
			var input = document.getElementById(holder.getAttribute('data-trh-preview'));
			if (!input) return;
			input.addEventListener('change', function () {
				var file = input.files && input.files[0];
				if (!file || !/^image\//.test(file.type)) return;
				var url = URL.createObjectURL(file);
				holder.innerHTML = '<img alt="" />';
				var img = holder.querySelector('img');
				img.onload = function () { URL.revokeObjectURL(url); };
				img.src = url;
			});
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('[data-trh-combo]').forEach(initCombo);
		initPasswordToggles();
		initChecklist();
		initPhotoPreview();
	});
})();
