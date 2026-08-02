import { Link } from 'react-router-dom';
import { Mark } from './Logo';
import { ANIMAL_TYPES, SERVICES } from '../lib/constants';

export default function Footer() {
  return (
    <footer className="mt-20 bg-saddle-dark text-cream/85">
      <div className="container-rh grid grid-cols-2 gap-8 py-12 md:grid-cols-4">
        <div className="col-span-2 md:col-span-1">
          <div className="flex items-center gap-2.5">
            <Mark className="h-9" />
            <span className="font-display text-lg font-700 text-cream-100">The Ranch Hand</span>
          </div>
          <p className="mt-3 max-w-xs text-sm leading-relaxed">
            Trusted horse sitters, farm sitters, and livestock caretakers. Book a barn sitter, equine sitter, or ranch
            sitter who knows the difference between a halter and a hackamore.
          </p>
        </div>

        <div>
          <h4 className="mb-3 font-display text-sm font-700 text-hay-light">Services</h4>
          <ul className="space-y-2 text-sm">
            {SERVICES.slice(0, 5).map((s) => (
              <li key={s.key}>
                <Link to={`/search?service=${s.key}`} className="hover:text-cream-100">{s.label}</Link>
              </li>
            ))}
          </ul>
        </div>

        <div>
          <h4 className="mb-3 font-display text-sm font-700 text-hay-light">Animals</h4>
          <ul className="space-y-2 text-sm">
            {ANIMAL_TYPES.map((a) => (
              <li key={a}>
                <Link to={`/search?animal=${encodeURIComponent(a)}`} className="hover:text-cream-100">{a}</Link>
              </li>
            ))}
          </ul>
        </div>

        <div>
          <h4 className="mb-3 font-display text-sm font-700 text-hay-light">Company</h4>
          <ul className="space-y-2 text-sm">
            <li><Link to="/become-a-caretaker" className="hover:text-cream-100">Become a Caretaker</Link></li>
            <li><Link to="/search" className="hover:text-cream-100">Find a Sitter</Link></li>
            <li><Link to="/signup" className="hover:text-cream-100">Sign Up</Link></li>
            <li><Link to="/login" className="hover:text-cream-100">Log In</Link></li>
          </ul>
        </div>
      </div>
      <div className="border-t border-cream/15">
        <div className="container-rh flex flex-col items-center justify-between gap-2 py-5 text-xs text-cream/65 sm:flex-row">
          <p>© {new Date().getFullYear()} The Ranch Hand. A demo marketplace. Bookings & payments are simulated.</p>
          <p>Built locally · No real charges are ever made.</p>
        </div>
      </div>
    </footer>
  );
}
