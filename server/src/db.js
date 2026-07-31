// SQLite connection + schema bootstrap.
// File-based DB (better-sqlite3) so the app runs with zero external setup.
const path = require('path');
const Database = require('better-sqlite3');

const DB_PATH = path.join(__dirname, '..', 'ranchhand.db');
const db = new Database(DB_PATH);

// Pragmas for sane local behavior.
db.pragma('journal_mode = WAL');
db.pragma('foreign_keys = ON');

// --- Schema -----------------------------------------------------------------
// JSON-shaped fields (animal_types, services, rates, animals) are stored as TEXT
// holding JSON strings; the API layer parses/stringifies at the boundary.
db.exec(`
CREATE TABLE IF NOT EXISTS users (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  name          TEXT    NOT NULL,
  email         TEXT    NOT NULL UNIQUE,
  password_hash TEXT    NOT NULL,
  role          TEXT    NOT NULL CHECK (role IN ('owner','caretaker')),
  location      TEXT    DEFAULT '',
  bio           TEXT    DEFAULT '',
  photo_url     TEXT    DEFAULT '',
  created_at    TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS caretaker_profiles (
  id               INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id          INTEGER NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
  experience_years INTEGER NOT NULL DEFAULT 0,
  animal_types     TEXT    NOT NULL DEFAULT '[]', -- JSON array
  services         TEXT    NOT NULL DEFAULT '[]', -- JSON array of service keys
  rates            TEXT    NOT NULL DEFAULT '{}', -- JSON map serviceKey -> price
  availability     TEXT    NOT NULL DEFAULT 'Available most days',
  headline         TEXT    DEFAULT ''
);

CREATE TABLE IF NOT EXISTS bookings (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  owner_id      INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  caretaker_id  INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  service       TEXT    NOT NULL,
  animals       TEXT    NOT NULL DEFAULT '[]', -- JSON array of {type, count}
  start_date    TEXT    NOT NULL,
  end_date      TEXT    NOT NULL,
  instructions  TEXT    DEFAULT '',
  status        TEXT    NOT NULL DEFAULT 'pending'
                  CHECK (status IN ('pending','confirmed','completed','cancelled')),
  base_price    REAL    NOT NULL DEFAULT 0,
  owner_fee     REAL    NOT NULL DEFAULT 0,
  caretaker_fee REAL    NOT NULL DEFAULT 0,
  total_price   REAL    NOT NULL DEFAULT 0,
  created_at    TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS reviews (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  booking_id INTEGER REFERENCES bookings(id) ON DELETE SET NULL,
  owner_id   INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  caretaker_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  rating     INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment    TEXT    DEFAULT '',
  created_at TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS messages (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  sender_id   INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  receiver_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  booking_id  INTEGER REFERENCES bookings(id) ON DELETE SET NULL,
  body        TEXT    NOT NULL,
  created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);
`);

// --- Lightweight migrations -------------------------------------------------
// Add columns introduced after the initial release, without dropping data.
// search_radius = the user's preferred search distance in miles (Facebook-Marketplace style).
const userColumns = db.prepare('PRAGMA table_info(users)').all().map((c) => c.name);
if (!userColumns.includes('search_radius')) {
  db.exec('ALTER TABLE users ADD COLUMN search_radius INTEGER NOT NULL DEFAULT 20');
}

// resume_name / resume_data = caretaker's uploaded qualifications doc.
// Stored as a data URL (base64) so the demo needs no file storage or static server.
const profileColumns = db.prepare('PRAGMA table_info(caretaker_profiles)').all().map((c) => c.name);
if (!profileColumns.includes('resume_name')) {
  db.exec("ALTER TABLE caretaker_profiles ADD COLUMN resume_name TEXT DEFAULT ''");
}
if (!profileColumns.includes('resume_data')) {
  db.exec("ALTER TABLE caretaker_profiles ADD COLUMN resume_data TEXT DEFAULT ''");
}
// availability is now a JSON schedule string; availability_notes holds the optional free text.
if (!profileColumns.includes('availability_notes')) {
  db.exec("ALTER TABLE caretaker_profiles ADD COLUMN availability_notes TEXT DEFAULT ''");
}

module.exports = db;
