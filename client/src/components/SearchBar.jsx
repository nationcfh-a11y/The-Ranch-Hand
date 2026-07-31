// Hero / page search bar: service type + location + dates. Navigates to /search with query params.
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { SERVICES } from '../lib/constants';

export default function SearchBar({ compact = false, initial = {} }) {
  const navigate = useNavigate();
  const [service, setService] = useState(initial.service || '');
  const [location, setLocation] = useState(initial.q || '');
  const [start, setStart] = useState(initial.start || '');
  const [end, setEnd] = useState(initial.end || '');

  function submit(e) {
    e.preventDefault();
    const params = new URLSearchParams();
    if (service) params.set('service', service);
    if (location) params.set('q', location);
    if (start) params.set('start', start);
    if (end) params.set('end', end);
    navigate(`/search?${params.toString()}`);
  }

  return (
    <form
      onSubmit={submit}
      className={`grid gap-3 rounded-xl bg-cream-100 p-3 shadow-pop sm:grid-cols-2 ${
        compact ? 'lg:grid-cols-5' : 'lg:grid-cols-[1.2fr_1.4fr_1fr_1fr_auto]'
      }`}
    >
      <label className="flex flex-col">
        <span className="sr-only">Service</span>
        <select
          value={service}
          onChange={(e) => setService(e.target.value)}
          className="input cursor-pointer"
          aria-label="Service type"
        >
          <option value="">Any service</option>
          {SERVICES.map((s) => (
            <option key={s.key} value={s.key}>{s.label}</option>
          ))}
        </select>
      </label>

      <label className="flex flex-col">
        <span className="sr-only">Location</span>
        <input
          type="text"
          value={location}
          onChange={(e) => setLocation(e.target.value)}
          placeholder="City or area (e.g. Bozeman)"
          className="input"
          aria-label="Location"
        />
      </label>

      <label className="flex flex-col">
        <span className="sr-only">Start date</span>
        <input type="date" value={start} onChange={(e) => setStart(e.target.value)} className="input" aria-label="Start date" />
      </label>

      <label className="flex flex-col">
        <span className="sr-only">End date</span>
        <input type="date" value={end} min={start || undefined} onChange={(e) => setEnd(e.target.value)} className="input" aria-label="End date" />
      </label>

      <button type="submit" className="btn-primary sm:col-span-2 lg:col-span-1">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" aria-hidden="true">
          <circle cx="11" cy="11" r="7" /><path d="M21 21l-4.3-4.3" strokeLinecap="round" />
        </svg>
        Search
      </button>
    </form>
  );
}
