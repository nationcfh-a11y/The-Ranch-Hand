// Auth routes: signup, login, and "who am I".
const express = require('express');
const db = require('../db');
const { hashPassword, verifyPassword, signToken, authRequired } = require('../auth');
const { publicUser } = require('../serializers');
const { mirrorSignup } = require('../sheets');

const router = express.Router();

// POST /api/auth/signup: create an owner or caretaker account.
router.post('/signup', (req, res) => {
  const { name, email, password, role, location = '', bio = '', photo_url = '', search_radius } = req.body || {};
  if (!name || !email || !password || !role) {
    return res.status(400).json({ error: 'Name, email, password, and role are required.' });
  }
  if (!['owner', 'caretaker'].includes(role)) {
    return res.status(400).json({ error: 'Role must be "owner" or "caretaker".' });
  }
  const existing = db.prepare('SELECT id FROM users WHERE email = ?').get(email.toLowerCase());
  if (existing) return res.status(409).json({ error: 'An account with that email already exists.' });

  // Clamp the radius to a sane range; default 20 miles.
  const radius = Math.min(500, Math.max(1, Number(search_radius) || 20));

  const info = db
    .prepare(
      `INSERT INTO users (name, email, password_hash, role, location, bio, photo_url, search_radius)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?)`
    )
    .run(name.trim(), email.toLowerCase().trim(), hashPassword(password), role, location, bio, photo_url, radius);

  // Caretakers always get a (possibly empty) profile row created.
  if (role === 'caretaker') {
    db.prepare('INSERT INTO caretaker_profiles (user_id) VALUES (?)').run(info.lastInsertRowid);
  }

  const user = db.prepare('SELECT * FROM users WHERE id = ?').get(info.lastInsertRowid);
  res.status(201).json({ token: signToken(user), user: publicUser(user) });

  // Mirror the new signup into the Google Sheet CRM view. Deliberately fired
  // after the response and never awaited: SQLite is the source of truth, so a
  // Sheets outage must not slow down or fail the signup.
  mirrorSignup(user);
});

// POST /api/auth/login
router.post('/login', (req, res) => {
  const { email, password } = req.body || {};
  if (!email || !password) return res.status(400).json({ error: 'Email and password are required.' });

  const user = db.prepare('SELECT * FROM users WHERE email = ?').get(String(email).toLowerCase().trim());
  if (!user || !verifyPassword(password, user.password_hash)) {
    return res.status(401).json({ error: 'Incorrect email or password.' });
  }
  return res.json({ token: signToken(user), user: publicUser(user) });
});

// GET /api/auth/me: current user from token.
router.get('/me', authRequired, (req, res) => {
  const user = db.prepare('SELECT * FROM users WHERE id = ?').get(req.user.id);
  if (!user) return res.status(404).json({ error: 'User not found.' });
  return res.json({ user: publicUser(user) });
});

module.exports = router;
