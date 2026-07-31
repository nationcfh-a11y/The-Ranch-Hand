// Weekly availability picker (Mon–Sun). Each day can be toggled on/off and given a
// from/to time range. Value is an object keyed by day; only AVAILABLE days are present:
//   { Mon: { from: '08:00', to: '17:00' }, Wed: { from: '09:00', to: '16:00' } }
import { DAYS, TIME_OPTIONS, formatTimeValue } from '../lib/constants';

const DEFAULT_RANGE = { from: '08:00', to: '17:00' };

export default function WeeklyAvailability({ value = {}, onChange }) {
  function setDay(key, patch) {
    const current = value[key] || DEFAULT_RANGE;
    const next = { ...value };
    if (patch.enabled === false) {
      delete next[key];
    } else if (patch.enabled === true) {
      next[key] = { from: current.from || '08:00', to: current.to || '17:00' };
    } else {
      next[key] = { ...current, ...patch };
    }
    onChange(next);
  }

  // Quick presets for convenience.
  function applyPreset(days, from, to) {
    const next = {};
    days.forEach((d) => (next[d] = { from, to }));
    onChange(next);
  }

  return (
    <div>
      <div className="mb-2 flex flex-wrap gap-2">
        <button type="button" onClick={() => applyPreset(['Mon', 'Tue', 'Wed', 'Thu', 'Fri'], '09:00', '17:00')} className="chip text-xs hover:bg-cream-200">
          Weekdays 9–5
        </button>
        <button type="button" onClick={() => applyPreset(DAYS.map((d) => d.key), '08:00', '18:00')} className="chip text-xs hover:bg-cream-200">
          Every day 8–6
        </button>
        <button type="button" onClick={() => onChange({})} className="chip text-xs hover:bg-cream-200">
          Clear all
        </button>
      </div>

      <div className="divide-y divide-line overflow-hidden rounded-md border border-line">
        {DAYS.map((d) => {
          const day = value[d.key];
          const enabled = !!day;
          return (
            <div key={d.key} className="flex flex-col gap-2 p-3 sm:flex-row sm:items-center">
              <label className="flex w-full items-center gap-2 font-semibold text-saddle-dark sm:w-36">
                <input
                  type="checkbox"
                  checked={enabled}
                  onChange={(e) => setDay(d.key, { enabled: e.target.checked })}
                  className="accent-barn"
                />
                {d.label}
              </label>

              {enabled ? (
                <div className="flex flex-1 items-center gap-2">
                  <select
                    value={day.from}
                    onChange={(e) => setDay(d.key, { from: e.target.value })}
                    className="input w-32 py-1.5"
                    aria-label={`${d.label} start time`}
                  >
                    {TIME_OPTIONS.slice(0, 24).map((t) => (
                      <option key={t.value} value={t.value}>{t.label}</option>
                    ))}
                  </select>
                  <span className="text-charcoal-muted">to</span>
                  <select
                    value={day.to}
                    onChange={(e) => setDay(d.key, { to: e.target.value })}
                    className="input w-32 py-1.5"
                    aria-label={`${d.label} end time`}
                  >
                    {TIME_OPTIONS.slice(1).map((t) => (
                      <option key={t.value} value={t.value}>{t.label}</option>
                    ))}
                  </select>
                </div>
              ) : (
                <span className="flex-1 text-sm text-charcoal-muted">Unavailable</span>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
}

// Read-only renderer for the public profile.
export function ScheduleList({ schedule }) {
  if (!schedule || Object.keys(schedule).length === 0) {
    return <p className="mt-1 text-sm text-charcoal-muted">Contact for availability.</p>;
  }
  return (
    <ul className="mt-1 space-y-1 text-sm">
      {DAYS.map((d) => {
        const s = schedule[d.key];
        return (
          <li key={d.key} className="flex justify-between gap-3">
            <span className="font-semibold text-saddle-dark">{d.label}</span>
            <span className={s ? 'text-charcoal' : 'text-charcoal-muted'}>
              {s ? `${formatTimeValue(s.from)} – ${formatTimeValue(s.to)}` : 'Unavailable'}
            </span>
          </li>
        );
      })}
    </ul>
  );
}
