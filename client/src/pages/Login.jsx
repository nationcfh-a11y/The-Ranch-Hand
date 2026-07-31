// Login page. Redirects back to wherever the user was headed (state.from).
import { useState } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { FullLogo } from '../components/Logo';

export default function Login() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const from = location.state?.from?.pathname || '/dashboard';

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  async function submit(e) {
    e.preventDefault();
    setError('');
    setBusy(true);
    try {
      await login(email, password);
      navigate(from, { replace: true });
    } catch (err) {
      setError(err.message);
    } finally {
      setBusy(false);
    }
  }

  function fill(demoEmail) {
    setEmail(demoEmail);
    setPassword('password123');
  }

  return (
    <div className="container-rh flex min-h-[70vh] items-center justify-center py-12">
      <div className="w-full max-w-md">
        <div className="mb-6 flex flex-col items-center text-center">
          <FullLogo className="h-20" />
          <h1 className="mt-3 font-display text-2xl font-700">Welcome back</h1>
          <p className="text-charcoal-muted">Log in to manage your bookings.</p>
        </div>

        <form onSubmit={submit} className="card space-y-4">
          {error && <div className="rounded-md border border-clay bg-clay/10 px-4 py-3 text-sm text-clay-dark">{error}</div>}
          <label className="block">
            <span className="label">Email</span>
            <input type="email" className="input" value={email} onChange={(e) => setEmail(e.target.value)} required autoComplete="email" />
          </label>
          <label className="block">
            <span className="label">Password</span>
            <input type="password" className="input" value={password} onChange={(e) => setPassword(e.target.value)} required autoComplete="current-password" />
          </label>
          <button type="submit" disabled={busy} className="btn-primary w-full">{busy ? 'Logging in…' : 'Log in'}</button>
        </form>

        {/* Demo helpers so reviewers can log in instantly. */}
        <div className="mt-4 rounded-lg border border-line bg-cream-100 p-4 text-sm">
          <p className="mb-2 font-bold text-saddle-dark">Demo accounts (password: <code className="font-mono">password123</code>)</p>
          <div className="flex flex-col gap-1.5">
            <button onClick={() => fill('karen.mitchell@ranchhand.test')} className="text-left text-barn hover:underline">→ Owner: karen.mitchell@ranchhand.test</button>
            <button onClick={() => fill('dale.whitaker@ranchhand.test')} className="text-left text-barn hover:underline">→ Caretaker: dale.whitaker@ranchhand.test</button>
          </div>
        </div>

        <p className="mt-6 text-center text-sm text-charcoal-muted">
          New here? <Link to="/signup" className="font-bold text-barn hover:underline">Create an account</Link>
        </p>
      </div>
    </div>
  );
}
