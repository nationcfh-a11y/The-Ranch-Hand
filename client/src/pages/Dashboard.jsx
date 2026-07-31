// Dashboard for both roles. Owners see bookings they requested + can review/message;
// caretakers see incoming bookings they can confirm/complete. Plus a messages panel.
import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../lib/api';
import { useAuth } from '../context/AuthContext';
import { ANIMAL_ICONS, STATUS_STYLES, money, formatDate } from '../lib/constants';

export default function Dashboard() {
  const { user } = useAuth();
  const isCaretaker = user.role === 'caretaker';
  const [tab, setTab] = useState('bookings');
  const [bookings, setBookings] = useState([]);
  const [threads, setThreads] = useState([]);
  const [loading, setLoading] = useState(true);

  function loadBookings() {
    return api.get('/bookings/mine').then((d) => setBookings(d.bookings));
  }

  useEffect(() => {
    Promise.all([
      loadBookings(),
      api.get('/messages/threads').then((d) => setThreads(d.threads)).catch(() => {}),
    ]).finally(() => setLoading(false));
  }, []);

  const now = new Date();
  const upcoming = bookings.filter((b) => new Date(b.end_date) >= now && b.status !== 'cancelled');
  const past = bookings.filter((b) => new Date(b.end_date) < now || b.status === 'cancelled');

  async function updateStatus(id, status) {
    await api.patch(`/bookings/${id}/status`, { status });
    await loadBookings();
  }

  return (
    <div className="bg-cream-200/40 min-h-[60vh]">
      <div className="container-rh py-10">
        {/* Header */}
        <div className="flex flex-wrap items-center justify-between gap-4">
          <div className="flex items-center gap-3">
            <img src={user.photo_url || `https://i.pravatar.cc/96?u=${user.email}`} alt="" className="h-14 w-14 rounded-full object-cover" />
            <div>
              <h1 className="font-display text-2xl font-700">Welcome back, {user.name.split(' ')[0]}</h1>
              <p className="text-sm text-charcoal-muted capitalize">{user.role} account</p>
            </div>
          </div>
          {isCaretaker ? (
            <Link to="/become-a-caretaker" className="btn-secondary">Edit my profile</Link>
          ) : (
            <Link to="/search" className="btn-primary">Find a sitter</Link>
          )}
        </div>

        {/* Stats */}
        <div className="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
          <Stat label="Upcoming" value={upcoming.length} />
          <Stat label="Past" value={past.length} />
          <Stat label="Pending" value={bookings.filter((b) => b.status === 'pending').length} />
          <Stat label="Messages" value={threads.length} />
        </div>

        {/* Tabs */}
        <div className="mt-8 flex gap-2 border-b border-line">
          {['bookings', 'messages'].map((t) => (
            <button
              key={t}
              onClick={() => setTab(t)}
              className={`px-4 py-2.5 text-sm font-bold capitalize transition-colors ${
                tab === t ? 'border-b-2 border-barn text-barn' : 'text-charcoal-muted hover:text-saddle'
              }`}
            >
              {t}
            </button>
          ))}
        </div>

        {loading ? (
          <p className="py-12 text-center text-charcoal-muted">Loading…</p>
        ) : tab === 'bookings' ? (
          <div className="mt-6 space-y-8">
            <BookingGroup
              title="Upcoming & active" empty="No upcoming visits yet."
              bookings={upcoming} isCaretaker={isCaretaker} onStatus={updateStatus}
            />
            <BookingGroup
              title="Past & cancelled" empty="No past visits."
              bookings={past} isCaretaker={isCaretaker} onStatus={updateStatus} past
            />
          </div>
        ) : (
          <MessagesPanel threads={threads} />
        )}
      </div>
    </div>
  );
}

function Stat({ label, value }) {
  return (
    <div className="card py-4 text-center">
      <p className="font-display text-3xl font-700 text-barn">{value}</p>
      <p className="text-sm text-charcoal-muted">{label}</p>
    </div>
  );
}

