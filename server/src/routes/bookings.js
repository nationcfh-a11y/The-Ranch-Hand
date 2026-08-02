// Booking routes: quote (fee preview), create, list mine, update status.
const express = require('express');
const db = require('../db');
const { authRequired } = require('../auth');
const { computeFees, SERVICE_LABELS } = require('../constants');
const { safeParse } = require('../serializers');

const router = express.Router();

// Resolve the base price for a booking = caretaker's rate for the service * quantity.
function priceFor(caretakerId, serviceKey, quantity) {
  const profile = db.prepare('SELECT rates FROM caretaker_profiles WHERE user_id = ?').get(caretakerId);
  const rates = safeParse(profile?.rates, {});
  const rate = Number(rates[serviceKey]) || 0;
  return { rate, base: rate * Math.max(1, Number(quantity) || 1) };
}

// POST /api/bookings/quote: preview the fee breakdown without saving. (Auth optional.)
router.post('/quote', (req, res) => {
  const { caretaker_id, service, quantity = 1 } = req.body || {};
  if (!caretaker_id || !service) return res.status(400).json({ error: 'caretaker_id and service are required.' });
  const { rate, base } = priceFor(caretaker_id, service, quantity);
  if (!rate) return res.status(400).json({ error: 'This caretaker does not offer that service.' });
  res.json({ rate, quantity: Number(quantity) || 1, service_label: SERVICE_LABELS[service] || service, ...computeFees(base) });
});

// POST /api/bookings: create a booking (simulated payment: status starts "pending").
router.post('/', authRequired, (req, res) => {
  if (req.user.role !== 'owner') {
    return res.status(403).json({ error: 'Only owner accounts can request bookings.' });
  }
  const { caretaker_id, service, animals = [], start_date, end_date, instructions = '', quantity = 1 } = req.body || {};
  if (!caretaker_id || !service || !start_date || !end_date) {
    return res.status(400).json({ error: 'caretaker_id, service, start_date, and end_date are required.' });
  }
  const { rate, base } = priceFor(caretaker_id, service, quantity);
  if (!rate) return res.status(400).json({ error: 'This caretaker does not offer that service.' });

  const fees = computeFees(base);
  const info = db
    .prepare(
      `INSERT INTO bookings
        (owner_id, caretaker_id, service, animals, start_date, end_date, instructions, status,
         base_price, owner_fee, caretaker_fee, total_price)
       VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?)`
    )
    .run(
      req.user.id, caretaker_id, service, JSON.stringify(animals), start_date, end_date, instructions,
      fees.base, fees.ownerFee, fees.caretakerFee, fees.total
    );

  res.status(201).json({ booking: getBooking(info.lastInsertRowid) });
});

// GET /api/bookings/mine: bookings for the current user (as owner or caretaker).
router.get('/mine', authRequired, (req, res) => {
  const col = req.user.role === 'caretaker' ? 'caretaker_id' : 'owner_id';
  const rows = db
    .prepare(
      `SELECT b.*, ow.name AS owner_name, ow.photo_url AS owner_photo,
              ct.name AS caretaker_name, ct.photo_url AS caretaker_photo
         FROM bookings b
         JOIN users ow ON ow.id = b.owner_id
         JOIN users ct ON ct.id = b.caretaker_id
        WHERE b.${col} = ? ORDER BY b.start_date DESC`
    )
    .all(req.user.id)
    .map(shapeBooking);
  res.json({ bookings: rows });
});

// PATCH /api/bookings/:id/status: caretaker confirms/completes; either party cancels.
router.patch('/:id/status', authRequired, (req, res) => {
  const { status } = req.body || {};
  const allowed = ['pending', 'confirmed', 'completed', 'cancelled'];
  if (!allowed.includes(status)) return res.status(400).json({ error: 'Invalid status.' });

  const booking = db.prepare('SELECT * FROM bookings WHERE id = ?').get(req.params.id);
  if (!booking) return res.status(404).json({ error: 'Booking not found.' });
  const isParty = booking.owner_id === req.user.id || booking.caretaker_id === req.user.id;
  if (!isParty) return res.status(403).json({ error: 'Not your booking.' });

  db.prepare('UPDATE bookings SET status = ? WHERE id = ?').run(status, req.params.id);
  res.json({ booking: getBooking(req.params.id) });
});

function getBooking(id) {
  const row = db
    .prepare(
      `SELECT b.*, ow.name AS owner_name, ow.photo_url AS owner_photo,
              ct.name AS caretaker_name, ct.photo_url AS caretaker_photo
         FROM bookings b
         JOIN users ow ON ow.id = b.owner_id
         JOIN users ct ON ct.id = b.caretaker_id
        WHERE b.id = ?`
    )
    .get(id);
  return row ? shapeBooking(row) : null;
}

function shapeBooking(row) {
  return {
    id: row.id,
    owner_id: row.owner_id,
    caretaker_id: row.caretaker_id,
    owner_name: row.owner_name,
    owner_photo: row.owner_photo,
    caretaker_name: row.caretaker_name,
    caretaker_photo: row.caretaker_photo,
    service: row.service,
    service_label: SERVICE_LABELS[row.service] || row.service,
    animals: safeParse(row.animals, []),
    start_date: row.start_date,
    end_date: row.end_date,
    instructions: row.instructions,
    status: row.status,
    base_price: row.base_price,
    owner_fee: row.owner_fee,
    caretaker_fee: row.caretaker_fee,
    total_price: row.total_price,
    created_at: row.created_at,
  };
}

module.exports = router;
