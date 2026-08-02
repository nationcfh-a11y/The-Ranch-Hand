// Signup page. Owners go straight in; choosing "caretaker" routes to onboarding.
import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { FullLogo } from '../components/Logo';
import AddressInput from '../components/AddressInput';

export default function Signup() {
  const { signup } = useAuth();
  const navigate = useNavigate();

  const [role, setRole] = useState('owner');
  const [form, setForm] = useState({ name: '', email: '', password: '', location: '', radius: 20 });
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  // Facebook-Marketplace-style distance presets (miles).
  const RADIUS_OPTIONS = [2, 5, 10, 20, 40, 60, 80, 100];

  function set(key, value) {
    setForm((f) => ({ ...f, [key]: value }));
  }

  async function submit(e) {
    e.preventDefault();
    setError('');
    if (form.password.length < 6) return setError('Password must be at least 6 characters.');
    setBusy(true);
    try {
      await signup({ ...form, role, search_radius: Number(form.radius) });
      // Caretakers finish their profile in onboarding; owners head to their dashboard.
      navigate(role === 'caretaker' ? '/become-a-caretaker' : '/dashboard', { replace: true });
    } catch (err) {
      setError(err.message);
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="container-rh flex min-h-[70vh] items-center justify-center py-12">
      <div className="w-full max-w-md">
        <div className="mb-6 flex flex-col items-center text-center">
          <FullLogo className="h-20" />
          <h1 className="mt-3 font-display text-2xl font-700">Join The Ranch Hand</h1>
          <p className="text-charcoal-muted">Create your free account.</p>
        </div>

        <form onSubmit={submit} className="card space-y-4">
          {error && <div className="rounded-md border border-clay bg-clay/10 px-4 py-3 text-sm text-clay-dark">{error}</div>}

          {/* Role toggle */}
          <div>
            <span className="label">I want to…</span>
            <div className="grid grid-cols-2 gap-2">
              {[
                { key: 'owner', title: 'Find care', sub: 'I own animals' },
                { key: 'caretaker', title: 'Give care', sub: 'I’m a sitter' },
              ].map((r) => (
                <button
                  type="button" key={r.key} onClick={() => setRole(r.key)}
                  className={`rounded-md border-2 p-3 text-left transition-colors ${role === r.key ? 'border-barn bg-barn/5' : 'border-line hover:border-saddle-light'}`}
                >
                  <span className="block font-700 text-saddle-dark">{r.title}</span>
                  <span className="block text-xs text-charcoal-muted">{r.sub}</span>
                </button>
              ))}
            </div>
          </div>

          <label className="block">
            <span className="label">Full name</span>
            <input className="input" value={form.name} onChange={(e) => set('name', e.target.value)} required />
          </label>
          <label className="block">
            <span className="label">Email</span>
            <input type="email" className="input" value={form.email} onChange={(e) => set('email', e.target.value)} required autoComplete="email" />
          </label>
          <div className="block">
            <span className="label" id="location-label">Location</span>
            <AddressInput value={form.location} onChange={(val) => set('location', val)} id="signup-location" />
          </div>

          {/* Radius prompt appears once a location is chosen, like Facebook Marketplace. */}
          {form.location.trim() && (
            <label className="block rounded-md border border-line bg-cream-100 p-3">
              <span className="label">Search radius</span>
              <div className="flex items-center gap-3">
                <input
                  type="range" min="0" max={RADIUS_OPTIONS.length - 1}
                  value={RADIUS_OPTIONS.indexOf(form.radius) === -1 ? 3 : RADIUS_OPTIONS.indexOf(form.radius)}
                  onChange={(e) => set('radius', RADIUS_OPTIONS[Number(e.target.value)])}
                  className="flex-1 accent-barn"
                  aria-label="Search radius in miles"
                />
                <select
                  value={form.radius}
                  onChange={(e) => set('radius', Number(e.target.value))}
                  className="input w-28 py-1.5"
                  aria-label="Search radius preset"
                >
                  {RADIUS_OPTIONS.map((m) => (
                    <option key={m} value={m}>{m} mi</option>
                  ))}
                </select>
              </div>
              <p className="mt-1.5 text-xs text-charcoal-muted">
                Show {role === 'caretaker' ? 'jobs' : 'sitters'} within <strong>{form.radius} miles</strong> of {form.location}.
              </p>
            </label>
          )}
          <label className="block">
            <span className="label">Password</span>
            <input type="password" className="input" value={form.password} onChange={(e) => set('password', e.target.value)} required autoComplete="new-password" />
          </label>

          <button type="submit" disabled={busy} className="btn-primary w-full">
            {busy ? 'Creating…' : role === 'caretaker' ? 'Continue to onboarding' : 'Create account'}
          </button>
        </form>

        <p className="mt-6 text-center text-sm text-charcoal-muted">
          Already have an account? <Link to="/login" className="font-bold text-barn hover:underline">Log in</Link>
        </p>
      </div>
    </div>
  );
}
