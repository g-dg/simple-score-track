<?php

require_once('session.php');

// force logon
if (!isset($_SESSION['user_id'])) {
	header('Location: login.php?redirect=import_export.php');
	exit();
}

require_once('database.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['action'], $_POST['_csrf_token']) && $_POST['_csrf_token'] === $_SESSION['csrf_token']) {
	switch ($_GET['action']) {
		case 'data_delete_all':
			if (isset($_POST['confirm_code'])) {
				if ($_POST['confirm_code'] === $_SESSION['confirmation_code']) {
					try {
						database_query('DELETE FROM "scores";');
						database_query('DELETE FROM "events";');
						database_query('DELETE FROM "teams";');
						database_query('DELETE FROM "clubs";');
						$_SESSION['import_export_error'] = 'All data deleted.';
					} catch (Exception $e) {
						$_SESSION['import_export_error'] = 'An error occurred while deleting the data.';
					}
				} else {
					$_SESSION['import_export_error'] = 'Confirmation code incorrect, data not deleted';
				}
			} else {
				http_response_code(400);
			}
			break;
		default:
			$_SESSION['import_export_error'] = 'An error occurred.';
			http_response_code(400);
	}
	header('Location: import_export.php');
	exit();
}

require_once('template.php');

template_header('Import/Export Data');

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
echo '<li><a href="export_json.php">Export events, clubs, teams, and scores (JSON format, for data backups)</a></li>';
echo '<li><a href="export_csv.php">Export events, clubs, teams, and scores (CSV format, for editing in an external program)</a></li>';
echo '</ul>';

echo '<h2>Import</h2>';
echo '<h3>JSON format</h3>';
echo '<strong>Note: This will delete data. Ensure you export the current data if you wish to keep it.</strong>';
echo '<br />';
echo '<form enctype="multipart/form-data" action="import_json.php" method="POST">';
echo '<input type="hidden" name="MAX_FILE_SIZE" value="1048576" />';
echo '<label for="import_json_file">JSON file to import (max size: 1MiB): </label>';
echo '<input id="import_json_file" name="import_file" type="file" accept=".json,text/json" required="required" />';
echo '<br />';
echo '<label for="import_json_data_select">Data to import: </label>';
echo '<select id="import_json_data_select" name="data_select">';
echo '<option value="clubs">Clubs (deletes existing clubs, teams, and scores)</option>';
echo '<option value="clubs,teams">Clubs and Teams (deletes existing clubs, teams, and scores)</option>';
echo '<option value="events">Events (deletes existing events and scores)</option>';
echo '<option value="clubs,events">Clubs and Events (deletes existing clubs, teams, events and scores)</option>';
echo '<option value="clubs,teams,events">Clubs, Teams and Events (deletes existing clubs, teams, events and scores)</option>';
echo '<option value="clubs,teams,events,scores" selected="selected">Clubs, Teams, Events and Scores (deletes existing clubs, teams, events and scores)</option>';
echo '</select>';
echo '<br />';
echo 'To import data, select a JSON file, select the data to import, and enter the following code in the textbox:';
echo ' <code>';
echo htmlescape($_SESSION['confirmation_code']);
echo '</code> ';
echo '<br />';
echo '<input name="confirm_code" type="text" placeholder="Confirmation Code" required="required" />';
echo '<input type="submit" value="Import" onclick="return confirm(&quot;Really import the file? This will delete data.&quot;);" />';
echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
echo '</form>';


echo '<h3>CSV format</h3>';
echo '<form enctype="multipart/form-data" action="import_csv.php" method="POST">';
echo '<strong>Note: This can delete data. Ensure you export the current data if you wish to keep it.</strong><br />';
echo '<strong>Note: Only the names of events, clubs, and teams can be imported.</strong><br />';
echo '<input type="hidden" name="MAX_FILE_SIZE" value="1048576" />';
echo '<label for="import_csv_file">CSV file to import (max size: 1MiB): </label>';
echo '<input id="import_csv_file" name="import_file" type="file" accept=".csv,text/csv" required="required" />';
echo '<br />';
echo '<label for="import_csv_import_mode">Import option: </label>';
echo '<select id="import_csv_import_mode" name="import_mode">';
echo '<option value="scores_only" selected="selected">Merge Scores Only (overwrites conflicting existing scores)</option>';
echo '<option value="merge">Merge New Clubs, Teams, Events and Scores (overwrites conflicting existing scores)</option>';
echo '<option value="delete">Delete Existing Clubs, Teams, Events and Scores</option>';
echo '</select>';
echo '<br />';
echo 'To import data, select a CSV file and enter the following code in the textbox:';
echo ' <code>';
echo htmlescape($_SESSION['confirmation_code']);
echo '</code> ';
echo '<br />';
echo '<input name="confirm_code" type="text" placeholder="Confirmation Code" required="required" />';
echo '<input type="submit" value="Import" onclick="return confirm(&quot;Really import the file? This can delete **ALL** events, clubs, teams, and scores.&quot;);" />';
echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
echo '</form>';

echo '<h2>Delete All Data</h2>';
echo '<form action="import_export.php?action=data_delete_all" method="post">';
echo '<strong>WARNING: THIS WILL DELETE ALL CLUBS, TEAMS, EVENTS, AND SCORES</strong><br />';
echo 'To delete all data, enter the following code in the textbox:';
echo ' <code>';
echo htmlescape($_SESSION['confirmation_code']);
echo '</code> ';
echo '<br />';
echo '<input name="confirm_code" type="text" placeholder="Confirmation Code" required="required" />';
echo '<input type="submit" value="Delete All Data" onclick="return confirm(&quot;Really delete **ALL** data (This won\'t delete users)?&quot;);" />';
echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
echo '</form>';

template_footer();
