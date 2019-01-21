<?php

require_once('session.php');

// force logon
if (!isset($_SESSION['user_id'])) {
	header('Location: login.php?redirect=scores.php');
	exit();
}

require_once('database.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['action'], $_POST['_csrf_token']) && $_POST['_csrf_token'] === $_SESSION['csrf_token']) {
	switch ($_GET['action']) {
		case 'score_update':
			if (isset($_GET['team_id'], $_GET['event_id'], $_POST['score'])) {
				try {
					// check if there is a score to insert
					if ($_POST['score'] !== '') {
						// input the score
						database_query('INSERT INTO "scores" ("team", "event", "points") VALUES (?, ?, ?);', [(int)$_GET['team_id'], (int)$_GET['event_id'], (float)$_POST['score']]);
					} else {
						// clear the score
						database_query('DELETE FROM "scores" WHERE "team" = ? AND "event" = ?;', [(int)$_GET['team_id'], (int)$_GET['event_id']]);
					}
				} catch (Exception $e) {
					$_SESSION['scores_error'] = 'Could not update score.';
				}
			} else {
				http_response_code(400);
			}
			break;
		case 'score_delete_all':
			if (isset($_POST['confirm_code'])) {
				if ($_POST['confirm_code'] === $_SESSION['confirmation_code']) {
					try {
						database_query('DELETE FROM "scores";');
						$_SESSION['scores_error'] = 'All scores deleted.';
					} catch (Exception $e) {
						$_SESSION['scores_error'] = 'An error occurred while deleting the scores.';
					}
				} else {
					$_SESSION['scores_error'] = 'Confirmation code incorrect, scores not deleted';
				}
			} else {
				http_response_code(400);
			}
			break;
		default:
			$_SESSION['scores_error'] = 'An error occurred.';
			http_response_code(400);
	}
	header('Location: scores.php');
	exit();
}

require_once('template.php');

template_header('Add/Update Scores');

if (isset($_SESSION['scores_error'])) {
	echo '<script>alert(';
	echo json_encode($_SESSION['scores_error']);
	echo ');</script>';
	unset($_SESSION['scores_error']);
}

echo '<h2>Add/Update Scores</h2>';

$events = database_query('SELECT "id", "name" FROM "events" ORDER BY "name";');
usort($events, function ($a, $b) {
	return strnatcasecmp($a['name'], $b['name']);
});
$teams = database_query('SELECT "teams"."id" AS "id", "clubs"."name" AS "club_name", "teams"."name" AS "team_name" FROM "teams" INNER JOIN "clubs" ON "teams"."club" = "clubs"."id" ORDER BY "clubs"."name", "teams"."name";');
usort($teams, function ($a, $b) {
	$cmp = strnatcasecmp($a['club_name'], $b['club_name']);
	if ($cmp == 0) {
		return strnatcasecmp($a['team_name'], $b['team_name']);
	} else {
		return $cmp;
	}
});

echo '<table class="scores"><thead><tr><th></th>';
foreach ($events as $event) {
	echo '<th>';
	echo htmlescape($event['name']);
	echo '</th>';
}
echo '</thead><tbody>';

foreach ($teams as $team) {
	echo '<tr>';

	echo '<td>';
	echo htmlescape($team['club_name'] . ' - ' . $team['team_name']);
	echo '</td>';

	foreach ($events as $event) {
		$score = database_query('SELECT "points" FROM "scores" WHERE "team" = ? AND "event" = ?;', [$team['id'], $event['id']]);
		if (isset($score[0])) {
			$score = round($score[0]['points'], 2);
			if ($score == 0) {
				echo '<td class="zero">';
			} else {
				echo '<td class="score">';
			}
		} else {
			$score = '';
			echo '<td class="empty">';
		}

		echo '<form action="scores.php?action=score_update&amp;team_id=' . htmlescape(urlencode($team['id'])) . '&amp;event_id=' . htmlescape(urlencode($event['id'])) . '" method="post">';
		echo '<input name="score" value="' . htmlescape($score) . '" type="number" min="0" step="0.01" />';
		echo '<input type="submit" value="Save" />';
		echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
		echo '</form>';

		echo '</td>';
	}

	echo '</tr>';
}

echo '</tbody></table>';

// generate the confirmation code
$_SESSION['confirmation_code'] = '';
for ($i = 0; $i < 8; $i++) {
	$_SESSION['confirmation_code'] .= (string)mt_rand(0, 9);
}

echo '<h2>Delete All Scores</h2>';
echo '<form action="scores.php?action=score_delete_all" method="post">';
echo 'To delete all scores, enter the following code in the textbox:';
echo ' <code>';
echo htmlescape($_SESSION['confirmation_code']);
echo '</code> ';
echo '<br />';
echo '<input name="confirm_code" type="text" placeholder="Confirmation Code" required="required" />';
echo '<input type="submit" value="Delete All Scores" onclick="return confirm(&quot;Really delete **ALL** the scores?&quot;);" />';
echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
echo '</form>';

template_footer();
