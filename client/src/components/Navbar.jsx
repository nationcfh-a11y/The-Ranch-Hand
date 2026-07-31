// Top navigation. Adapts to auth state and is mobile-responsive (hamburger menu).
import { useState } from 'react';
import { Link, NavLink, useNavigate } from 'react-router-dom';
import Logo from './Logo';
import { useAuth } from '../context/AuthContext';

export default function Navbar() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [open, setOpen] = useState(false);

  const links = [
    { to: '/search', label: 'Register Your Ranch' },
    { to: '/become-a-caretaker', label: 'Become A Hand' },
  ];

  const handleLogout = () => {
    logout();
    setOpen(false);
    navigate('/');
  };

  const navClass = ({ isActive }) =>
    `px-3 py-2 rounded-md text-sm font-bold transition-colors ${
      isActive ? 'text-barn' : 'text-saddle hover:text-barn'
    }`;

  return (
    <header className="sticky top-0 z-40 border-b border-line bg-cream-100/95 backdrop-blur">
      <nav className="container-rh flex items-center justify-between py-3">
        <Link to="/" aria-label="The Ranch Hand home" onClick={() => setOpen(false)}>
          <Logo />
        </Link>

        {/* Desktop */}
        <div className="hidden items-center gap-1 md:flex">
          {links.map((l) => (
            <NavLink key={l.to} to={l.to} className={navClass}>
              {l.label}
            </NavLink>
          ))}
          {user ? (
            <>
              <NavLink to="/dashboard" className={navClass}>Dashboard</NavLink>
              <span className="mx-2 hidden text-sm text-charcoal-muted lg:inline">Hi, {user.name.split(' ')[0]}</span>
              <button onClick={handleLogout} className="btn-ghost px-3 py-2 text-sm">Log out</button>
            </>
          ) : (
            <>
              <Link to="/login" className="btn-ghost px-4 py-2 text-sm">Log in</Link>
              <Link to="/signup" className="btn-primary px-4 py-2 text-sm">Sign up</Link>
            </>
          )}
        </div>

        {/* Mobile toggle */}
        <button
          className="btn-ghost p-2 md:hidden"
          aria-label="Toggle menu"
          aria-expanded={open}
          onClick={() => setOpen((v) => !v)}
        >
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2">
            {open ? <path d="M6 6l12 12M18 6L6 18" strokeLinecap="round" /> : <path d="M4 7h16M4 12h16M4 17h16" strokeLinecap="round" />}
          </svg>
        </button>
      </nav>

      {/* Mobile menu */}
      {open && (
        <div className="border-t border-line bg-cream-100 md:hidden">
          <div className="container-rh flex flex-col gap-1 py-3">
            {links.map((l) => (
              <NavLink key={l.to} to={l.to} className={navClass} onClick={() => setOpen(false)}>
                {l.label}
              </NavLink>
            ))}
            {user ? (
              <>
                <NavLink to="/dashboard" className={navClass} onClick={() => setOpen(false)}>Dashboard</NavLink>
                <button onClick={handleLogout} className="btn-secondary mt-2">Log out</button>
              </>
            ) : (
              <div className="mt-2 flex gap-2">
                <Link to="/login" className="btn-secondary flex-1" onClick={() => setOpen(false)}>Log in</Link>
                <Link to="/signup" className="btn-primary flex-1" onClick={() => setOpen(false)}>Sign up</Link>
              </div>
            )}
          </div>
        </div>
      )}
    </header>
  );
}
