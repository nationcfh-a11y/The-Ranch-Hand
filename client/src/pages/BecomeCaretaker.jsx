// "Become a Caretaker" onboarding: multi-step form.
// Step 1 Account (if not logged in as caretaker) · 2 Profile · 3 Experience & animals
// · 4 Services & rates. Saves via signup + PUT /caretakers/me/profile.
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { api } from '../lib/api';
import { useAuth } from '../context/AuthContext';
import { SERVICES, SERVICE_UNITS, ANIMAL_TYPES, ANIMAL_ICONS } from '../lib/constants';

export default function BecomeCaretaker() {
  const navigate = useNavigate();
  const { user, signup, setUser } = useAuth();

  // If already a caretaker, skip the account step.
  const alreadyCaretaker = user?.role === 'caretaker';
  const [step, setStep] = useState(alreadyCaretaker ? 1 : 0);
  const [error, setError] = useState('');
  const [saving, setSaving] = useState(false);

  // Account
  const [account, setAccount] = useState({ name: user?.name || '', email: user?.email || '', password: '' });
  // Profile
  const [profile, setProfile] = useState({
    location: user?.location || '', photo_url: user?.photo_url || '', headline: '', bio: user?.bio || '',
  });
  // Experience & animals
  const [experience, setExperience] = useState(1);
  const [animalTypes, setAnimalTypes] = useState([]);
  const [availabilityNotes, setAvailabilityNotes] = useState('');
  const [resume, setResume] = useState(null); // { name, size, dataUrl } — required qualifications doc
  // Services & rates
  const [selectedServices, setSelectedServices] = useState([]);
  const [rates, setRates] = useState({});

  const STEP_LABELS = alreadyCaretaker
    ? ['Profile', 'Experience', 'Services']
    : ['Account', 'Profile', 'Experience', 'Services'];

  function toggleAnimal(a) {
    setAnimalTypes((p) => (p.includes(a) ? p.filter((x) => x !== a) : [...p, a]));
  }

  // Read the chosen resume/qualifications file as a data URL (validated type + size).
  function handleResume(file) {
    setError('');
    if (!file) return;
    const okExt = /\.(pdf|docx?|png|jpe?g)$/i;
    if (!okExt.test(file.name)) {
      return setError('Please upload a PDF, Word document, or image.');
    }
    if (file.size > 5 * 1024 * 1024) {
      return setError('That file is too large — please keep it under 5 MB.');
    }
    const reader = new FileReader();
    reader.onload = () => setResume({ name: file.name, size: file.size, dataUrl: reader.result });
    reader.onerror = () => setError('Could not read that file. Please try another.');
    reader.readAsDataURL(file);
  }
  const prettySize = (bytes) =>
    bytes < 1024 * 1024 ? `${Math.round(bytes / 1024)} KB` : `${(bytes / 1024 / 1024).toFixed(1)} MB`;
  function toggleService(key) {
    setSelectedServices((p) => {
      const next = p.includes(key) ? p.filter((x) => x !== key) : [...p, key];
      if (!next.includes(key)) setRates((r) => { const { [key]: _, ...rest } = r; return rest; });
      return next;
    });
  }

  // Map the absolute step index to a logical phase, accounting for the skipped account step.
  const phase = alreadyCaretaker ? step + 1 : step; // 0 account,1 profile,2 experience,3 services

  function next() {
    setError('');
    if (phase === 0 && (!account.name || !account.email || account.password.length < 6))
      return setError('Enter your name, email, and a password of at least 6 characters.');
    if (phase === 1 && (!profile.location || !profile.bio))
      return setError('Please add your location and a short bio.');
    if (phase === 2 && animalTypes.length === 0)
      return setError('Select at least one animal type you care for.');
    if (phase === 2 && !resume)
      return setError('Please upload your resume or qualifications to continue.');
    setStep((s) => s + 1);
  }
  function back() {
    setError('');
    setStep((s) => Math.max(alreadyCaretaker ? 1 : 0, s - 1));
  }

  async function finish() {
    setError('');
    if (selectedServices.length === 0) return setError('Offer at least one service.');
    const missing = selectedServices.filter((s) => !(Number(rates[s]) > 0));
    if (missing.length) return setError('Set a rate for every service you offer.');

    if (!resume) return setError('Please upload your resume or qualifications.');

    setSaving(true);
    try {
      // Create the account if needed.
      if (!alreadyCaretaker) {
        const u = await signup({
          name: account.name, email: account.email, password: account.password, role: 'caretaker',
          location: profile.location, bio: profile.bio, photo_url: profile.photo_url,
        });
        setUser(u);
      }
      // Save the caretaker profile.
      const cleanRates = Object.fromEntries(selectedServices.map((s) => [s, Number(rates[s])]));
      await api.put('/caretakers/me/profile', {
        location: profile.location, bio: profile.bio, photo_url: profile.photo_url, headline: profile.headline,
        experience_years: Number(experience), animal_types: animalTypes,
        services: selectedServices, rates: cleanRates,
        availability_notes: availabilityNotes,
        resume_name: resume.name, resume_data: resume.dataUrl,
      });
      navigate('/dashboard');
    } catch (e) {
      setError(e.message);
    } finally {
      setSaving(false);
    }
  }

  return (
    <div className="bg-cream-200/40">
      <div className="container-rh max-w-3xl py-10">
        <div className="mb-2 text-center">
          <h1 className="font-display text-3xl font-700">Become a Ranch Hand caretaker</h1>
          <p className="mt-1 text-charcoal-muted">Set your own rates and schedule. It takes about two minutes.</p>
        </div>

        {/* Stepper */}
        <ol className="my-8 flex items-center justify-center gap-2 sm:gap-3">
          {STEP_LABELS.map((label, i) => {
            const absolute = alreadyCaretaker ? i + 1 : i;
            const active = absolute <= step;
            return (
              <li key={label} className="flex items-center gap-2">
                <span className={`flex h-8 w-8 items-center justify-center rounded-full text-sm font-700 ${active ? 'bg-barn text-cream-100' : 'bg-cream-200 text-charcoal-muted'}`}>{i + 1}</span>
                <span className={`hidden text-sm font-bold sm:inline ${active ? 'text-saddle-dark' : 'text-charcoal-muted'}`}>{label}</span>
                {i < STEP_LABELS.length - 1 && <span className="h-px w-6 bg-line" />}
              </li>
            );
          })}
        </ol>

        {error && <div className="mb-4 rounded-md border border-clay bg-clay/10 px-4 py-3 text-sm text-clay-dark">{error}</div>}

        <div className="card space-y-6">
          {/* Account */}
          {phase === 0 && (
            <>
              <h2 className="text-xl font-700">Create your account</h2>
              <Field label="Full name"><input className="input" value={account.name} onChange={(e) => setAccount({ ...account, name: e.target.value })} placeholder="Dale Whitaker" /></Field>
              <Field label="Email"><input type="email" className="input" value={account.email} onChange={(e) => setAccount({ ...account, email: e.target.value })} placeholder="you@example.com" /></Field>
              <Field label="Password" hint="At least 6 characters."><input type="password" className="input" value={account.password} onChange={(e) => setAccount({ ...account, password: e.target.value })} /></Field>
            </>
          )}

          {/* Profile */}
          {phase === 1 && (
            <>
              <h2 className="text-xl font-700">Your profile</h2>
              <Field label="Location"><input className="input" value={profile.location} onChange={(e) => setProfile({ ...profile, location: e.target.value })} placeholder="Bozeman, MT" /></Field>
              <Field label="Photo URL" hint="Optional — paste a link to a photo of yourself.">
                <input className="input" value={profile.photo_url} onChange={(e) => setProfile({ ...profile, photo_url: e.target.value })} placeholder="https://…" />
              </Field>
              <Field label="Headline" hint="One line owners see first.">
                <input className="input" value={profile.headline} onChange={(e) => setProfile({ ...profile, headline: e.target.value })} placeholder="Lifelong horseman — 18 years on working ranches." />
              </Field>
              <Field label="About you">
                <textarea rows="4" className="input" value={profile.bio} onChange={(e) => setProfile({ ...profile, bio: e.target.value })} placeholder="Tell owners about your experience and how you care for animals." />
              </Field>
            </>
          )}

          {/* Experience & animals */}
          {phase === 2 && (
            <>
              <h2 className="text-xl font-700">Experience &amp; animals</h2>
              <Field label={`Years of experience: ${experience}`}>
                <input type="range" min="0" max="30" value={experience} onChange={(e) => setExperience(e.target.value)} className="w-full accent-barn" />
              </Field>
              <div>
                <span className="label">Animals you care for</span>
                <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                  {ANIMAL_TYPES.map((a) => (
                    <label key={a} className={`flex cursor-pointer items-center gap-2 rounded-md border p-3 text-sm font-semibold ${animalTypes.includes(a) ? 'border-barn bg-barn/5' : 'border-line'}`}>
                      <input type="checkbox" checked={animalTypes.includes(a)} onChange={() => toggleAnimal(a)} className="accent-barn" />
                      <span aria-hidden="true">{ANIMAL_ICONS[a]}</span> {a}
                    </label>
                  ))}
                </div>
              </div>

              {/* Required resume / qualifications upload */}
              <div>
                <span className="label">
                  Resume / qualifications <span className="text-barn" aria-hidden="true">*</span>
                </span>
                {!resume ? (
                  <label
                    className="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-md border-2 border-dashed border-line bg-cream-100 px-4 py-7 text-center transition-colors hover:border-saddle-light focus-within:border-barn"
                  >
                    <input
                      type="file"
                      accept=".pdf,.doc,.docx,.png,.jpg,.jpeg"
                      className="sr-only"
                      onChange={(e) => handleResume(e.target.files?.[0])}
                      aria-label="Upload resume or qualifications"
                      required
                    />
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className="text-saddle" aria-hidden="true">
                      <path d="M12 16V4M12 4l-4 4M12 4l4 4" strokeLinecap="round" strokeLinejoin="round" />
                      <path d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2" strokeLinecap="round" />
                    </svg>
                    <span className="font-bold text-saddle-dark">Click to upload your resume</span>
                    <span className="text-xs text-charcoal-muted">PDF, Word, or image — up to 5 MB</span>
                  </label>
                ) : (
                  <div className="flex items-center gap-3 rounded-md border border-line bg-cream-100 p-3">
                    <span className="flex h-10 w-10 items-center justify-center rounded-md bg-sage/20 text-lg" aria-hidden="true">📄</span>
                    <div className="min-w-0 flex-1">
                      <p className="truncate font-bold text-saddle-dark" title={resume.name}>{resume.name}</p>
                      <p className="text-xs text-charcoal-muted">{prettySize(resume.size)} · ready to upload</p>
                    </div>
                    <button type="button" onClick={() => setResume(null)} className="btn-ghost px-3 py-1.5 text-sm">
                      Replace
                    </button>
                  </div>
                )}
                <p className="mt-1.5 text-xs text-charcoal-muted">
                  Required — share certifications, references, or work history so owners can trust your experience.
                </p>
              </div>

              <Field label="Additional Notes" hint="Optional">
                <textarea
                  rows="3"
                  className="input"
                  value={availabilityNotes}
                  onChange={(e) => setAvailabilityNotes(e.target.value)}
                  placeholder="e.g. Available most days; overnights preferred. Book 3+ days ahead."
                />
              </Field>
            </>
          )}

          {/* Services & rates */}
          {phase === 3 && (
            <>
              <h2 className="text-xl font-700">Services &amp; rates</h2>
              <p className="text-sm text-charcoal-muted">Pick the services you offer and set your price. The platform keeps a 15% commission; owners pay a 7% service fee on top.</p>
              <div className="space-y-2">
                {SERVICES.map((s) => {
                  const on = selectedServices.includes(s.key);
                  return (
                    <div key={s.key} className={`flex items-center justify-between gap-3 rounded-md border p-3 ${on ? 'border-barn bg-barn/5' : 'border-line'}`}>
                      <label className="flex cursor-pointer items-center gap-2 text-sm font-semibold">
                        <input type="checkbox" checked={on} onChange={() => toggleService(s.key)} className="accent-barn" />
                        <span>{s.label}<span className="block text-xs font-normal text-charcoal-muted">{SERVICE_UNITS[s.key]}</span></span>
                      </label>
                      {on && (
                        <div className="flex items-center gap-1">
                          <span className="text-charcoal-muted">$</span>
                          <input
                            type="number" min="1" className="input w-24 py-1.5" value={rates[s.key] || ''}
                            onChange={(e) => setRates({ ...rates, [s.key]: e.target.value })} aria-label={`Rate for ${s.label}`}
                          />
                        </div>
                      )}
                    </div>
                  );
                })}
              </div>
            </>
          )}

          {/* Nav buttons */}
          <div className="flex items-center justify-between pt-2">
            {step > (alreadyCaretaker ? 1 : 0) ? (
              <button onClick={back} className="btn-ghost">← Back</button>
            ) : <span />}
            {phase < 3 ? (
              <button onClick={next} className="btn-primary">Continue</button>
            ) : (
              <button onClick={finish} disabled={saving} className="btn-primary">{saving ? 'Saving…' : 'Finish & publish'}</button>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

function Field({ label, hint, children }) {
  return (
    <label className="block">
      <span className="label">{label}{hint && <span className="ml-1 font-normal text-charcoal-muted">— {hint}</span>}</span>
      {children}
    </label>
  );
}
