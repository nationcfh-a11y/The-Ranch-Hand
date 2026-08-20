/**
 * The Ranch Hand — signup mirror for the "The Ranch Hand Users" sheet.
 *
 * Appends each WordPress signup to the "Ranch" tab (owners) or the "Hand" tab
 * (caretakers), matching the existing columns:
 *
 *   ID | Full Name | Email | Role | Location | Search Radius (mi) | Signed Up
 *
 * HOW TO INSTALL
 *   1. Open the sheet -> Extensions -> Apps Script.
 *   2. Delete anything there, paste this whole file, Save.
 *   3. Deploy -> New deployment -> Web app.
 *        Execute as:      Me
 *        Who has access:  Anyone
 *      Copy the Web app URL (ends in /exec).
 *   4. Paste that URL into wp-admin -> Appearance -> Customize -> Integrations.
 *
 * OPTIONAL HARDENING
 *   Set SHARED_TOKEN below to any random string, and paste the same string into
 *   the "Sheet secret token" field in the Customizer. Requests without a
 *   matching token are then rejected. Leave it '' to accept any request (the
 *   /exec URL itself is long and unguessable).
 *
 *   After editing, redeploy: Deploy -> Manage deployments -> Edit -> New version.
 */

var SHARED_TOKEN = ''; // e.g. 'k7Qp...'; must match the Customizer field if set.

function doPost(e) {
  try {
    var p = (e && e.parameter) ? e.parameter : {};

    if (SHARED_TOKEN && String(p.token || '') !== SHARED_TOKEN) {
      return _json({ ok: false, error: 'bad token' });
    }

    var role = String(p.role || '').toLowerCase();
    var tabName = (role === 'owner' || role === 'ranch') ? 'Ranch' : 'Hand';

    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var sheet = ss.getSheetByName(tabName) || ss.insertSheet(tabName);

    if (sheet.getLastRow() === 0) {
      sheet.appendRow(['ID', 'Full Name', 'Email', 'Role', 'Location', 'Search Radius (mi)', 'Signed Up']);
    }

    var nextId = _nextId(sheet);
    var signedUp = Utilities.formatDate(new Date(), ss.getSpreadsheetTimeZone(), 'yyyy-MM-dd HH:mm:ss');

    sheet.appendRow([
      nextId,
      p.name || '',
      p.email || '',
      role || 'owner',
      p.location || '',
      p.search_radius || '',
      signedUp
    ]);

    return _json({ ok: true, id: nextId });
  } catch (err) {
    return _json({ ok: false, error: String(err) });
  }
}

/** Next sequential ID = max existing numeric ID + 1 (so it continues 27, 28 -> 29). */
function _nextId(sheet) {
  var last = sheet.getLastRow();
  if (last < 2) { return 1; }
  var ids = sheet.getRange(2, 1, last - 1, 1).getValues();
  var max = 0;
  for (var i = 0; i < ids.length; i++) {
    var n = parseInt(ids[i][0], 10);
    if (!isNaN(n) && n > max) { max = n; }
  }
  return max + 1;
}

function _json(obj) {
  return ContentService
    .createTextOutput(JSON.stringify(obj))
    .setMimeType(ContentService.MimeType.JSON);
}
