#!/usr/bin/env node
// Populate server/.env from a Google service-account JSON key file.
//
//   node scripts/setup-sheets-env.js <path-to-service-account.json> [sheetId] [tabName]
//
// Reads the key file locally and writes the credential straight into .env, so
// the private key never has to be copied through a terminal, chat, or clipboard.
// Existing .env values are preserved; only the keys below are added/replaced.
const fs = require('fs');
const path = require('path');

const [, , keyPath, sheetIdArg, tabArg] = process.argv;

if (!keyPath) {
  console.error('Usage: node scripts/setup-sheets-env.js <service-account.json> [sheetId] [tabName]');
  process.exit(1);
}

const resolved = path.resolve(keyPath.replace(/^~/, process.env.HOME || '~'));
if (!fs.existsSync(resolved)) {
  console.error(`✗ No such file: ${resolved}`);
  process.exit(1);
}

let key;
try {
  key = JSON.parse(fs.readFileSync(resolved, 'utf8'));
} catch (err) {
  console.error(`✗ Could not parse JSON: ${err.message}`);
  process.exit(1);
}

if (!key.client_email || !key.private_key) {
  console.error('✗ That file has no "client_email"/"private_key". Is it a service-account key?');
  console.error('  (An OAuth client secret is a different file and will not work here.)');
  process.exit(1);
}

const ENV_PATH = path.join(__dirname, '..', '.env');
const existing = fs.existsSync(ENV_PATH) ? fs.readFileSync(ENV_PATH, 'utf8') : '';

// Store the key on one line with literal \n, matching what src/sheets.js expects.
const updates = {
  GOOGLE_SERVICE_ACCOUNT_EMAIL: key.client_email,
  GOOGLE_PRIVATE_KEY: `"${key.private_key.replace(/\n/g, '\\n')}"`,
};
if (sheetIdArg) updates.GOOGLE_SHEETS_ID = sheetIdArg;
if (tabArg) updates.GOOGLE_SHEETS_TAB = tabArg;

let out = existing;
for (const [k, v] of Object.entries(updates)) {
  const line = `${k}=${v}`;
  const re = new RegExp(`^${k}=.*$`, 'm');
  out = re.test(out) ? out.replace(re, line) : `${out}${out.endsWith('\n') || !out ? '' : '\n'}${line}\n`;
}
if (!out.endsWith('\n')) out += '\n';

fs.writeFileSync(ENV_PATH, out, { mode: 0o600 });

console.log(`✓ Wrote credentials to ${ENV_PATH} (chmod 600)`);
console.log('');
console.log('  Service account email:');
console.log(`    ${key.client_email}`);
console.log('');
console.log('  ⚠️  Share your Google Sheet with that address as **Editor**,');
console.log('     or every write will fail with a 403.');
