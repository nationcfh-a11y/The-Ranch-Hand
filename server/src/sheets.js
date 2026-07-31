// Google Sheets mirror — a temporary, read-only-for-humans CRM view of signups.
//
// SQLite remains the source of truth for auth. This module only *mirrors* new
// users into a spreadsheet so they can be eyeballed/filtered before a real CRM
// exists. Two deliberate properties:
//
//   1. Fail-soft — every export swallows its own errors. A bad key, a revoked
//      share, or no internet must never break signup.
//   2. Opt-in — with no credentials configured it is a silent no-op, so the app
//      still runs fully offline with zero external setup (as the README promises).
//
// Password hashes are never written here.
const { google } = require('googleapis');

const SHEET_ID = process.env.GOOGLE_SHEETS_ID || '';
const CLIENT_EMAIL = process.env.GOOGLE_SERVICE_ACCOUNT_EMAIL || '';
// Key is stored single-line in .env with literal "\n"; restore real newlines.
const PRIVATE_KEY = (process.env.GOOGLE_PRIVATE_KEY || '').replace(/\\n/g, '\n');
const TAB = process.env.GOOGLE_SHEETS_TAB || 'Signups';

// Human-friendly column labels — this sheet is read by people, not code.
// Deliberately no password/hash column: credentials never leave SQLite.
const HEADERS = ['ID', 'Full Name', 'Email', 'Role', 'Location', 'Search Radius (mi)', 'Signed Up'];

const isConfigured = () => Boolean(SHEET_ID && CLIENT_EMAIL && PRIVATE_KEY);

// --- Client (memoized) -------------------------------------------------------
let clientPromise = null;
function getSheetsClient() {
  if (!clientPromise) {
    const auth = new google.auth.JWT({
      email: CLIENT_EMAIL,
      key: PRIVATE_KEY,
      scopes: ['https://www.googleapis.com/auth/spreadsheets'],
    });
    clientPromise = auth
      .authorize()
      .then(() => google.sheets({ version: 'v4', auth }))
      .catch((err) => {
        clientPromise = null; // allow a later retry rather than caching the failure
        throw err;
      });
  }
  return clientPromise;
}

// --- One-time tab + header bootstrap ----------------------------------------
// Creates the tab if missing and writes the header row if the sheet is empty.
let readyPromise = null;
function ensureSheetReady(sheets) {
  if (!readyPromise) {
    readyPromise = (async () => {
      const meta = await sheets.spreadsheets.get({ spreadsheetId: SHEET_ID });
      const exists = (meta.data.sheets || []).some((s) => s.properties?.title === TAB);
      if (!exists) {
        await sheets.spreadsheets.batchUpdate({
          spreadsheetId: SHEET_ID,
          requestBody: { requests: [{ addSheet: { properties: { title: TAB } } }] },
        });
      }
      const head = await sheets.spreadsheets.values.get({
        spreadsheetId: SHEET_ID,
        range: `${TAB}!A1:G1`,
      });
      if (!head.data.values?.length) {
        await sheets.spreadsheets.values.update({
          spreadsheetId: SHEET_ID,
          range: `${TAB}!A1`,
          valueInputOption: 'RAW',
          requestBody: { values: [HEADERS] },
        });
      }
    })().catch((err) => {
      readyPromise = null; // retry bootstrap on the next signup
      throw err;
    });
  }
  return readyPromise;
}

// --- Public API --------------------------------------------------------------
// Appends one signup row. Never throws and never rejects — call and forget.
function mirrorSignup(user) {
  if (!isConfigured()) return Promise.resolve(false);

  return (async () => {
    const sheets = await getSheetsClient();
    await ensureSheetReady(sheets);
    await sheets.spreadsheets.values.append({
      spreadsheetId: SHEET_ID,
      range: `${TAB}!A:G`,
      // RAW, not USER_ENTERED, on purpose:
      //   1. USER_ENTERED coerces the timestamp into an opaque date serial.
      //   2. It also evaluates leading "=" as a formula, so a signup name like
      //      "=IMPORTXML(...)" would execute in the sheet. RAW stores text as text.
      valueInputOption: 'RAW',
      insertDataOption: 'INSERT_ROWS',
      requestBody: {
        values: [[
          user.id,
          user.name,
          user.email,
          user.role,
          user.location || '',
          user.search_radius ?? '',
          user.created_at || new Date().toISOString(),
        ]],
      },
    });
    return true;
  })().catch((err) => {
    // Log and move on — the user is already saved in SQLite.
    console.warn(`⚠️  Google Sheets mirror failed for ${user.email}: ${err.message}`);
    return false;
  });
}

module.exports = { mirrorSignup, isConfigured, HEADERS, TAB };
