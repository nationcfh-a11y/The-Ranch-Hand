// Booking flow: 1) details (service, dates, animals, instructions) → 2) review with
// fee breakdown (caretaker 15% commission + owner 7% service fee) → 3) confirmation.
// Payment is fully SIMULATED — no processor, no real charge.
import { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { api } from '../lib/api';
import { useAuth } from '../context/AuthContext';
import {
  SERVICE_LABELS, SERVICE_UNITS, ANIMAL_TYPES, ANIMAL_ICONS, FEES, money, formatDate,
} from '../lib/constants';

const STEPS = ['Details', 'Review', 'Confirmed'];

export default function Booking() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();

  const [c, setC] = useState(null);
  const [step, setStep] = useState(0);
  const [error, setError] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [confirmed, setConfirmed] = useState(null);

  // Form state.
  const [service, setService] = useState('');
  const [start, setStart] = useState('');
  const [end, setEnd] = useState('');
  const [animals, setAnimals] = useState([]); // [{type, count}]
  const [instructions, setInstructions] = useState('');

  useEffect(() => {
    api.get(`/caretakers/${id}`, { auth: false }).then((d) => {
      setC(d.caretaker);
      setService(d.caretaker.services[0] || '');
    }).catch((e) => setError(e.message));
  }, [id]);

  // Quantity = number of nights/visits implied by the date range (min 1).
  const quantity = useMemo(() => {
    if (!start || !end) return 1;
    const ms = new Date(end) - new Date(start);
    return Math.max(1, Math.round(ms / 86400000) || 1);
  }, [start, end]);

  const rate = c && service ? Number(c.rates[service]) || 0 : 0;
  const base = rate * quantity;
  const ownerFee = round2(base * FEES.owner_service_fee);
  const caretakerFee = round2(base * FEES.caretaker_commission);
  const total = round2(base + ownerFee);
  const caretakerPayout = round2(base - caretakerFee);

  function toggleAnimal(type) {
    setAnimals((prev) =>
      prev.some((a) => a.type === type) ? prev.filter((a) => a.type !== type) : [...prev, { type, count: 1 }]
    );
  }
  function setCount(type, count) {
    setAnimals((prev) => prev.map((a) => (a.type === type ? { ...a, count: Math.max(1, Number(count) || 1) } : a)));
  }

  function goReview(e) {
    e.preventDefault();
    setError('');
    if (!service) return setError('Please choose a service.');
    if (!start || !end) return setError('Please choose start and end dates.');
    if (new Date(end) < new Date(start)) return setError('End date must be after the start date.');
    if (animals.length === 0) return setError('Please select at least one animal.');
    setStep(1);
  }

  async function submitBooking() {
    setSubmitting(true);
    setError('');
    try {
      const { booking } = await api.post('/bookings', {
        caretaker_id: c.id, service, animals, start_date: start, end_date: end, instructions, quantity,
      });
      setConfirmed(booking);
      setStep(2);
    } catch (e) {
      setError(e.message);
    } finally {
      setSubmitting(false);
    }
  }

  if (error && !c) return <div className="container-rh py-24 text-center text-charcoal-muted">{error}</div>;
  if (!c) return <div className="container-rh py-24 text-center text-charcoal-muted">Loading…</div>;

  return (
    <div className="container-rh max-w-3xl py-10">
      {/* Stepper */}
      <ol className="mb-8 flex items-center justify-center gap-2 sm:gap-4">
        {STEPS.map((label, i) => (
          <li key={label} className="flex items-center gap-2">
            <span className={`flex h-8 w-8 items-center justify-center rounded-full text-sm font-700 ${
              i <= step ? 'bg-barn text-cream-100' : 'bg-cream-200 text-charcoal-muted'
            }`}>{i + 1}</span>
            <span className={`text-sm font-bold ${i <= step ? 'text-saddle-dark' : 'text-charcoal-muted'}`}>{label}</span>
            {i < STEPS.length - 1 && <span className="hidden h-px w-8 bg-line sm:block" />}
          </li>
        ))}
      </ol>

      {error && c && <div className="mb-4 rounded-md border border-clay bg-clay/10 px-4 py-3 text-sm text-clay-dark">{error}</div>}

      {/* Caretaker mini-header */}
      <div className="mb-6 flex items-center gap-3">
        <img src={c.photo_url} alt="" className="h-12 w-12 rounded-full object-cover" />
        <div>
          <p className="font-700 text-saddle-dark">Booking with {c.name}</p>
          <p className="text-sm text-charcoal-muted">📍 {c.location}</p>
        </div>
      </div>

      {/* ---- Step 0: Details ---- */}
      {step === 0 && (
        <form onSubmit={goReview} className="card space-y-6">
          <div>
            <label className="label" htmlFor="service">Service</label>
            <select id="service" value={service} onChange={(e) => setService(e.target.value)} className="input">
              {c.services.map((s) => (
                <option key={s} value={s}>{SERVICE_LABELS[s]} — {money(c.rates[s])} {SERVICE_UNITS[s]}</option>
              ))}
            </select>
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label className="label" htmlFor="start">Start date</label>
              <input id="start" type="date" value={start} onChange={(e) => setStart(e.target.value)} className="input" required />
            </div>
            <div>
              <label className="label" htmlFor="end">End date</label>
              <input id="end" type="date" value={end} min={start || undefined} onChange={(e) => setEnd(e.target.value)} className="input" required />
            </div>
          </div>

          <div>
            <span className="label">Which animals?</span>
            <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
              {ANIMAL_TYPES.map((a) => {
                const picked = animals.find((x) => x.type === a);
                return (
                  <div key={a} className={`rounded-md border p-2 ${picked ? 'border-barn bg-barn/5' : 'border-line'}`}>
                    <label className="flex cursor-pointer items-center gap-2 text-sm font-semibold">
                      <input type="checkbox" checked={!!picked} onChange={() => toggleAnimal(a)} className="accent-barn" />
                      <span aria-hidden="true">{ANIMAL_ICONS[a]}</span> {a}
                    </label>
                    {picked && (
                      <input
                        type="number" min="1" value={picked.count}
                        onChange={(e) => setCount(a, e.target.value)}
                        className="input mt-2 py-1 text-sm" aria-label={`Number of ${a}`}
                      />
                    )}
                  </div>
                );
              })}
            </div>
          </div>

          <div>
            <label className="label" htmlFor="instructions">Special instructions (feeding, meds, turnout)</label>
            <textarea
              id="instructions" rows="4" value={instructions} onChange={(e) => setInstructions(e.target.value)}
              placeholder="e.g. Feed grain twice daily. Bandit gets his joint supplement each morning. Turnout in the north paddock only."
              className="input"
            />
          </div>

          <div className="flex items-center justify-between">
            <Link to={`/caretakers/${c.id}`} className="btn-ghost">← Back to profile</Link>
            <button type="submit" className="btn-primary">Review booking</button>
          </div>
        </form>
      )}

      {/* ---- Step 1: Review + fee breakdown ---- */}
      {step === 1 && (
        <div className="card space-y-6">
          <h2 className="text-xl font-700">Review &amp; confirm</h2>

          <dl className="divide-y divide-line text-sm">
            <Row label="Service"><span className="font-bold">{SERVICE_LABELS[service]}</span></Row>
            <Row label="Dates">{formatDate(start)} → {formatDate(end)}</Row>
            <Row label={SERVICE_UNITS[service]?.includes('night') ? 'Nights' : 'Visits'}>{quantity}</Row>
            <Row label="Animals">
              {animals.map((a) => `${a.count} ${a.type}`).join(', ')}
            </Row>
            {instructions && <Row label="Instructions"><span className="text-charcoal-muted">{instructions}</span></Row>}
          </dl>

          {/* Fee breakdown (mirrors Rover's model) */}
          <div className="rounded-lg border border-line bg-cream-200/50 p-4">
            <h3 className="mb-3 font-display text-base font-700">Price breakdown</h3>
            <div className="space-y-2 text-sm">
              <FeeRow label={`${money(rate)} × ${quantity} ${SERVICE_UNITS[service]?.replace('per ', '') || ''}`} value={money(base)} />
              <FeeRow label="Owner service fee (7%)" value={money(ownerFee)} hint="Helps run the platform & support." />
              <div className="my-2 h-px bg-line" />
              <FeeRow label="Total you pay" value={money(total)} strong />
            </div>
            <div className="mt-3 rounded-md bg-cream-100 p-3 text-xs text-charcoal-muted">
              <p className="flex justify-between"><span>Caretaker’s rate</span><span>{money(base)}</span></p>
              <p className="flex justify-between"><span>Platform commission (15%)</span><span>−{money(caretakerFee)}</span></p>
              <p className="mt-1 flex justify-between font-bold text-saddle-dark"><span>{c.name} receives</span><span>{money(caretakerPayout)}</span></p>
            </div>
          </div>

          <div className="rounded-md border border-hay bg-hay/10 px-4 py-3 text-sm text-saddle-dark">
            🔒 This is a <strong>simulated</strong> booking. No payment method is required and no money changes hands.
          </div>

          <div className="flex items-center justify-between">
            <button onClick={() => setStep(0)} className="btn-ghost">← Edit details</button>
            <button onClick={submitBooking} disabled={submitting} className="btn-primary">
              {submitting ? 'Confirming…' : `Confirm booking · ${money(total)}`}
            </button>
          </div>
        </div>
      )}

      {/* ---- Step 2: Confirmation ---- */}
      {step === 2 && confirmed && (
        <div className="card text-center">
          <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-sage/20 text-3xl" aria-hidden="true">✅</div>
          <h2 className="mt-4 font-display text-2xl font-700">Booking requested!</h2>
          <p className="mt-2 text-charcoal-muted">
            Your request was sent to {c.name}. You’ll see it as <strong>pending</strong> in your dashboard until they confirm.
          </p>
          <div className="mx-auto mt-6 max-w-sm rounded-lg border border-line bg-cream-200/50 p-4 text-left text-sm">
            <p className="flex justify-between"><span className="text-charcoal-muted">Service</span><span className="font-bold">{confirmed.service_label}</span></p>
            <p className="flex justify-between"><span className="text-charcoal-muted">Dates</span><span>{formatDate(confirmed.start_date)} → {formatDate(confirmed.end_date)}</span></p>
            <p className="flex justify-between"><span className="text-charcoal-muted">Total</span><span className="font-700 text-barn">{money(confirmed.total_price)}</span></p>
          </div>
          <div className="mt-6 flex justify-center gap-3">
            <Link to="/dashboard" className="btn-primary">Go to dashboard</Link>
            <Link to="/search" className="btn-secondary">Find more sitters</Link>
          </div>
        </div>
      )}
    </div>
  );
}

function Row({ label, children }) {
  return (
    <div className="flex justify-between gap-4 py-2.5">
      <dt className="text-charcoal-muted">{label}</dt>
      <dd className="text-right text-charcoal">{children}</dd>
    </div>
  );
}
function FeeRow({ label, value, strong, hint }) {
  return (
    <div className="flex items-start justify-between">
      <span className={strong ? 'font-700 text-saddle-dark' : 'text-charcoal'}>
        {label}
        {hint && <span className="block text-xs text-charcoal-muted">{hint}</span>}
      </span>
      <span className={strong ? 'font-700 text-barn' : 'text-charcoal'}>{value}</span>
    </div>
  );
}
function round2(n) {
  return Math.round((n + Number.EPSILON) * 100) / 100;
}
