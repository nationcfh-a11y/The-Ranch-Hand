/**
 * The Ranch Hand — signup mirror for the "The Ranch Hand Users" sheet.
 *
 * WordPress POSTs a signup to this Web App, which writes it to the tab named in
 * the "tab" field ("Ranch" or "Hand"). The row is sent as JSON in "payload",
 * keyed by the EXACT column header names on that tab. The script matches an
 * existing row by its "Email" value and updates it (so the 3-step Hand signup
 * fills one row as it progresses); otherwise it appends a new row. "ID" and
 * "Signed Up" are filled in by the script on insert and left alone on updates.
 *
 * Only the columns present in a given POST are written, so a later step never
 * blanks a column an earlier step already filled.
 *
 * HOW TO INSTALL / UPDATE
 *   1. Open the sheet -> Extensions -> Apps Script.
 *   2. Delete anything there, paste this whole file, Save.
 *   3. First time:  Deploy -> New deployment -> Web app
 *                     Execute as: Me    Who has access: Anyone
 *                   Copy the /exec URL into wp-admin -> Customize -> Integrations.
 *      Updating:    Deploy -> Manage deployments -> (pencil) Edit ->
 *                   Version: New version -> Deploy. The /exec URL stays the same.
 *
 * OPTIONAL HARDENING
 *   Set SHARED_TOKEN below to any random string, and paste the same string into
 *   the "Sheet secret token" field in the Customizer. Requests without a
 *   matching token are then rejected.
 */

var SHARED_TOKEN = ''; // must match the Customizer field if set.

function doPost(e) {
  try {
    var p = (e && e.parameter) ? e.parameter : {};

    if (SHARED_TOKEN && String(p.token || '') !== SHARED_TOKEN) {
      return _json({ ok: false, error: 'bad token' });
    }

    // Row data + target tab. New callers send tab + JSON payload; the else branch
    // keeps the original flat-field format working just in case.
    var tabName, data;
    if (p.payload) {
      tabName = p.tab || 'Ranch';
      data = JSON.parse(p.payload);
    } else {
      var role = String(p.role || '').toLowerCase();
      tabName = (role === 'owner' || role === 'ranch') ? 'Ranch' : 'Hand';
      data = {
        'Full Name': p.name || '',
        'Email': p.email || '',
        'Role': role || 'owner',
        'Location': p.location || '',
        'Search Radius (mi)': p.search_radius || ''
      };
    }

    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var sheet = ss.getSheetByName(tabName) || ss.insertSheet(tabName);

    // If the tab has no header row yet, seed one from the payload keys.
    if (sheet.getLastRow() === 0) {
      sheet.appendRow(['ID'].concat(Object.keys(data)).concat(['Signed Up']));
    }

    var headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
    var col = {}; // header name -> 1-based column
    for (var i = 0; i < headers.length; i++) {
      var h = String(headers[i]).trim();
      if (h) { col[h] = i + 1; }
    }

    var emailCol = col['Email'];
    var idCol = col['ID'];
    var signedCol = col['Signed Up'];

    // Find an existing row by email (case-insensitive).
    var rowNum = 0;
    var email = String(data['Email'] || '').trim().toLowerCase();
    if (emailCol && email && sheet.getLastRow() >= 2) {
      var col_vals = sheet.getRange(2, emailCol, sheet.getLastRow() - 1, 1).getValues();
      for (var r = 0; r < col_vals.length; r++) {
        if (String(col_vals[r][0]).trim().toLowerCase() === email) { rowNum = r + 2; break; }
      }
    }

    var isNew = (rowNum === 0);

    // Build the full row: start from the existing row (update) or blanks (insert),
    // then overlay every provided column. One setValues write keeps it fast.
    var width = headers.length;
    var rowVals;
    if (isNew) {
      rowNum = sheet.getLastRow() + 1;
      rowVals = [];
      for (var c = 0; c < width; c++) { rowVals.push(''); }
      if (idCol) { rowVals[idCol - 1] = _nextId(sheet, idCol); }
      if (signedCol) { rowVals[signedCol - 1] = _now(ss); }
    } else {
      rowVals = sheet.getRange(rowNum, 1, 1, width).getValues()[0];
    }

    for (var key in data) {
      if (key === 'ID' || key === 'Signed Up') { continue; } // script-managed
      if (col[key]) { rowVals[col[key] - 1] = data[key]; }
    }

    sheet.getRange(rowNum, 1, 1, width).setValues([rowVals]);

    return _json({ ok: true, row: rowNum, isNew: isNew });
  } catch (err) {
    return _json({ ok: false, error: String(err) });
  }
}

/** Next sequential ID = max existing numeric ID + 1. */
function _nextId(sheet, idCol) {
  var last = sheet.getLastRow();
  if (last < 2) { return 1; }
  var ids = sheet.getRange(2, idCol, last - 1, 1).getValues();
  var max = 0;
  for (var i = 0; i < ids.length; i++) {
    var n = parseInt(ids[i][0], 10);
    if (!isNaN(n) && n > max) { max = n; }
  }
  return max + 1;
}

function _now(ss) {
  return Utilities.formatDate(new Date(), ss.getSpreadsheetTimeZone(), 'yyyy-MM-dd HH:mm:ss');
}

function _json(obj) {
  return ContentService
    .createTextOutput(JSON.stringify(obj))
    .setMimeType(ContentService.MimeType.JSON);
}
