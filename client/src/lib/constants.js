// Client-side mirror of domain constants + display helpers.
// (Service/animal lists also come from /api/meta at runtime; these are fallbacks + icons.)

export const SERVICES = [
  { key: 'barn_sitting', label: 'Farm/Barn Sitting', unit: 'per visit', blurb: 'Daily visits while you are away.' },
  { key: 'overnight', label: 'Overnight Care', unit: 'per night', blurb: 'Caretaker stays overnight on-site.' },
  { key: 'drop_in', label: 'Drop-in Feeding & Watering', unit: 'per visit', blurb: 'Quick feed, water, and welfare check.' },
  { key: 'turnout', label: 'Turnout / Exercise', unit: 'per visit', blurb: 'Turnout, lunging, and exercise.' },
  { key: 'mucking', label: 'Mucking & Stall Cleaning', unit: 'per stall', blurb: 'Muck out and refresh bedding.' },
  { key: 'medication', label: 'Medication Administration', unit: 'per visit', blurb: 'Administer meds per your vet plan.' },
];

export const SERVICE_LABELS = Object.fromEntries(SERVICES.map((s) => [s.key, s.label]));
export const SERVICE_UNITS = Object.fromEntries(SERVICES.map((s) => [s.key, s.unit]));

export const ANIMAL_TYPES = ['Horses', 'Cattle', 'Goats/Sheep', 'Pigs', 'Poultry', 'Mixed Farm'];

// Emoji icons used as lightweight, dependency-free imagery for animal categories.
export const ANIMAL_ICONS = {
  Horses: '🐴',
  Cattle: '🐄',
  'Goats/Sheep': '🐐',
  Pigs: '🐖',
  Poultry: '🐓',
  'Mixed Farm': '🚜',
};

export const ANIMAL_BLURBS = {
  Horses: 'Trail horses, show horses, seniors & green stock.',
  Cattle: 'Beef & dairy herds, feeding rotations, water checks.',
  'Goats/Sheep': 'Small ruminants, milking, kidding & lambing.',
  Pigs: 'Pot-bellies to pasture pigs, feeding & wallows.',
  Poultry: 'Chickens, ducks & turkeys; eggs & coop care.',
  'Mixed Farm': 'A little of everything, whole-farm sitting.',
};

export const FEES = { caretaker_commission: 0.15, owner_service_fee: 0.07 };

export const money = (n) =>
  (Number(n) || 0).toLocaleString('en-US', { style: 'currency', currency: 'USD' });

export const formatDate = (s) => {
  if (!s) return '';
  const d = new Date(s + (s.length === 10 ? 'T00:00:00' : ''));
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

// --- Weekly availability -----------------------------------------------------
export const DAYS = [
  { key: 'Mon', label: 'Monday' },
  { key: 'Tue', label: 'Tuesday' },
  { key: 'Wed', label: 'Wednesday' },
  { key: 'Thu', label: 'Thursday' },
  { key: 'Fri', label: 'Friday' },
  { key: 'Sat', label: 'Saturday' },
  { key: 'Sun', label: 'Sunday' },
];

// Format an hour index (0–24) as "8:00 AM" / "12:00 PM" / "12:00 AM" (midnight).
function formatHour(h) {
  const ampm = h < 12 || h === 24 ? 'AM' : 'PM';
  let hr = h % 12;
  if (hr === 0) hr = 12;
  return `${hr}:00 ${ampm}`;
}

// Hourly options 12:00 AM … 12:00 AM(next day). Value is "HH:00" (24:00 = midnight end).
export const TIME_OPTIONS = Array.from({ length: 25 }, (_, h) => ({
  value: `${String(h).padStart(2, '0')}:00`,
  label: formatHour(h),
}));

// Format a stored "HH:MM" value (incl. "24:00") into a friendly label.
export function formatTimeValue(v) {
  if (!v) return '';
  const [hh, mm] = v.split(':').map(Number);
  const ampm = hh < 12 || hh === 24 ? 'AM' : 'PM';
  let hr = hh % 12;
  if (hr === 0) hr = 12;
  return `${hr}:${String(mm).padStart(2, '0')} ${ampm}`;
}

export const STATUS_STYLES = {
  pending: 'bg-clay/20 text-clay-dark',
  confirmed: 'bg-sage/25 text-sage-dark',
  completed: 'bg-saddle/15 text-saddle-dark',
  cancelled: 'bg-charcoal/10 text-charcoal-muted',
};
