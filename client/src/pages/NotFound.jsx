import { Link } from 'react-router-dom';

export default function NotFound() {
  return (
    <div className="container-rh flex min-h-[60vh] flex-col items-center justify-center py-20 text-center">
      <div className="text-6xl" aria-hidden="true">🐎</div>
      <h1 className="mt-4 font-display text-3xl font-700">This trail leads nowhere</h1>
      <p className="mt-2 text-charcoal-muted">We couldn’t find that page. Let’s get you back to the barn.</p>
      <Link to="/" className="btn-primary mt-6">Back home</Link>
    </div>
  );
}
