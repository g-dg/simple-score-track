<?php

require_once('session.php');

require_once('auth.php');

require_once('database.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['action'], $_POST['_csrf_token']) && $_POST['_csrf_token'] === $_SESSION['csrf_token']) {
	switch ($_GET['action']) {
		case 'get_competitions':
			header('Content-Type: application/json');
			if (isset($_POST['year_id'])) {
				$competitions = [];
				foreach (database_query('SELECT "id", "name" FROM "competitions" WHERE "year" = ? ORDER BY "name";', [(int)$_POST['year_id']]) as $competition) {
					$competitions[] = ['id' => (int)$competition['id'], 'name' => $competition['name']];
				}
				usort($competitions, function ($a, $b) {
					return strnatcasecmp($a['name'], $b['name']);
				});
				echo json_encode($competitions);
			} else {
				http_response_code(400);
			}
			break;
		case 'get_clubs':
			header('Content-Type: application/json');
			if (isset($_POST['year_id'])) {
				$clubs = [];
				foreach (database_query('SELECT "id", "name" FROM "clubs" WHERE "year" = ? ORDER BY "name";', [(int)$_POST['year_id']]) as $club) {
					$clubs[] = ['id' => (int)$club['id'], 'name' => $club['name']];
				}
				usort($clubs, function ($a, $b) {
					return strnatcasecmp($a['name'], $b['name']);
				});
				echo json_encode($clubs);
			} else {
				http_response_code(400);
			}
			break;
		case 'get_events':
			header('Content-Type: application/json');
			if (isset($_POST['competition_id'])) {
				$events = [];
				foreach (database_query('SELECT "id", "name", "type" FROM "events" WHERE "competition" = ? ORDER BY "name";', [(int)$_POST['competition_id']]) as $event) {
					$events[] = ['id' => (int)$event['id'], 'name' => $event['name'], 'type' => $event['type']];
				}
				usort($events, function ($a, $b) {
					return strnatcasecmp($a['name'], $b['name']);
				});
				echo json_encode($events);
			} else {
				http_response_code(400);
			}
			break;
		case 'get_teams':
			header('Content-Type: application/json');
			if (isset($_POST['club_id'], $_POST['competition_id'])) {
				$teams = [];
				foreach (database_query('SELECT "id", "name" FROM "teams" WHERE "club" = ? AND "competition" = ? ORDER BY "name";', [(int)$_POST['club_id'], (int)$_POST['competition_id']]) as $team) {
					$teams[] = ['id' => (int)$team['id'], 'name' => $team['name']];
				}
				usort($teams, function ($a, $b) {
					return strnatcasecmp($a['name'], $b['name']);
				});
				echo json_encode($teams);
			} else {
				http_response_code(400);
			}
			break;
		case 'get_score':
			header('Content-Type: application/json');
			if (isset($_POST['event_id'])) {
				$event_details = database_query('SELECT "type" FROM "events" WHERE "id" = ?;', [(int)$_POST['event_id']]);
				if (isset($event_details[0])) {
					$event_type = $event_details[0]['type'];
					switch ($event_type) {
						case 'points':
							if (isset($_POST['team_id'])) {
								$score = database_query('SELECT "points" FROM "point_scores" WHERE "team" = ? AND "event" = ?;', [(int)$_POST['team_id'], (int)$_POST['event_id']]);
								if (isset($score[0])) {
									echo json_encode(['type' => 'points', 'points' => (float)$score[0]['points']]);
								} else {
									echo json_encode(['type' => 'points', 'points' => null]);
								}
							} else {
								http_response_code(400);
							}
							break;
						case 'timed':
							if (isset($_POST['team_id'])) {
								$score = database_query('SELECT "time", "errors" FROM "timed_scores" WHERE "team" = ? AND "event" = ?;', [(int)$_POST['team_id'], (int)$_POST['event_id']]);
								if (isset($score[0])) {
									echo json_encode(['type' => 'timed', 'time' => (float)$score[0]['time'], 'errors' => (float)$score[0]['errors']]);
								} else {
									echo json_encode(['type' => 'timed', 'time' => null, 'errors' => null]);
								}
							} else {
								http_response_code(400);
							}
							break;
						case 'individual':
							if (isset($_POST['club_id'])) {
								$results = database_query('SELECT "id", "name", "points" FROM "individual_scores" WHERE "club" = ? AND "event" = ? ORDER BY "name";', [(int)$_POST['club_id'], (int)$_POST['event_id']]);
								$scores = [];
								foreach ($results as $result) {
									$scores[$result['id']] = ['name' => $result['name'], 'points' => (float)$result['points']];
								}
								uasort($scores, function ($a, $b) {
									return strnatcasecmp($a['name'], $b['name']);
								});
								echo json_encode(['type' => 'individual', 'scores' => $scores]);
							} else {
								http_response_code(400);
							}
							break;
						default:
							http_response_code(500);
							break;
					}
				} else {
					http_response_code(404);
				}
			} else {
				http_response_code(400);
			}
			break;
		case 'set_score':
			if (isset($_POST['event_id'])) {
				$event_details = database_query('SELECT "type" FROM "events" WHERE "id" = ?;', [(int)$_POST['event_id']]);
				if (isset($event_details[0])) {
					$event_type = $event_details[0]['type'];
					switch ($event_type) {
						case 'points':
							if (isset($_POST['team_id'], $_POST['points'])) {
								if ($_POST['points'] !== '') {
									database_query('INSERT INTO "point_scores" ("team", "event", "points") VALUES (?, ?, ?);', [(int)$_POST['team_id'], (int)$_POST['event_id'], (float)$_POST['points']]);
								} else {
									database_query('DELETE FROM "point_scores" WHERE "team" = ? AND "event" = ?;', [(int)$_POST['team_id'], (int)$_POST['event_id']]);
								}
							} else {
								http_response_code(400);
							}
							break;
						case 'timed':
							if (isset($_POST['team_id'], $_POST['time'], $_POST['errors'])) {
								if ($_POST['time'] !== '' || $_POST['errors'] !== '') {
									database_query('INSERT INTO "timed_scores" ("team", "event", "time", "errors") VALUES (?, ?, ?, ?);', [(int)$_POST['team_id'], (int)$_POST['event_id'], (float)$_POST['time'], (float)$_POST['errors']]);
								} else {
									database_query('DELETE FROM "timed_scores" WHERE "team" = ? AND "event" = ?;', [(int)$_POST['team_id'], (int)$_POST['event_id']]);
								}
							} else {
								http_response_code(400);
							}
							break;
						case 'individual':
							if (isset($_POST['club_id'], $_POST['scores'])) {
								$scores = json_decode($_POST['scores'], true);
								foreach ($scores as $key => $score) {
									if ($key === '' && $score['name'] !== '' && $score['points'] !== '') {
										database_query('INSERT INTO "individual_scores" ("club", "event", "name", "points") VALUES (?, ?, ?, ?);', [(int)$_POST['club_id'], (int)$_POST['event_id'], $score['name'], (float)$score['points']]);
									} else {
										if ($score['name'] !== '' || $score['points'] !== '') {
											database_query('UPDATE "individual_scores" SET "name" = ?, "points" = ? WHERE "id" = ?;', [$score['name'], (float)$score['points'], (int)$key]);
										} else {
											database_query('DELETE FROM "individual_scores" WHERE "id" = ?;', [(int)$key]);
										}
									}
								}
							} else {
								http_response_code(400);
							}
							break;
						default:
							http_response_code(500);
							break;
					}
				} else {
					http_response_code(400);
				}
			} else {
				http_response_code(404);
			}
			break;
		default:
			http_response_code(400);
			break;
	}
	exit();
}

