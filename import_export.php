<?php

require_once('session.php');
require_once('auth.php');
require_once('database.php');
require_once('template.php');

template_header('Import/Export');

if (isset($_SESSION['import_export_error'])) {
	echo '<script>alert(';
	echo json_encode($_SESSION['import_export_error']);
	echo ');</script>';
	unset($_SESSION['import_export_error']);
}

echo '<h2>Export</h2>';

echo '<h3>SQLite3 database file</h3>';
echo '<form action="export_sqlite3.php" method="POST">';
echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
echo '<input type="submit" value="Export SQLite3 database" />';
echo '</form>';

/*
echo '<br />To export a competition, go to the competition you wish to export in the management section, and click "Export (CSV)"';
*/


echo '<h2>Import</h2>';

echo '<h3>SQLite3 database file</h3>';
echo '<form enctype="multipart/form-data" action="import_sqlite3.php" method="POST">';
echo '<strong>WARNING: This will delete <em>***ALL***</em> existing data (except for system users). Ensure you back up the current data if you wish to keep it.</strong><br />';
echo '<input type="hidden" name="MAX_FILE_SIZE" value="2000000" />';
echo '<label for="import_sqlite3_file">SQLite3 file to import (max size: 2MB): </label>';
echo '<input id="import_sqlite3_file" name="import_file" type="file" accept=".sqlite3,application/vnd.sqlite3,application/x-sqlite3" required="required" />';
echo '<br />';
echo '<input type="submit" value="Import" onclick="return confirm(&quot;Really import this file? This WILL delete ***ALL*** data (except for system users).&quot;);" />';
echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
echo '</form>';

/*
$competitions = database_query('SELECT "years"."id" AS "year_id", "years"."name" AS "year_name", "competitions"."id" AS "competition_id", "competitions"."name" AS "competition_name" FROM "competitions" INNER JOIN "years" ON "years"."id" = "competitions"."year" ORDER BY "year_name", "competition_name";');
echo '<h3>CSV format</h3>';
echo '<form enctype="multipart/form-data" action="import_csv.php" method="POST">';
echo '<strong>WARNING: This can delete data. Ensure you back up the current data if you wish to keep it.</strong><br />';
echo '<strong>Note: Only the names of events, clubs, and teams can be imported.</strong><br />';
echo '<strong>Note: This can only (currently) import point-based scores.</strong><br />';
echo '<input type="hidden" name="MAX_FILE_SIZE" value="2000000" />';
echo '<label for="import_csv_file">CSV file to import (max size: 2MB): </label>';
echo '<input id="import_csv_file" name="import_file" type="file" accept=".csv,text/csv" required="required" />';
echo '<br />';
echo '<label for="import_csv_import_mode">Import option: </label>';
echo '<select id="import_csv_import_mode" name="import_mode">';
echo '<option value="scores_only" selected="selected">Merge Scores Only (overwrites conflicting existing scores)</option>';
echo '<option value="merge">Merge New Clubs, Teams, Events and Scores (overwrites conflicting existing scores)</option>';
echo '<option value="delete">Delete Existing Teams, Events and Scores, Merge Clubs</option>';
echo '</select>';
echo '<br />';
echo '<label for="import_csv_import_competition">Competition to import to: </label>';
echo '<select id="import_csv_import_competition" name="import_competition_id" required="required">';
echo '<option value="" selected="selected" disabled="disabled">-- Select a Competition --</option>';
foreach ($competitions as $competition) {
	echo '<option value="' . htmlescape($competition['competition_id']) . '">' . htmlescape($competition['year_name']) . ' - ' . htmlescape($competition['competition_name']) . '</option>';
}
echo '</select>';
echo '<br />';
echo '<input type="submit" value="Import" onclick="return confirm(&quot;Really import the file? This can delete **ALL** events, clubs, teams, and scores.&quot;);" />';
echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
echo '</form>';
*/

template_footer();
