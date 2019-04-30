<?php

require_once('session.php');

require_once('auth.php');

require_once('database.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'], $_POST['_csrf_token']) && $_POST['_csrf_token'] === $_SESSION['csrf_token']) {
	switch ($_POST['action']) {
		case 'get_competitions':
			if (isset($_POST['year_id'])) {
				$competitions = [];
				foreach (database_query('SELECT "id", "name" FROM "competitions" WHERE "year" = ?', [(int)$_POST['year_id']]) as $competition) {
					$competitions[] = ['id' => (int)$competition['id'], 'name' => $competition['name']];
				}
				echo json_encode($competitions);
			} else {
				http_response_code(400);
			}
			break;
		case 'get_clubs':
			if (isset($_POST['year_id'])) {
				$clubs = [];
				foreach (database_query('SELECT "id", "name" FROM "clubs" WHERE "year" = ?', [(int)$_POST['year_id']]) as $club) {
					$clubs[] = ['id' => (int)$club['id'], 'name' => $club['name']];
				}
				echo json_encode($clubs);
			} else {
				http_response_code(400);
			}
			break;
		case 'get_events':
			if (isset($_POST['competition_id'])) {
				$events = [];
				foreach (database_query('SELECT "id", "name", "type" FROM "events" WHERE "competition" = ?', [(int)$_POST['competition_id']]) as $event) {
					$events[] = ['id' => (int)$event['id'], 'name' => $event['name'], 'type' => $event['type']];
				}
				echo json_encode($events);
			} else {
				http_response_code(400);
			}
			break;
		case 'get_teams':
			if (isset($_POST['club_id'], $_POST['competition_id'])) {
				$teams = [];
				foreach (database_query('SELECT "id", "name" FROM "teams" WHERE "club" = ? AND "competition" = ?', [(int)$_POST['club_id'], (int)$_POST['competition_id']]) as $team) {
					$teams[] = ['id' => (int)$team['id'], 'name' => $team['name']];
				}
				echo json_encode($teams);
			} else {
				http_response_code(400);
			}
			break;
		case 'get_score':
			if (isset($_POST['team_id'], $_POST['event_id'])) {
				/*$score = database_query('SELECT "points" FROM "scores" WHERE "team" = ? AND "event" = ?;', [(int)$_POST['team_id'], (int)$_POST['event_id']]);
				if (isset($score[0])) {
					echo round($score[0]['points'], 2);
				} else {
					echo '';
				}*/
			} else {
				http_response_code(400);
			}
			break;
		case 'set_score':
			if (isset($_POST['team_id'], $_POST['event_id'], $_POST['score'])) {
				// check if there is a score to insert
				if ($_POST['score'] !== '') {
					// input the score
					
				} else {
					// clear the score
					
				}
			} else {
				http_response_code(400);
			}
			break;
		default:
			$_SESSION['scores_error'] = 'An error occurred.';
			http_response_code(400);
			break;
	}
	exit();
}

require_once('template.php');

template_header('Score Entry', true, '<script src="res/scores.js"></script>');

if (isset($_SESSION['scores_error'])) {
	echo '<script>alert(';
	echo json_encode($_SESSION['scores_error']);
	echo ');</script>';
	unset($_SESSION['scores_error']);
}

echo '<form id="score_form" action="scores.php" method="post" class="form">';

echo '<input id="csrf_token" name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';

$years = database_query('SELECT "id", "name" FROM "years" ORDER BY "name";');
usort($years, function ($a, $b) {
	return strnatcasecmp($a['name'], $b['name']);
});
echo '<div class="form-element">';
echo '<div class="form-subelement">';
echo '<label for="year">Year:</label>';
echo '</div>';
echo '<div class="form-subelement">';
echo '<select id="year" autofocus="autofocus">';
echo '<option value="" selected="selected" disabled="disabled">-- Select Year --</option>';
foreach ($years as $year) {
	echo '<option value="' . htmlescape($year['id']) . '">' . htmlescape($year['name']) . '</option>';
}
echo '</select>';
echo '</div>';
echo '</div>';

echo '<div class="form-element">';
echo '<div class="form-subelement">';
echo '<label for="competition">Competition:</label>';
echo '</div>';
echo '<div class="form-subelement">';
echo '<select id="competition" disabled="disabled">';
echo '<option value="" selected="selected" disabled="disabled">-- Select Competition --</option>';
echo '</select>';
echo '</div>';
echo '</div>';

echo '<div class="form-element">';
echo '<div class="form-subelement">';
echo '<label for="event">Event:</label>';
echo '</div>';
echo '<div class="form-subelement">';
echo '<select id="event" disabled="disabled">';
echo '<option value="" selected="selected" disabled="disabled">-- Select Event --</option>';
echo '</select>';
echo '</div>';
echo '</div>';

echo '<div class="form-element">';
echo '<div class="form-subelement">';
echo '<label for="club">Club:</label>';
echo '</div>';
echo '<div class="form-subelement">';
echo '<select id="club" disabled="disabled">';
echo '<option value="" selected="selected" disabled="disabled">-- Select Club --</option>';
echo '</select>';
echo '</div>';
echo '</div>';


echo '<div class="form-element">';
echo '<div class="form-subelement">';
echo '<label for="team">Team:</label>';
echo '</div>';
echo '<div class="form-subelement">';
echo '<select id="team" disabled="disabled">';
echo '<option value="" selected="selected" disabled="disabled">-- Select Team --</option>';
echo '</select>';
echo '</div>';
echo '</div>';


echo '<div class="form-element">';
echo '<div class="form-subelement">';
echo '<label for="score">Score:</label>';
echo '</div>';
echo '<div class="form-subelement">';
echo '<input id="score" value="" type="number" min="0" step="0.01" disabled="disabled" />';
echo '</div>';
echo '</div>';

echo '<div class="form-element">';
echo '<div class="form-subelement"></div>';
echo '<div class="form-subelement">';
echo '<input id="submit" type="submit" value="Save" disabled="disabled" />';
echo ' <span id="status"></span>';
echo '</div>';
echo '</div>';

echo '</form>';

template_footer();
