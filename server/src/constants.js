// Shared domain constants: services, animal categories, and fee model.
// Kept in one place so the API and seed stay consistent (the client has its own copy).

// The six services modeled (Ranch Hand's equivalent of Rover's dog services).
const SERVICES = [
  { key: 'barn_sitting',   label: 'Farm/Barn Sitting',          unit: 'per visit',  blurb: 'Daily visits while you are away.' },
  { key: 'overnight',      label: 'Overnight Care',             unit: 'per night',  blurb: 'Caretaker stays overnight on-site.' },
  { key: 'drop_in',        label: 'Drop-in Feeding & Watering', unit: 'per visit',  blurb: 'Quick feed, water, and welfare check.' },
  { key: 'turnout',        label: 'Turnout / Exercise',         unit: 'per visit',  blurb: 'Turnout, lunging, and exercise.' },
  { key: 'mucking',        label: 'Mucking & Stall Cleaning',   unit: 'per stall',  blurb: 'Muck out and refresh bedding.' },
  { key: 'medication',     label: 'Medication Administration',  unit: 'per visit',  blurb: 'Administer meds per your vet plan.' },
];

// Animal categories shown across the marketplace.
const ANIMAL_TYPES = ['Horses', 'Cattle', 'Goats/Sheep', 'Pigs', 'Poultry', 'Mixed Farm'];

// Simulated marketplace fee model (mirrors Rover's structure).
const CARETAKER_COMMISSION_RATE = 0.15; // platform keeps 15% of caretaker's rate
const OWNER_SERVICE_FEE_RATE = 0.07;    // owner pays a 7% service fee on top

const SERVICE_LABELS = Object.fromEntries(SERVICES.map((s) => [s.key, s.label]));

// Compute the full fee breakdown for a given base price (rate * quantity).
// Returns rounded currency numbers the UI can display directly.
function computeFees(basePrice) {
  const base = Math.max(0, Number(basePrice) || 0);
  const ownerFee = round2(base * OWNER_SERVICE_FEE_RATE);
  const caretakerFee = round2(base * CARETAKER_COMMISSION_RATE);
  const total = round2(base + ownerFee);            // what the OWNER pays
  const caretakerPayout = round2(base - caretakerFee); // what the CARETAKER receives
  return { base: round2(base), ownerFee, caretakerFee, total, caretakerPayout };
}

function round2(n) {
  return Math.round((Number(n) + Number.EPSILON) * 100) / 100;
}

module.exports = {
  SERVICES,
  SERVICE_LABELS,
  ANIMAL_TYPES,
  CARETAKER_COMMISSION_RATE,
  OWNER_SERVICE_FEE_RATE,
  computeFees,
  round2,
};
