// Message routes: simple direct messaging between owners and caretakers.
const express = require('express');
const db = require('../db');
const { authRequired } = require('../auth');

const router = express.Router();

// GET /api/messages/threads: one entry per conversation partner, with last message.
router.get('/threads', authRequired, (req, res) => {
  const rows = db
    .prepare(
      `SELECT m.*,
              su.name AS sender_name, ru.name AS receiver_name,
              su.photo_url AS sender_photo, ru.photo_url AS receiver_photo
         FROM messages m
         JOIN users su ON su.id = m.sender_id
         JOIN users ru ON ru.id = m.receiver_id
        WHERE m.sender_id = ? OR m.receiver_id = ?
        ORDER BY m.created_at ASC`
    )
    .all(req.user.id, req.user.id);

  // Collapse into threads keyed by the "other" participant.
  const threads = new Map();
  for (const m of rows) {
    const otherId = m.sender_id === req.user.id ? m.receiver_id : m.sender_id;
    const otherName = m.sender_id === req.user.id ? m.receiver_name : m.sender_name;
    const otherPhoto = m.sender_id === req.user.id ? m.receiver_photo : m.sender_photo;
    threads.set(otherId, {
      partner_id: otherId,
      partner_name: otherName,
      partner_photo: otherPhoto,
      last_message: m.body,
      last_at: m.created_at,
    });
  }
  res.json({ threads: [...threads.values()].sort((a, b) => (a.last_at < b.last_at ? 1 : -1)) });
});

// GET /api/messages/with/:userId: full conversation with one partner.
router.get('/with/:userId', authRequired, (req, res) => {
  const rows = db
    .prepare(
      `SELECT * FROM messages
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at ASC`
    )
    .all(req.user.id, req.params.userId, req.params.userId, req.user.id);
  res.json({ messages: rows });
});

// POST /api/messages: send a message.
router.post('/', authRequired, (req, res) => {
  const { receiver_id, body, booking_id = null } = req.body || {};
  if (!receiver_id || !body) return res.status(400).json({ error: 'receiver_id and body are required.' });
  const info = db
    .prepare('INSERT INTO messages (sender_id, receiver_id, booking_id, body) VALUES (?, ?, ?, ?)')
    .run(req.user.id, receiver_id, booking_id, body);
  res.status(201).json({ message: db.prepare('SELECT * FROM messages WHERE id = ?').get(info.lastInsertRowid) });
});

module.exports = router;
