// Authentication helpers: password hashing (bcryptjs) and JWT issue/verify.
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');

// Local-dev secret. For a real deployment this would come from the environment.
const JWT_SECRET = process.env.JWT_SECRET || 'ranch-hand-dev-secret-change-me';
const TOKEN_TTL = '7d';

function hashPassword(plain) {
  return bcrypt.hashSync(plain, 10);
}

function verifyPassword(plain, hash) {
  return bcrypt.compareSync(plain, hash);
}

function signToken(user) {
  return jwt.sign(
    { id: user.id, role: user.role, name: user.name, email: user.email },
    JWT_SECRET,
    { expiresIn: TOKEN_TTL }
  );
}

// Express middleware: populates req.user when a valid Bearer token is present.
function authRequired(req, res, next) {
  const header = req.headers.authorization || '';
  const token = header.startsWith('Bearer ') ? header.slice(7) : null;
  if (!token) return res.status(401).json({ error: 'Authentication required.' });
  try {
    req.user = jwt.verify(token, JWT_SECRET);
    next();
  } catch {
    return res.status(401).json({ error: 'Invalid or expired session.' });
  }
}

// Soft variant: attaches req.user if a token exists, but never blocks the request.
function authOptional(req, _res, next) {
  const header = req.headers.authorization || '';
  const token = header.startsWith('Bearer ') ? header.slice(7) : null;
  if (token) {
    try { req.user = jwt.verify(token, JWT_SECRET); } catch { /* ignore */ }
  }
  next();
}

module.exports = { hashPassword, verifyPassword, signToken, authRequired, authOptional };
