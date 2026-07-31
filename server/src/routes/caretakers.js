// Caretaker routes: search/list, single profile (+reviews), and become/update profile.
const express = require('express');
const db = require('../db');
const { authRequired } = require('../auth');
const { caretaker, reviewWithAuthor } = require('../serializers');

const router = express.Router();

// Note: list query includes resume_name (lightweight flag) but NOT resume_data (the
// large base64 blob) — that's only fetched on the single-profile route.
const listStmt = db.prepare(
  `SELECT u.*, p.experience_years, p.animal_types, p.services, p.rates, p.availability, p.availability_notes, p.headline, p.resume_name
     FROM users u
     JOIN caretaker_profiles p ON p.user_id = u.id
    WHERE u.role = 'caretaker'`
);

// GET /api/caretakers — list + filter caretakers.
// Query params: animal, service, minPrice, maxPrice, minRating, q (text), sort
router.get('/', (req, res) => {
  const { animal, service, minPrice, maxPrice, minRating, q, sort } = req.query;
  let list = listStmt.all().map((row) => caretaker(row, row));

  if (animal) list = list.filter((c) => c.animal_types.includes(animal));
  if (service) list = list.filter((c) => c.services.includes(service));
  if (minPrice) list = list.filter((c) => c.min_price != null && c.min_price >= Number(minPrice));
  if (maxPrice) list = list.filter((c) => c.min_price != null && c.min_price <= Number(maxPrice));
  if (minRating) list = list.filter((c) => (c.rating || 0) >= Number(minRating));
  if (q) {
    const needle = String(q).toLowerCase();
    list = list.filter(
      (c) =>
        c.name.toLowerCase().includes(needle) ||
        (c.location || '').toLowerCase().includes(needle) ||
        (c.bio || '').toLowerCase().includes(needle) ||
        (c.headline || '').toLowerCase().includes(needle)
    );
  }

  switch (sort) {
    case 'price_asc':  list.sort((a, b) => (a.min_price ?? 1e9) - (b.min_price ?? 1e9)); break;
    case 'price_desc': list.sort((a, b) => (b.min_price ?? -1) - (a.min_price ?? -1)); break;
    case 'experience': list.sort((a, b) => b.experience_years - a.experience_years); break;
    default:           list.sort((a, b) => (b.rating ?? 0) - (a.rating ?? 0)); // rating, default
  }

  res.json({ caretakers: list, total: list.length });
});

// GET /api/caretakers/:id — full profile incl. reviews.
router.get('/:id', (req, res) => {
  const row = db
    .prepare(
      `SELECT u.*, p.experience_years, p.animal_types, p.services, p.rates, p.availability, p.availability_notes, p.headline,
              p.resume_name, p.resume_data
         FROM users u JOIN caretaker_profiles p ON p.user_id = u.id
        WHERE u.id = ? AND u.role = 'caretaker'`
    )
    .get(req.params.id);
  if (!row) return res.status(404).json({ error: 'Caretaker not found.' });

  const reviews = db
    .prepare(
      `SELECT r.*, u.name AS owner_name, u.photo_url AS owner_photo
         FROM reviews r JOIN users u ON u.id = r.owner_id
        WHERE r.caretaker_id = ? ORDER BY r.created_at DESC`
    )
    .all(req.params.id)
    .map(reviewWithAuthor);

  // Attach the resume separately (only on the single-profile endpoint).
  const resume = row.resume_name ? { name: row.resume_name, data: row.resume_data } : null;
  res.json({ caretaker: caretaker(row, row), reviews, resume });
});

// PUT /api/caretakers/me — create/update the logged-in caretaker's profile (onboarding).
router.put('/me/profile', authRequired, (req, res) => {
  if (req.user.role !== 'caretaker') {
    return res.status(403).json({ error: 'Only caretaker accounts have a caretaker profile.' });
  }
  const {
    location, bio, photo_url, headline,
    experience_years, animal_types, services, rates, availability, availability_notes,
    resume_name, resume_data,
  } = req.body || {};

  // availability arrives as a schedule object — store it as a JSON string (matches
  // animal_types/services). A plain string (legacy) is stored as-is.
  const availabilityValue =
    availability == null ? null : typeof availability === 'string' ? availability : JSON.stringify(availability);

  // Update user-level fields when provided.
  db.prepare(
    `UPDATE users SET location = COALESCE(?, location), bio = COALESCE(?, bio),
            photo_url = COALESCE(?, photo_url) WHERE id = ?`
  ).run(location ?? null, bio ?? null, photo_url ?? null, req.user.id);

  // Ensure a profile row exists, then update it.
  db.prepare('INSERT OR IGNORE INTO caretaker_profiles (user_id) VALUES (?)').run(req.user.id);
  db.prepare(
    `UPDATE caretaker_profiles
        SET experience_years = COALESCE(?, experience_years),
            animal_types     = COALESCE(?, animal_types),
            services         = COALESCE(?, services),
            rates            = COALESCE(?, rates),
            availability       = COALESCE(?, availability),
            availability_notes = COALESCE(?, availability_notes),
            headline           = COALESCE(?, headline),
            resume_name        = COALESCE(?, resume_name),
            resume_data        = COALESCE(?, resume_data)
      WHERE user_id = ?`
  ).run(
    experience_years ?? null,
    animal_types ? JSON.stringify(animal_types) : null,
    services ? JSON.stringify(services) : null,
    rates ? JSON.stringify(rates) : null,
    availabilityValue,
    availability_notes ?? null,
    headline ?? null,
    resume_name ?? null,
    resume_data ?? null,
    req.user.id
  );

  const row = db
    .prepare(
      `SELECT u.*, p.experience_years, p.animal_types, p.services, p.rates, p.availability, p.availability_notes, p.headline, p.resume_name
         FROM users u JOIN caretaker_profiles p ON p.user_id = u.id WHERE u.id = ?`
    )
    .get(req.user.id);
  res.json({ caretaker: caretaker(row, row) });
});

module.exports = router;
