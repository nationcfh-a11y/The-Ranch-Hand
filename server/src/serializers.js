// Helpers that assemble DB rows into the JSON shapes the client expects.
const db = require('./db');

function safeParse(json, fallback) {
  try { return JSON.parse(json); } catch { return fallback; }
}

// Interpret the availability column: a JSON schedule object → { availability: {...} };
// a legacy plain string → { availability: null, availability_text: "<string>" }.
function parseAvailability(raw) {
  if (!raw) return { availability: null, availability_text: '' };
  try {
    const v = JSON.parse(raw);
    if (v && typeof v === 'object' && !Array.isArray(v)) {
      return { availability: v, availability_text: '' };
    }
  } catch { /* not JSON — fall through to legacy text */ }
  return { availability: null, availability_text: String(raw) };
}

// Public user shape (never leaks password_hash).
function publicUser(row) {
  if (!row) return null;
  return {
    id: row.id,
    name: row.name,
    email: row.email,
    role: row.role,
    location: row.location,
    bio: row.bio,
    photo_url: row.photo_url,
    search_radius: row.search_radius ?? 20,
  };
}

// Prepared aggregate for a caretaker's reviews.
const ratingStmt = db.prepare(
  `SELECT COUNT(*) AS count, AVG(rating) AS avg FROM reviews WHERE caretaker_id = ?`
);

// Full caretaker shape = user + profile + rating aggregate.
function caretaker(userRow, profileRow) {
  const agg = ratingStmt.get(userRow.id);
  const rates = safeParse(profileRow?.rates, {});
  const priceValues = Object.values(rates).map(Number).filter((n) => n > 0);
  return {
    id: userRow.id,
    name: userRow.name,
    location: userRow.location,
    bio: userRow.bio,
    photo_url: userRow.photo_url,
    headline: profileRow?.headline || '',
    experience_years: profileRow?.experience_years ?? 0,
    animal_types: safeParse(profileRow?.animal_types, []),
    services: safeParse(profileRow?.services, []),
    rates,
    // availability: structured weekly schedule object, or null. Legacy free-text
    // (pre-schedule profiles) is surfaced as availability_text for graceful fallback.
    ...parseAvailability(profileRow?.availability),
    availability_notes: profileRow?.availability_notes || '',
    rating: agg.avg ? Math.round(agg.avg * 10) / 10 : null,
    review_count: agg.count,
    min_price: priceValues.length ? Math.min(...priceValues) : null,
    has_resume: !!profileRow?.resume_name,
  };
}

function reviewWithAuthor(row) {
  return {
    id: row.id,
    booking_id: row.booking_id,
    rating: row.rating,
    comment: row.comment,
    created_at: row.created_at,
    owner_name: row.owner_name,
    owner_photo: row.owner_photo,
  };
}

module.exports = { safeParse, publicUser, caretaker, reviewWithAuthor };
