<?php

require_once('session.php');

// force logon
if (!isset($_SESSION['user_id'])) {
	header('Location: login.php?redirect=scores.php');
	exit();
}

require_once('database.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'], $_POST['_csrf_token']) && $_POST['_csrf_token'] === $_SESSION['csrf_token']) {
	switch ($_POST['action']) {
		case 'get_teams':
			if (isset($_POST['club_id'])) {
				$teams = [];
				foreach (database_query('SELECT "id", "name" FROM "teams" WHERE "club" = ?', [(int)$_POST['club_id']]) as $team) {
					$teams[] = ['id' => (int)$team['id'], 'name' => $team['name']];
				}
				echo json_encode($teams);
			} else {
				http_response_code(400);
			}
			exit();
			break;
		case 'get_score':
			if (isset($_POST['team_id'], $_POST['event_id'])) {
				$score = database_query('SELECT "points" FROM "scores" WHERE "team" = ? AND "event" = ?;', [(int)$_POST['team_id'], (int)$_POST['event_id']]);
				if (isset($score[0])) {
					echo round($score[0]['points'], 2);
				} else {
					echo '';
				}
			} else {
				http_response_code(400);
			}
			exit();
			break;
		case 'update_score':
			if (isset($_POST['team_id'], $_POST['event_id'], $_POST['score'])) {
				// check if there is a score to insert
				if ($_POST['score'] !== '') {
					// input the score
					database_query('INSERT INTO "scores" ("team", "event", "points") VALUES (?, ?, ?);', [(int)$_POST['team_id'], (int)$_POST['event_id'], (float)$_POST['score']]);
				} else {
					// clear the score
					database_query('DELETE FROM "scores" WHERE "team" = ? AND "event" = ?;', [(int)$_POST['team_id'], (int)$_POST['event_id']]);
				}
			} else {
				http_response_code(400);
			}
			exit();
			break;
		default:
			$_SESSION['scores_error'] = 'An error occurred.';
			http_response_code(400);
			exit();
	}
	header('Location: scores.php');
	exit();
}

require_once('template.php');

template_header('Score Entry', true, '<script src="scores.js"></script>');

if (isset($_SESSION['scores_error'])) {
	echo '<script>alert(';
	echo json_encode($_SESSION['scores_error']);
	echo ');</script>';
	unset($_SESSION['scores_error']);
}

echo '<form id="score_form" action="scores.php" method="post" class="form">';

echo '<input id="csrf_token" name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';

$events = database_query('SELECT "id", "name" FROM "events" ORDER BY "name";');
usort($events, function ($a, $b) {
	return strnatcasecmp($a['name'], $b['name']);
});
echo '<div class="form-element">';
echo '<div class="form-subelement">';
echo '<label for="event">Event:</label>';
echo '</div>';
echo '<div class="form-subelement">';
echo '<select id="event" autofocus="autofocus">';
echo '<option value="" selected="selected" disabled="disabled">-- Select Event --</option>';
foreach ($events as $event) {
	echo '<option value="' . htmlescape($event['id']) . '">' . htmlescape($event['name']) . '</option>';
}
echo '</select>';
echo '</div>';
echo '</div>';


$clubs = database_query('SELECT "id", "name" FROM "clubs" ORDER BY "name";');
usort($clubs, function ($a, $b) {
	return strnatcasecmp($a['name'], $b['name']);
});
echo '<div class="form-element">';
echo '<div class="form-subelement">';
echo '<label for="club">Club:</label>';
echo '</div>';
echo '<div class="form-subelement">';
echo '<select id="club">';
echo '<option value="" selected="selected" disabled="disabled">-- Select Club --</option>';
foreach ($clubs as $club) {
	echo '<option value="' . htmlescape($club['id']) . '">' . htmlescape($club['name']) . '</option>';
}
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
