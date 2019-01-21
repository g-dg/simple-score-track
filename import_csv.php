<?php

require_once('session.php');

// force logon
if (!isset($_SESSION['user_id'])) {
	header('Location: login.php?redirect=export_scores_csv.php');
	exit();
}

require_once('database.php');

// check CSRF token
if (isset($_POST['import_mode'], $_POST['_csrf_token']) && $_POST['_csrf_token'] === $_SESSION['csrf_token']) {
	// check the confirmation code
	if (isset($_POST['confirm_code']) && $_POST['confirm_code'] === $_SESSION['confirmation_code']) {
		// check that there is a file uploaded
		if (isset($_FILES['import_file']) && is_uploaded_file($_FILES['import_file']['tmp_name'])) {

			$database_connection->beginTransaction();
			try {
				// read csv file into array
				$fh = fopen($_FILES['import_file']['tmp_name'], 'r');
				$csv = [];
				while (!feof($fh)) {
					$line = fgetcsv($fh);
					if ($line) {
						$csv[] = $line;
					}
				}

				// check for valid csv header
				if (!isset($csv[0]) || !is_array($csv[0]) || count($csv[0]) < 2) {
					throw new Exception('Invalid CSV header');
				}

				if ($_POST['import_mode'] == 'delete') {
					// delete everything that we are going to import
					database_query('DELETE FROM "scores";');
					database_query('DELETE FROM "events";');
					database_query('DELETE FROM "teams";');
					database_query('DELETE FROM "clubs"');
				}

				// create events
				$csv_events = array_slice($csv[0], 2);
				// check for duplicate events in csv file
				if (count($csv_events) !== count(array_unique($csv_events))) {
					throw new Exception('Duplicate event(s) detected in CSV file');
				}
				// don't create events if we are only merging scores
				if ($_POST['import_mode'] != 'scores_only') {
					foreach ($csv_events as $event) {
						// check if the event already exists
						if ((int)database_query('SELECT COUNT() FROM "events" WHERE "name" = ?;', [$event])[0][0] < 1) {
							database_query('INSERT INTO "events"("name") VALUES (?);', [$event]);
						} else {
							if ($_POST['import_mode'] == 'delete') {
								throw new Exception('Duplicate event detected');
							}
						}
					}
				}

				// create clubs and teams
				//TODO: check for duplicate clubs and teams in csv file
				$csv_records = array_slice($csv, 1);
				foreach ($csv_records as $record) {
					// integrity check
					if (!is_array($record) || count($record) < 2) {
						throw new Exception('Invalid record');
					}
					// don't create the club or teams if we are only merging scores
					if ($_POST['import_mode'] != 'scores_only') {
						// check if the club already exists
						if ((int)database_query('SELECT COUNT() FROM "clubs" WHERE "name" = ?;', [$record[0]])[0][0] < 1) {
							// create the new club
							database_query('INSERT INTO "clubs"("name") VALUES (?);', [$record[0]]);
						}

						// create the team
						// get the club id
						$club_id = (int)database_query('SELECT "id" FROM "clubs" WHERE "name" = ?;', [$record[0]])[0]['id'];
						// check if the team already exists
						if ((int)(database_query('SELECT COUNT() FROM "teams" WHERE "club" = ? AND "name" = ?;', [$club_id, $record[1]])[0][0]) < 1) {
							database_query('INSERT INTO "teams"("club", "name") VALUES (?, ?);', [$club_id, $record[1]]);
						} else {
							if ($_POST['import_mode'] == 'delete') {
								throw new Exception('Duplicate team detected');
							}
						}
					}

					// try to get the team id
					$result = database_query('SELECT "teams"."id" AS "team_id" FROM "teams" INNER JOIN "clubs" ON "teams"."club" = "clubs"."id" WHERE "clubs"."name" = ? AND "teams"."name" = ?;', [$record[0], $record[1]]);
					if (isset($result[0])) {
						$team_id = (int)$result[0]['team_id'];
					} else {
						// there is no team id, nothing to do
						// only should happen when merging scores
						continue;
					}

					for ($i = 2; $i < count($record); $i++) {
						// find the event id
						if (isset($csv[0][$i])) {
							$result = database_query('SELECT "id" FROM "events" WHERE "name" = ?;', [$csv[0][$i]]);
							if (isset($result[0])) {
								$event_id = (int)$result[0]['id'];
							} else {
								// there is no event id, nothing to do
								// only should happen when merging scores
								continue;
							}
						} else {
							throw new Exception('No corresponding header for column');
						}
						// insert the score
						// check if it should be null
						if ($record[$i] !== '') {
							// check if it is a number
							if (is_numeric($record[$i]) && ($score = (float)$record[$i]) >= 0) {
								database_query('INSERT INTO "scores"("team", "event", "points") VALUES (?, ?, ?);', [$team_id, $event_id, round($score, 2)]);
							} else {
								throw new Exception('Invalid or negative score');
							}
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
			fclose($fh);
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
