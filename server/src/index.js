// The Ranch Hand — Express REST API entry point.
const express = require('express');
const cors = require('cors');
const path = require('path');

// Load server/.env before any module reads process.env (e.g. ./sheets).
require('dotenv').config({ path: path.join(__dirname, '..', '.env') });

require('./db'); // ensure schema is created on boot
const { SERVICES, ANIMAL_TYPES, CARETAKER_COMMISSION_RATE, OWNER_SERVICE_FEE_RATE } = require('./constants');

const app = express();
app.use(cors());
app.use(express.json());

// Lightweight request log for local dev.
app.use((req, _res, next) => {
  console.log(`${new Date().toISOString()}  ${req.method} ${req.url}`);
  next();
});

// Public metadata the client uses to render filters/services consistently.
app.get('/api/meta', (_req, res) => {
  res.json({
    services: SERVICES,
    animal_types: ANIMAL_TYPES,
    fees: { caretaker_commission: CARETAKER_COMMISSION_RATE, owner_service_fee: OWNER_SERVICE_FEE_RATE },
  });
});

app.get('/api/health', (_req, res) => res.json({ ok: true }));

app.use('/api/auth', require('./routes/auth'));
app.use('/api/caretakers', require('./routes/caretakers'));
app.use('/api/bookings', require('./routes/bookings'));
app.use('/api/reviews', require('./routes/reviews'));
app.use('/api/messages', require('./routes/messages'));

// Serve the built client (production bundle) so the whole app runs from this one
// origin/port — no dev-server module requests or proxy needed.
const clientDist = path.join(__dirname, '..', '..', 'client', 'dist');
app.use(express.static(clientDist));
// SPA fallback: any non-/api GET returns index.html so client-side routing works.
app.get(/^\/(?!api\/).*/, (_req, res) => res.sendFile(path.join(clientDist, 'index.html')));

// 404 + error handlers.
app.use('/api', (_req, res) => res.status(404).json({ error: 'Not found.' }));
app.use((err, _req, res, _next) => {
  console.error(err);
  res.status(500).json({ error: 'Something went wrong on the ranch.' });
});

const PORT = process.env.PORT || 3001;
app.listen(PORT, () => console.log(`🐴 Ranch Hand API listening on http://localhost:${PORT}`));
