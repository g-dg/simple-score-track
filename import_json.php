<?php

require_once('session.php');

require_once('auth.php');

require_once('database.php');

// check CSRF token
if (isset($_POST['data_select'], $_POST['_csrf_token']) && $_POST['_csrf_token'] === $_SESSION['csrf_token']) {
	// check the confirmation code
	if (isset($_POST['confirm_code']) && $_POST['confirm_code'] === $_SESSION['confirmation_code']) {
		// check that there is a file uploaded
		if (isset($_FILES['import_file']) && is_uploaded_file($_FILES['import_file']['tmp_name'])) {
			$file_data = file_get_contents($_FILES['import_file']['tmp_name']);
			$database_connection->beginTransaction();
			try {
				$data = json_decode($file_data, true);
				if (json_last_error() != JSON_ERROR_NONE) {
					throw new Exception('Malformed JSON data');
				}

				// check whether the required fields are present
				$data_to_import = explode(',', $_POST['data_select']);
				if (!(is_array($data) && 
					(in_array('clubs', $data_to_import) ? isset($data['clubs']) && is_array($data['clubs']) : true) &&
					(in_array('teams', $data_to_import) ? isset($data['teams']) && is_array($data['teams']) : true) &&
					(in_array('events', $data_to_import) ? isset($data['events']) && is_array($data['events']) : true) &&
					(in_array('scores', $data_to_import) ? isset($data['scores']) && is_array($data['scores']) : true)
				)){
					throw new Exception('Invalid data');
				}

				// clear the tables that need to be cleared
				database_query('DELETE FROM "scores";'); // scores will always be cleared
				if (in_array('events', $data_to_import)) {
					database_query('DELETE FROM "events";');
				}
				if (in_array('teams', $data_to_import)) {
					database_query('DELETE FROM "teams";');
					database_query('DELETE FROM "clubs"');
				}
				if (in_array('clubs', $data_to_import)) {
					database_query('DELETE FROM "clubs"');
				}

				// import clubs
				if (in_array('clubs', $data_to_import)) {
					foreach ($data['clubs'] as $record) {
						// validate record
						if (isset($record['id'], $record['name']) && is_int($record['id']) && is_string($record['name'])) {
							database_query('INSERT INTO "clubs"("id","name") VALUES (?,?);', [$record['id'], $record['name']]);
						} else {
							throw new Exception('Invalid club data');
						}
					}
				}

				// import teams
				if (in_array('teams', $data_to_import) && in_array('clubs', $data_to_import)) { // check that clubs were imported
					foreach ($data['teams'] as $record) {
						// validate record
						if (isset($record['id'], $record['club'], $record['name']) && is_int($record['id']) && is_int($record['club']) && is_string($record['name'])) {
							database_query('INSERT INTO "teams"("id","club","name") VALUES (?,?,?);', [$record['id'], $record['club'], $record['name']]);
						} else {
							throw new Exception('Invalid team data');
						}
					}
				}

				// import events
				if (in_array('events', $data_to_import)) {
					foreach ($data['events'] as $record) {
						// validate record
						if (isset($record['id'], $record['name'], $record['overall_point_multiplier']) && is_int($record['id']) && is_string($record['name']) && (is_int($record['overall_point_multiplier']) || is_float($record['overall_point_multiplier']))) {
							database_query('INSERT INTO "events"("id","name","overall_point_multiplier") VALUES (?,?,?);', [$record['id'], $record['name'], round((float)$record['overall_point_multiplier'], 2)]);
						} else {
							throw new Exception('Invalid event data');
						}
					}
				}

				// import scores
				if (in_array('scores', $data_to_import) && in_array('events', $data_to_import) && in_array('teams', $data_to_import)) { // check that events and teams were imported
					foreach ($data['scores'] as $record) {
						// validate record
						if (isset($record['team'], $record['event'], $record['points']) && is_int($record['team']) && is_int($record['event']) && (is_int($record['points']) || is_float($record['points']))) {
							database_query('INSERT INTO "scores"("team","event","points") VALUES (?,?,?);', [$record['team'], $record['event'], round((float)$record['points'], 2)]);
						} else {
							throw new Exception('Invalid team data');
						}
					}
				}

				$database_connection->commit();

				// redirect to import/export page
				$_SESSION['import_export_error'] = 'Import succeeded.';
				header('Location: import_export.php');
			} catch (Exception $e) {
				$database_connection->rollBack();
				echo 'An error occurred while attempting to import the data; changes have been undone.';
				$_SESSION['import_export_error'] = 'An error occurred while attempting to import the data; changes have been undone.';
				header('Location: import_export.php');
			}
		} else {
			http_response_code(400);
		}
	} else {
		$_SESSION['import_export_error'] = 'Confirmation code incorrect.';
		header('Location: import_export.php');
	}
} else {
	http_response_code(400);
}
