/**
 * The Ranch Hand — signup mirror for the "The Ranch Hand Users" sheet.
 *
 * WordPress POSTs a signup to this Web App, which writes it to the tab named in
 * the "tab" field ("Ranch" or "New Hand"). The row is sent as JSON in "payload",
 * keyed by the EXACT column header names on that tab.
 *
 * Matching an existing row (so re-submits update in place instead of adding a
 * new row):
 *   - if the payload has an "ID", the row is matched by the ID column
 *     (WordPress sends its own stable ID);
 *   - otherwise it is matched by the "Email" column.
 * On insert, ID is taken from the payload when given, else auto-numbered.
 * "Signed Up" is stamped on insert. Only columns present in the payload are
 * written, so a later step never blanks a column an earlier step filled.
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
 *   Set SHARED_TOKEN below and paste the same value into the Customizer's
 *   "Sheet secret token" field; requests without a matching token are rejected.
 */

var SHARED_TOKEN = ''; // must match the Customizer field if set.

function doPost(e) {
  try {
    var p = (e && e.parameter) ? e.parameter : {};

    if (SHARED_TOKEN && String(p.token || '') !== SHARED_TOKEN) {
      return _json({ ok: false, error: 'bad token' });
    }

    var tabName, data;
    if (p.payload) {
      tabName = p.tab || 'Ranch';
      data = JSON.parse(p.payload);
    } else {
      // Legacy flat fields, kept working just in case.
      var role = String(p.role || '').toLowerCase();
      tabName = (role === 'owner' || role === 'ranch') ? 'Ranch' : 'New Hand';
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

    if (sheet.getLastRow() === 0) {
      sheet.appendRow(['ID'].concat(Object.keys(data)).concat(['Signed Up']));
    }

    var headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
    var col = {};
    for (var i = 0; i < headers.length; i++) {
      var h = String(headers[i]).trim();
      if (h) { col[h] = i + 1; }
    }

    var idCol = col['ID'];
    var signedCol = col['Signed Up'];

    // Match on ID when supplied, otherwise on Email.
    var hasId = (data['ID'] !== undefined && String(data['ID']).trim() !== '');
    var keyHeader = hasId ? 'ID' : 'Email';
    var keyCol = col[keyHeader];
    var keyVal = String(data[keyHeader] || '').trim().toLowerCase();

    var rowNum = 0;
    if (keyCol && keyVal && sheet.getLastRow() >= 2) {
      var vals = sheet.getRange(2, keyCol, sheet.getLastRow() - 1, 1).getValues();
      for (var r = 0; r < vals.length; r++) {
        if (String(vals[r][0]).trim().toLowerCase() === keyVal) { rowNum = r + 2; break; }
      }
    }

    var isNew = (rowNum === 0);
    var width = headers.length;
    var rowVals;
    if (isNew) {
      rowNum = sheet.getLastRow() + 1;
      rowVals = [];
      for (var c = 0; c < width; c++) { rowVals.push(''); }
      if (idCol) {
        rowVals[idCol - 1] = hasId ? data['ID'] : _nextId(sheet, idCol);
      }
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