function BookingGroup({ title, bookings, empty, isCaretaker, onStatus, past }) {
  return (
    <section>
      <h2 className="mb-3 font-display text-lg font-700">{title}</h2>
      {bookings.length === 0 ? (
        <div className="card text-sm text-charcoal-muted">{empty}</div>
      ) : (
        <div className="space-y-3">
          {bookings.map((b) => (
            <BookingRow key={b.id} b={b} isCaretaker={isCaretaker} onStatus={onStatus} past={past} />
          ))}
        </div>
      )}
    </section>
  );
}

function BookingRow({ b, isCaretaker, onStatus, past }) {
  const counterpartName = isCaretaker ? b.owner_name : b.caretaker_name;
  const counterpartPhoto = isCaretaker ? b.owner_photo : b.caretaker_photo;
  return (
    <div className="card flex flex-col gap-4 sm:flex-row sm:items-center">
      <img src={counterpartPhoto || `https://i.pravatar.cc/80?u=${b.id}`} alt="" className="h-14 w-14 rounded-full object-cover" />
      <div className="flex-1">
        <div className="flex flex-wrap items-center gap-2">
          <p className="font-700 text-saddle-dark">{b.service_label}</p>
          <span className={`badge ${STATUS_STYLES[b.status]} capitalize`}>{b.status}</span>
        </div>
        <p className="text-sm text-charcoal-muted">
          {isCaretaker ? 'For' : 'With'} {counterpartName} · {formatDate(b.start_date)} → {formatDate(b.end_date)}
        </p>
        <p className="mt-1 text-sm">
          {b.animals.map((a) => `${ANIMAL_ICONS[a.type] || ''} ${a.count} ${a.type}`).join(' · ')}
        </p>
        {b.instructions && <p className="mt-1 line-clamp-1 text-xs text-charcoal-muted" title={b.instructions}>“{b.instructions}”</p>}
      </div>
      <div className="text-right">
        <p className="font-display text-lg font-700 text-saddle-dark">{money(b.total_price)}</p>
        <p className="text-xs text-charcoal-muted">{isCaretaker ? 'you net ' + money(b.base_price - b.caretaker_fee) : 'total'}</p>

        {/* Role-specific actions */}
        <div className="mt-2 flex flex-wrap justify-end gap-2">
          {isCaretaker && b.status === 'pending' && (
            <>
              <button onClick={() => onStatus(b.id, 'confirmed')} className="btn-primary px-3 py-1.5 text-xs">Accept</button>
              <button onClick={() => onStatus(b.id, 'cancelled')} className="btn-ghost px-3 py-1.5 text-xs">Decline</button>
            </>
          )}
          {isCaretaker && b.status === 'confirmed' && (
            <button onClick={() => onStatus(b.id, 'completed')} className="btn-secondary px-3 py-1.5 text-xs">Mark complete</button>
          )}
          {!isCaretaker && b.status === 'pending' && (
            <button onClick={() => onStatus(b.id, 'cancelled')} className="btn-ghost px-3 py-1.5 text-xs">Cancel</button>
          )}
          {!isCaretaker && (
            <Link to={`/caretakers/${b.caretaker_id}`} className="btn-ghost px-3 py-1.5 text-xs">View sitter</Link>
          )}
        </div>
      </div>
    </div>
  );
}

function MessagesPanel({ threads }) {
  if (threads.length === 0) {
    return (
      <div className="card mt-6 text-center text-charcoal-muted">
        <div className="text-3xl" aria-hidden="true">✉️</div>
        <p className="mt-2">No messages yet. Start a conversation from a caretaker’s profile.</p>
      </div>
    );
  }
  return (
    <div className="mt-6 space-y-3">
      {threads.map((t) => (
        <div key={t.partner_id} className="card flex items-center gap-3">
          <img src={t.partner_photo || `https://i.pravatar.cc/64?u=${t.partner_id}`} alt="" className="h-11 w-11 rounded-full object-cover" />
          <div className="min-w-0 flex-1">
            <p className="font-700 text-saddle-dark">{t.partner_name}</p>
            <p className="truncate text-sm text-charcoal-muted">{t.last_message}</p>
          </div>
          <span className="whitespace-nowrap text-xs text-charcoal-muted">{formatDate(t.last_at)}</span>
        </div>
      ))}
    </div>
  );
}
