<?php

require_once('session.php');

require_once('auth.php');

require_once('database.php');

require_once('template.php');

template_header('Import/Export');

// generate the confirmation code
$_SESSION['confirmation_code'] = '';
for ($i = 0; $i < 8; $i++) {
	$_SESSION['confirmation_code'] .= (string)mt_rand(0, 9);
}

if (isset($_SESSION['import_export_error'])) {
	echo '<script>alert(';
	echo json_encode($_SESSION['import_export_error']);
	echo ');</script>';
	unset($_SESSION['import_export_error']);
}

echo '<h2>Export</h2>';
echo '<ul>';
echo '<li>To export a competition, go to the competition you wish to export in the management section, and click "Export (CSV)"</li>';
echo '</ul>';

echo '<h2>Import</h2>';


$competitions = database_query('SELECT "years"."id" AS "year_id", "years"."name" AS "year_name", "competitions"."id" AS "competition_id", "competitions"."name" AS "competition_name" FROM "competitions" INNER JOIN "years" ON "years"."id" = "competitions"."year" ORDER BY "year_name", "competition_name";');

echo '<h3>CSV format</h3>';
echo '<form enctype="multipart/form-data" action="import_csv.php" method="POST">';
echo '<strong>Note: This can delete data. Ensure you export the current data if you wish to keep it.</strong><br />';
echo '<strong>Note: Only the names of events, clubs, and teams can be imported.</strong><br />';
echo '<strong>Note: This can only (currently) import point-based scores.</strong><br />';
echo '<input type="hidden" name="MAX_FILE_SIZE" value="1048576" />';
echo '<label for="import_csv_file">CSV file to import (max size: 1MiB): </label>';
echo '<input id="import_csv_file" name="import_file" type="file" accept=".csv,text/csv" required="required" />';
echo '<br />';
echo '<label for="import_csv_import_mode">Import option: </label>';
echo '<select id="import_csv_import_mode" name="import_mode">';
echo '<option value="scores_only" selected="selected">Merge Scores Only (overwrites conflicting existing scores)</option>';
echo '<option value="merge">Merge New Clubs, Teams, Events and Scores (overwrites conflicting existing scores)</option>';
echo '<option value="delete">Delete Existing Teams, Events and Scores, Merge Clubs</option>';
echo '</select>';
echo '<br />';
echo '<label for="import_csv_import_competition">Competition to import: </label>';
echo '<select id="import_csv_import_competition" name="import_competition_id" required="required">';
echo '<option value="" selected="selected" disabled="disabled">-- Select a Competition --</option>';
foreach ($competitions as $competition) {
	echo '<option value="' . htmlescape($competition['competition_id']) . '">' . htmlescape($competition['year_name']) . ' - ' . htmlescape($competition['competition_name']) . '</option>';
}
echo '</select>';
echo '<br />';
echo 'To import data, select a CSV file, select the competition to import and enter the following code in the textbox:';
echo ' <code>';
echo htmlescape($_SESSION['confirmation_code']);
echo '</code> ';
echo '<br />';
echo '<input name="confirm_code" type="text" placeholder="Confirmation Code" required="required" />';
echo '<input type="submit" value="Import" onclick="return confirm(&quot;Really import the file? This can delete **ALL** events, clubs, teams, and scores.&quot;);" />';
echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
echo '</form>';

template_footer();
