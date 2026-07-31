// Review routes: owners leave a review for a completed booking.
const express = require('express');
const db = require('../db');
const { authRequired } = require('../auth');

const router = express.Router();

// POST /api/reviews — owner reviews a caretaker (optionally tied to a booking).
router.post('/', authRequired, (req, res) => {
  if (req.user.role !== 'owner') {
    return res.status(403).json({ error: 'Only owners can leave reviews.' });
  }
  const { caretaker_id, booking_id = null, rating, comment = '' } = req.body || {};
  if (!caretaker_id || !rating) return res.status(400).json({ error: 'caretaker_id and rating are required.' });
  if (rating < 1 || rating > 5) return res.status(400).json({ error: 'Rating must be 1–5.' });

  const info = db
    .prepare(
      `INSERT INTO reviews (booking_id, owner_id, caretaker_id, rating, comment)
       VALUES (?, ?, ?, ?, ?)`
    )
    .run(booking_id, req.user.id, caretaker_id, rating, comment);

  const row = db.prepare('SELECT * FROM reviews WHERE id = ?').get(info.lastInsertRowid);
  res.status(201).json({ review: row });
});

module.exports = router;
