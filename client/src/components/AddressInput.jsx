// Accessible address autocomplete (ARIA combobox pattern).
// Backed by Photon (https://photon.komoot.io) — a free, keyless geocoder built on
// OpenStreetMap data. No API key required. Falls back to plain text if offline:
// whatever the user types is still saved, so the field never blocks signup.
import { useEffect, useId, useRef, useState } from 'react';

// Turn a Photon feature into a readable, de-duplicated address string.
function formatPlace(p = {}) {
  const line1 =
    p.housenumber && p.street ? `${p.housenumber} ${p.street}` : p.name || p.street || '';
  const parts = [line1, p.city, p.county, p.state, p.country];
  const seen = new Set();
  const out = [];
  for (const part of parts) {
    if (!part) continue;
    const key = part.toLowerCase();
    if (seen.has(key)) continue;
    seen.add(key);
    out.push(part);
  }
  return out.join(', ');
}

export default function AddressInput({ value, onChange, id, placeholder = 'Start typing a city or address…' }) {
  const [query, setQuery] = useState(value || '');
  const [results, setResults] = useState([]);
  const [open, setOpen] = useState(false);
  const [active, setActive] = useState(-1);
  const [loading, setLoading] = useState(false);
  const [locating, setLocating] = useState(false);

  const boxRef = useRef(null);
  const listId = useId();
  const autoId = useId();
  const inputId = id || autoId;
  const skipNextFetch = useRef(false); // avoid re-querying right after a selection

  // Debounced lookup as the user types.
  useEffect(() => {
    if (skipNextFetch.current) {
      skipNextFetch.current = false;
      return;
    }
    const q = query.trim();
    if (q.length < 3) {
      setResults([]);
      setLoading(false);
      return;
    }
    setLoading(true);
    const ctrl = new AbortController();
    const timer = setTimeout(async () => {
      try {
        const res = await fetch(
          `https://photon.komoot.io/api/?q=${encodeURIComponent(q)}&limit=5&lang=en`,
          { signal: ctrl.signal }
        );
        const data = await res.json();
        const items = (data.features || [])
          .map((f) => ({ label: formatPlace(f.properties), id: f.properties.osm_id }))
          .filter((x) => x.label);
        // De-dupe identical labels (Photon sometimes repeats).
        const unique = [...new Map(items.map((i) => [i.label, i])).values()];
        setResults(unique);
        setOpen(true);
        setActive(-1);
      } catch {
        /* offline or aborted — keep the free-typed value, just no suggestions */
      } finally {
        setLoading(false);
      }
    }, 300);
    return () => {
      clearTimeout(timer);
      ctrl.abort();
    };
  }, [query]);

  // Close the dropdown when clicking outside.
  useEffect(() => {
    function onDoc(e) {
      if (boxRef.current && !boxRef.current.contains(e.target)) setOpen(false);
    }
    document.addEventListener('mousedown', onDoc);
    return () => document.removeEventListener('mousedown', onDoc);
  }, []);

  function commit(label) {
    skipNextFetch.current = true;
    setQuery(label);
    onChange(label);
    setResults([]);
    setOpen(false);
    setActive(-1);
  }

  function handleType(v) {
    setQuery(v);
    onChange(v); // free text is always saved, even without picking a suggestion
  }

  function onKeyDown(e) {
    if (!open || results.length === 0) return;
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      setActive((i) => (i + 1) % results.length);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setActive((i) => (i <= 0 ? results.length - 1 : i - 1));
    } else if (e.key === 'Enter' && active >= 0) {
      e.preventDefault();
      commit(results[active].label);
    } else if (e.key === 'Escape') {
      setOpen(false);
    }
  }

  // "Use my current location" → browser geolocation → Photon reverse geocode.
  function useMyLocation() {
    if (!navigator.geolocation) return;
    setLocating(true);
    navigator.geolocation.getCurrentPosition(
      async (pos) => {
        try {
          const { latitude, longitude } = pos.coords;
          const res = await fetch(
            `https://photon.komoot.io/reverse?lat=${latitude}&lon=${longitude}&lang=en`
          );
          const data = await res.json();
          const label = formatPlace(data.features?.[0]?.properties || {});
          if (label) commit(label);
        } catch {
          /* ignore — user can still type */
        } finally {
          setLocating(false);
        }
      },
      () => setLocating(false),
      { enableHighAccuracy: false, timeout: 8000 }
    );
  }

  return (
    <div ref={boxRef} className="relative">
      <div className="relative">
        <input
          id={inputId}
          type="text"
          className="input pr-10"
          value={query}
          placeholder={placeholder}
          onChange={(e) => handleType(e.target.value)}
          onKeyDown={onKeyDown}
          onFocus={() => results.length && setOpen(true)}
          role="combobox"
          aria-expanded={open}
          aria-controls={listId}
          aria-autocomplete="list"
          aria-activedescendant={active >= 0 ? `${listId}-opt-${active}` : undefined}
          autoComplete="off"
        />
        {/* Pin / spinner indicator */}
        <span className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-charcoal-muted" aria-hidden="true">
          {loading ? (
            <span className="inline-block h-4 w-4 animate-spin rounded-full border-2 border-line border-t-barn" />
          ) : (
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M12 21s7-6.2 7-11a7 7 0 10-14 0c0 4.8 7 11 7 11z" strokeLinejoin="round" />
              <circle cx="12" cy="10" r="2.5" />
            </svg>
          )}
        </span>
      </div>

      {/* Suggestions */}
      {open && results.length > 0 && (
        <ul
          id={listId}
          role="listbox"
          className="absolute z-30 mt-1 max-h-64 w-full overflow-auto rounded-md border border-line bg-cream-100 py-1 shadow-pop"
        >
          {results.map((r, i) => (
            <li
              key={`${r.label}-${i}`}
              id={`${listId}-opt-${i}`}
              role="option"
              aria-selected={active === i}
              onMouseEnter={() => setActive(i)}
              onMouseDown={(e) => {
                e.preventDefault(); // keep focus, prevent blur-close before click
                commit(r.label);
              }}
              className={`flex cursor-pointer items-center gap-2 px-3 py-2 text-sm ${
                active === i ? 'bg-barn/10 text-saddle-dark' : 'text-charcoal hover:bg-cream-200'
              }`}
            >
              <span aria-hidden="true">📍</span>
              <span className="truncate">{r.label}</span>
            </li>
          ))}
        </ul>
      )}

      <button
        type="button"
        onClick={useMyLocation}
        disabled={locating}
        className="mt-1.5 inline-flex items-center gap-1.5 text-sm font-bold text-barn hover:text-barn-dark disabled:opacity-50"
      >
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
          <circle cx="12" cy="12" r="3.5" />
          <path d="M12 2v3M12 19v3M2 12h3M19 12h3" strokeLinecap="round" />
        </svg>
        {locating ? 'Locating…' : 'Use my current location'}
      </button>
    </div>
  );
}