require_once('template.php');

template_header('Score Entry', true, '<script src="res/scores.js"></script>');

echo '<form id="score_form" action="scores.php" method="post" class="form">';

echo '<input id="csrf_token" name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';

$years = database_query('SELECT "id", "name" FROM "years" ORDER BY "name";');
usort($years, function ($a, $b) {
	return strnatcasecmp($a['name'], $b['name']);
});

echo '<table>';

echo '<tbody>';

echo '<tr>';
echo '<td>';
echo '<label for="year">Year:</label>';
echo '</td>';
echo '<td>';
echo '<select id="year" autofocus="autofocus">';
echo '<option value="" selected="selected" disabled="disabled">-- Select Year --</option>';
foreach ($years as $year) {
	echo '<option value="' . htmlescape($year['id']) . '">' . htmlescape($year['name']) . '</option>';
}
echo '</select>';
echo '</td>';
echo '</tr>';

echo '<tr>';
echo '<td>';
echo '<label for="competition">Competition:</label>';
echo '</td>';
echo '<td>';
echo '<select id="competition" disabled="disabled">';
echo '<option value="" selected="selected" disabled="disabled">-- Select Competition --</option>';
echo '</select>';
echo '</td>';
echo '</tr>';

echo '<tr>';
echo '<td>';
echo '<label for="event">Event:</label>';
echo '</td>';
echo '<td>';
echo '<select id="event" disabled="disabled">';
echo '<option value="" selected="selected" disabled="disabled">-- Select Event --</option>';
echo '</select>';
echo '</td>';
echo '</tr>';

echo '<tr>';
echo '<td>';
echo '<label for="club">Club:</label>';
echo '</td>';
echo '<td>';
echo '<select id="club" disabled="disabled">';
echo '<option value="" selected="selected" disabled="disabled">-- Select Club --</option>';
echo '</select>';
echo '</td>';
echo '</tr>';

echo '<tr>';
echo '<td>';
echo '<label for="team">Team:</label>';
echo '</td>';
echo '<td>';
echo '<select id="team" disabled="disabled">';
echo '<option value="" selected="selected" disabled="disabled">-- Select Team --</option>';
echo '</select>';
echo '</td>';
echo '</tr>';

echo '<tr id="score_points">';
echo '<td>';
echo '<label for="score_points_value">Points:</label>';
echo '</td>';
echo '<td>';
echo '<input id="score_points_value" value="" type="number" min="0" step="0.01" disabled="disabled" />';
echo '</td>';
echo '</tr>';

echo '<tr id="score_time" style="display: none;">';
echo '<td>';
echo '<label for="score_time_value">Time:</label>';
echo '</td>';
echo '<td>';
echo '<input id="score_time_value" value="" type="text" pattern="(\d*:)?\d+(\.\d{0,3})?" placeholder="mm:ss.sss" maxlength="250" disabled="disabled" />';
echo '</td>';
echo '</tr>';

echo '<tr id="score_errors" style="display: none;">';
echo '<td>';
echo '<label for="score_errors_value">Errors:</label>';
echo '</td>';
echo '<td>';
echo '<input id="score_errors_value" value="" type="number" min="0" step="0.01" disabled="disabled" />';
echo '</td>';
echo '</tr>';

echo '</tbody>';

echo '<tbody id="individual_scores"></tbody>';

echo '<tfoot>';

echo '<tr>';
echo '<td></td>';
echo '<td>';
echo '<input id="submit" type="submit" value="Save" disabled="disabled" />';
echo '</td>';
echo '</tr>';

echo '<tr>';
echo '<td></td>';
echo '<td>';
echo '<div id="status">Ready.</div>';
echo '</td>';
echo '</tr>';

echo '</tfoot>';

echo '</table>';

echo '</form>';

template_footer();
