// Caretaker profile: photo, bio, experience, animals, services+pricing, reviews,
// a simple availability calendar, and a "Request Booking" call to action.
import { useEffect, useState } from 'react';
import { Link, useParams, useNavigate } from 'react-router-dom';
import Stars from '../components/Stars';
import { ScheduleList } from '../components/WeeklyAvailability';
import { api } from '../lib/api';
import { useAuth } from '../context/AuthContext';
import { SERVICE_LABELS, SERVICE_UNITS, ANIMAL_ICONS, money, formatDate } from '../lib/constants';

export default function CaretakerProfile() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const [data, setData] = useState(null);
  const [error, setError] = useState('');

  useEffect(() => {
    api
      .get(`/caretakers/${id}`, { auth: false })
      .then(setData)
      .catch((e) => setError(e.message));
  }, [id]);

  if (error) return <div className="container-rh py-24 text-center text-charcoal-muted">{error}</div>;
  if (!data) return <div className="container-rh py-24 text-center text-charcoal-muted">Loading caretaker…</div>;

  const { caretaker: c, reviews, resume } = data;

  return (
    <div className="bg-cream-200/40">
      {/* Header band */}
      <div className="bg-cream-100 border-b border-line">
        <div className="container-rh flex flex-col gap-6 py-8 sm:flex-row sm:items-center">
          <img
            src={c.photo_url}
            alt={`${c.name}, caretaker`}
            className="h-32 w-32 rounded-xl object-cover shadow-card sm:h-40 sm:w-40"
          />
          <div className="flex-1">
            <h1 className="font-display text-3xl font-700">{c.name}</h1>
            <p className="mt-1 text-charcoal-muted">📍 {c.location} · {c.experience_years} years of experience</p>
            <div className="mt-2">
              {c.review_count > 0 ? <Stars value={c.rating} count={c.review_count} /> : <span className="text-charcoal-muted">New caretaker</span>}
            </div>
            {c.headline && <p className="mt-2 max-w-2xl font-display text-lg italic text-saddle">“{c.headline}”</p>}
          </div>
          <div className="text-right">
            {c.min_price != null && (
              <p className="mb-2 text-sm text-charcoal-muted">from <span className="text-2xl font-700 text-saddle-dark">{money(c.min_price)}</span></p>
            )}
            <BookButton c={c} user={user} navigate={navigate} />
          </div>
        </div>
      </div>

      <div className="container-rh grid gap-8 py-10 lg:grid-cols-[1fr_340px]">
        {/* Left column */}
        <div className="space-y-8">
          <section className="card">
            <h2 className="text-xl font-700">About {c.name.split(' ')[0]}</h2>
            <p className="mt-3 leading-relaxed text-charcoal">{c.bio}</p>
          </section>

          <section className="card">
            <h2 className="text-xl font-700">Animals I handle</h2>
            <div className="mt-3 flex flex-wrap gap-2">
              {c.animal_types.map((a) => (
                <span key={a} className="chip">
                  <span aria-hidden="true">{ANIMAL_ICONS[a] || '🐾'}</span> {a}
                </span>
              ))}
            </div>
          </section>

          {resume && (
            <section className="card">
              <h2 className="text-xl font-700">Qualifications</h2>
              <div className="mt-3 flex items-center gap-3 rounded-md border border-line bg-cream-200/40 p-3">
                <span className="flex h-11 w-11 items-center justify-center rounded-md bg-sage/20 text-xl" aria-hidden="true">📄</span>
                <div className="min-w-0 flex-1">
                  <p className="truncate font-bold text-saddle-dark" title={resume.name}>{resume.name}</p>
                  <p className="text-xs text-charcoal-muted">Resume &amp; qualifications</p>
                </div>
                <a href={resume.data} download={resume.name} className="btn-secondary px-4 py-2 text-sm">
                  Download
                </a>
              </div>
            </section>
          )}

          <section className="card">
            <h2 className="text-xl font-700">Services &amp; pricing</h2>
            <ul className="mt-3 divide-y divide-line">
              {c.services.map((s) => (
                <li key={s} className="flex items-center justify-between py-3">
                  <div>
                    <p className="font-bold text-saddle-dark">{SERVICE_LABELS[s] || s}</p>
                    <p className="text-xs text-charcoal-muted">{SERVICE_UNITS[s]}</p>
                  </div>
                  <span className="font-display text-lg font-700 text-barn">{money(c.rates[s])}</span>
                </li>
              ))}
            </ul>
          </section>

          <section className="card">
            <h2 className="text-xl font-700">Reviews</h2>
            {reviews.length === 0 ? (
              <p className="mt-3 text-charcoal-muted">No reviews yet. Be the first to book and review!</p>
            ) : (
              <ul className="mt-4 space-y-5">
                {reviews.map((r) => (
                  <li key={r.id} className="border-b border-line pb-5 last:border-0 last:pb-0">
                    <div className="flex items-center gap-3">
                      <img src={r.owner_photo || `https://i.pravatar.cc/64?u=${r.id}`} alt="" className="h-9 w-9 rounded-full object-cover" />
                      <div>
                        <p className="font-bold text-saddle-dark">{r.owner_name}</p>
                        <p className="text-xs text-charcoal-muted">{formatDate(r.created_at)}</p>
                      </div>
                      <span className="ml-auto"><Stars value={r.rating} size="text-sm" showNumber={false} /></span>
                    </div>
                    {r.comment && <p className="mt-2 text-charcoal">{r.comment}</p>}
                  </li>
                ))}
              </ul>
            )}
          </section>
        </div>

        {/* Right column: availability + sticky booking */}
        <aside className="space-y-6">
          <div className="card">
            <h2 className="text-lg font-700">Weekly availability</h2>
            {c.availability ? (
              <ScheduleList schedule={c.availability} />
            ) : (
              c.availability_text && <p className="mt-1 text-sm text-charcoal-muted">{c.availability_text}</p>
            )}
            {c.availability_notes && (
              <p className="mt-3 rounded-md bg-cream-200/50 p-2.5 text-sm text-charcoal">{c.availability_notes}</p>
            )}
            <AvailabilityCalendar />
          </div>
          <div className="card lg:sticky lg:top-24">
            <p className="text-sm text-charcoal-muted">Starting at</p>
            <p className="font-display text-3xl font-700 text-saddle-dark">{c.min_price != null ? money(c.min_price) : '-'}</p>
            <BookButton c={c} user={user} navigate={navigate} full />
            <p className="mt-3 text-center text-xs text-charcoal-muted">You won’t be charged. Payments are simulated.</p>
          </div>
        </aside>
      </div>
    </div>
  );
}

