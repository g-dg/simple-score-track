<?php

require_once('session.php');

require_once('auth.php');

require_once('database.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['year_id'])) {
	header('Location: results.php?year_id=' . urlencode($_POST['year_id']));
	exit();
}

require_once('template.php');

template_header('Results');

$years = database_query('SELECT "id", "name" FROM "years" ORDER BY "name";');
usort($years, function ($a, $b) {
	return strnatcasecmp($a['name'], $b['name']);
});
echo '<form action="results.php" method="post">';
echo '<select name="year_id" required="required">';
echo '<option value="" ' . (!isset($_GET['year_id']) ? 'selected="selected"' : '') . ' disabled="disabled">-- Select Year --</option>';
foreach ($years as $year) {
	echo '<option value="' . htmlescape($year['id']) . '" ' . (isset($_GET['year_id']) && $year['id'] == (int)$_GET['year_id'] ? 'selected="selected"' : '') . '>' . htmlescape($year['name']) . '</option>';
}
echo '</select>';
echo '<input type="submit" value="Apply" />';
echo '</form>';
echo '<br />';

if (!isset($_GET['year_id'])) {
	exit();
}

function getPointsScore($event_id, $team_id)
{
	$score = database_query('SELECT "points" FROM "point_scores" WHERE "event" = ? AND "team" = ?;', [$event_id, $team_id]);
	if (isset($score[0])) {
		return round((float)$score[0]['points'], 2);
	} else {
		return null;
	}
}

function getTimedScore($event_id, $team_id)
{
	$score = database_query('SELECT "time", "errors" FROM "timed_scores" WHERE "event" = ? AND "team" = ?;', [$event_id, $team_id]);
	if (isset($score[0])) {
		$score = $score[0];
		$event_details = database_query('SELECT "min_time", "max_time", "max_points", "error_penalty_time", "error_exponent", "cap_points" FROM "timed_event_details" WHERE "event" = ?;', [$event_id])[0];
		$penalty_time = pow((float)$score['errors'], (float)$event_details['error_exponent']) * (int)$event_details['error_penalty_time']; // calculate penalty time
		$adj_time = (float)$score['time'] + $penalty_time; // calculate adjusted time
		$points = ($adj_time - (int)$event_details['max_time']) * ((int)$event_details['max_points']) / ((int)$event_details['min_time'] - (int)$event_details['max_time']); // calculate points
		$points = max($points, 0); // ensure points is never less than 0
		if ($event_details['cap_points'] != 0) {
			$points = min($points, (int)$event_details['max_points']); // cap points if required
		}
		return round($points, 2);
	} else {
		return null;
	}
}

function getIndividualScore($event_id, $team_id)
{
	$club_id = (int)database_query('SELECT "club" FROM "teams" WHERE "id" = ?;', [$team_id])[0]['club'];
	$scores = database_query('SELECT "points" FROM "individual_scores" WHERE "event" = ? AND "club" = ?;', [$event_id, $club_id]);
	if (count($scores) > 0) {
		$total = 0.0;
		foreach ($scores as $score) {
			$total += (float)$score['points'];
		}
		return round($total / count($scores), 2);
	} else {
		return null;
	}
}


$competitions = database_query('SELECT "id", "name" FROM "competitions" WHERE "year" = ?;', [(int)$_GET['year_id']]);
if (count($competitions) > 0) {
	foreach ($competitions as $competition) {

		echo '<h1>' . htmlescape($competition['name']) . '</h1>';

		/******************************************************************************/
		// Event Rankings

		echo '<h2>Event Rankings</h2>';

		$events = database_query('SELECT "id", "name", "type" FROM "events" WHERE "competition" = ? ORDER BY "name";', [(int)$competition['id']]);
		usort($events, function ($a, $b) {
			return strnatcasecmp($a['name'], $b['name']);
		});

		$teams = database_query('SELECT "clubs"."id" AS "club_id", "clubs"."name" AS "club_name", "teams"."id" AS "team_id", "teams"."name" AS "team_name" FROM "teams" INNER JOIN "clubs" ON "teams"."club" = "clubs"."id" WHERE "teams"."competition" = ? ORDER BY "club_name", "team_name";', [(int)$competition['id']]);
		usort($teams, function ($a, $b) {
			return $a['team_id'] == $b['team_id'] ? strnatcasecmp($a['team_name'], $b['team_name']) : strnatcasecmp($a['club_name'], $b['club_name']);
		});

		if (count($events) > 0) {
			foreach ($events as $event) {
				echo '<h3>' . htmlescape($event['name']) . '</h3>';

				$scores = [];
				foreach ($teams as $team) {
					$score = null;
					switch ($event['type']) {
						case 'points':
							$score = getPointsScore((int)$event['id'], (int)$team['team_id']);
							break;
						case 'timed':
							$score = getTimedScore((int)$event['id'], (int)$team['team_id']);
							break;
						case 'individual':
							$score = getIndividualScore((int)$event['id'], (int)$team['team_id']);
							break;
					}
					if ($score !== null) {
						$scores[] = ['score' => $score, 'club_id' => (int)$team['club_id'], 'club_name' => $team['club_name'], 'team_id' => (int)$team['team_id'], 'team_name' => $team['team_name']];
					}
				}

				usort($scores, function ($a, $b) {
					if ($a['score'] < $b['score']) {
						return 1;
					} else if ($a['score'] > $b['score']) {
						return -1;
					} else {
						return 0;
					}
				});

				if (count($scores) > 0) {
					echo '<table class="ranking"><thead><tr><th>Rank</th><th>Club</th><th>Team</th><th>Score</th></tr></thead><tbody>';
					$rank = 1;
					$highest_score = (float)$scores[0]['score'];
					foreach ($scores as $score) {
						// check if next rank (i.e. not tied)
						if (round((float)$score['score'], RANKING_PRECISION) < round($highest_score, RANKING_PRECISION)) {
							$rank++;
							$highest_score = $score['score'];
						}
						switch ($rank) {
							case 1:
								echo '<tr class="first-place">';
								break;
							case 2:
								echo '<tr class="second-place">';
								break;
							case 3:
								echo '<tr class="third-place">';
								break;
							default:
								echo '<tr>';
						}
						echo '<td>';
						echo $rank;
						echo '</td><td>';
						echo htmlescape($score['club_name']);
						echo '</td><td>';
						echo htmlescape($score['team_name']);
						echo '</td><td>';
						echo htmlescape(round($score['score'], 2));
						echo '</td></tr>';
					}
					echo '</tbody></table>';
				} else {
					echo '<em>No results.</em><br />';
				}
			}
		} else {
			echo '<em>No events.</em><br />';
		}
		echo '<br /><hr />';

		/******************************************************************************/
	}
} else {
	echo '<em>No competitions</em><br />';
}

template_footer();