function BookButton({ c, user, navigate, full }) {
  const cls = `btn-primary ${full ? 'mt-4 w-full' : ''}`;
  if (user && user.role === 'caretaker') {
    return <span className={`${full ? 'mt-4 block ' : ''}text-sm text-charcoal-muted`}>Log in as an owner to book.</span>;
  }
  return (
    <button
      className={cls}
      onClick={() => (user ? navigate(`/book/${c.id}`) : navigate('/login', { state: { from: { pathname: `/book/${c.id}` } } }))}
    >
      Request Booking
    </button>
  );
}

// Lightweight read-only availability calendar (current month, mostly-open demo).
function AvailabilityCalendar() {
  const today = new Date();
  const year = today.getFullYear();
  const month = today.getMonth();
  const firstDay = new Date(year, month, 1).getDay();
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const monthName = today.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

  // Deterministic "booked" days for demo texture.
  const booked = new Set([4, 5, 12, 19, 20, 27].map((d) => d));

  return (
    <div className="mt-4">
      <p className="mb-2 text-center text-sm font-bold text-saddle-dark">{monthName}</p>
      <div className="grid grid-cols-7 gap-1 text-center text-[11px] text-charcoal-muted">
        {['S', 'M', 'T', 'W', 'T', 'F', 'S'].map((d, i) => (
          <span key={i} className="py-1 font-bold">{d}</span>
        ))}
        {Array.from({ length: firstDay }).map((_, i) => <span key={`x${i}`} />)}
        {Array.from({ length: daysInMonth }).map((_, i) => {
          const day = i + 1;
          const isBooked = booked.has(day);
          const isPast = day < today.getDate();
          return (
            <span
              key={day}
              title={isBooked ? 'Booked' : isPast ? 'Past' : 'Available'}
              className={`rounded py-1 text-xs ${
                isPast ? 'text-line' : isBooked ? 'bg-barn/10 text-barn line-through' : 'bg-sage/20 text-sage-dark'
              }`}
            >
              {day}
            </span>
          );
        })}
      </div>
      <div className="mt-3 flex justify-center gap-4 text-[11px] text-charcoal-muted">
        <span className="flex items-center gap-1"><span className="h-3 w-3 rounded bg-sage/40" /> Available</span>
        <span className="flex items-center gap-1"><span className="h-3 w-3 rounded bg-barn/20" /> Booked</span>
      </div>
    </div>
  );
}
