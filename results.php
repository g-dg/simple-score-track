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
	template_footer();
	exit();
}

function getPointsScore($event_id, $team_id)
{
	$score = database_query('SELECT "points" FROM "point_scores" WHERE "event" = ? AND "team" = ?;', [$event_id, $team_id]);
	if (isset($score[0])) {
		return (float)$score[0]['points'];
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
		return $points;
	} else {
		return null;
	}
}

function getIndividualScore($event_id, $club_id)
{
	$scores = database_query('SELECT "points" FROM "individual_scores" WHERE "event" = ? AND "club" = ?;', [$event_id, $club_id]);
	if (count($scores) > 0) {
		$total = 0.0;
		foreach ($scores as $score) {
			$total += (float)$score['points'];
		}
		return $total / count($scores);
	} else {
		return null;
	}
}

function getOverallScoreForTeam($competition_id, $team_id)
{
	$events = database_query('SELECT "id", "name", "type", "overall_point_multiplier" FROM "events" WHERE "competition" = ?;', [$competition_id]);
	$team = database_query('SELECT "name", "club" FROM "teams" WHERE "id" = ?;', [$team_id])[0];
	if (count($events) > 0) {
		$score = 0.0;
		foreach ($events as $event) {
			switch ($event['type']) {
				case 'points':
					$score += getPointsScore((int)$event['id'], $team_id) * (float)$event['overall_point_multiplier'];
					break;
				case 'timed':
					$score += getTimedScore((int)$event['id'], $team_id) * (float)$event['overall_point_multiplier'];
					break;
				case 'individual':
					$score += getIndividualScore((int)$event['id'], (int)$team['club']) * (float)$event['overall_point_multiplier'];
					break;
			}
		}
		return $score;
	} else {
		return null;
	}
}

function getOverallAverageForClub($competition_id, $club_id)
{
	$teams = database_query('SELECT "id", "name" FROM "teams" WHERE "club" = ? AND "competition" = ?;', [$club_id, $competition_id]);
	$total = 0.0;
	foreach ($teams as $team) {
		$score = getOverallScoreForTeam($competition_id, (int)$team['id']);
		if ($score !== null) {
			$total += $score;
		}
	}
	return $total / count($teams);
}


$competitions = database_query('SELECT "id", "name", "year" FROM "competitions" WHERE "year" = ?;', [(int)$_GET['year_id']]);
if (count($competitions) > 0) {
	foreach ($competitions as $competition) {

		echo '<h1>' . htmlescape($competition['name']) . '</h1>';

		/******************************************************************************/
		// General Statistics

		echo '<h2>General Statistics</h2>';

		$club_count = database_query('SELECT COUNT() FROM "clubs" WHERE "year" = ?;', [(int)$competition['year']])[0][0];
		$team_count = database_query('SELECT COUNT() FROM "teams" WHERE "competition" = ?;', [(int)$competition['id']])[0][0];
		$event_count = database_query('SELECT COUNT() FROM "events" WHERE "competition" = ?;', [(int)$competition['id']])[0][0];


		echo '<dl>';
		/*echo '<dt>Completion:</dt>';
		echo '<dd>';
		if (($team_count * $event_count) > 0 && $competition['type'] != 'individual') {
			echo htmlescape(number_format(round(SCORE_COUNT / ($team_count * $event_count) * 100, 2), 2) . '%');
		} else {
			echo 'N/A';
		}
		echo '</dd>';*/
		echo '<dt>Clubs:</dt>';
		echo '<dd>' . htmlescape($club_count) . '</dd>';
		echo '<dt>Teams:</dt>';
		echo '<dd>' . htmlescape($team_count) . '</dd>';
		echo '<dt>Events:</dt>';
		echo '<dd>' . htmlescape($event_count) . '</dd>';
		echo '</dl>';


		/******************************************************************************/
		// Overall Club Averages

		echo '<h2>Overall Club Averages</h2>';

		$clubs = database_query('SELECT DISTINCT "clubs"."id" AS "id", "clubs"."name" AS "name" FROM "teams" INNER JOIN "clubs" ON "teams"."club" = "clubs"."id" WHERE "teams"."competition" = ? ORDER BY "name";', [(int)$competition['id']]);
		usort($clubs, function ($a, $b) {
			return strnatcasecmp($a['name'], $b['name']);
		});

		$scores = [];
		foreach ($clubs as $club) {
			$score = getOverallAverageForClub((int)$competition['id'], (int)$club['id']);
			if ($score !== null) {
				$scores[] = ['score' => $score, 'club_id' => (int)$club['id'], 'club_name' => $club['name']];
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
			echo '<table class="ranking"><thead><tr><th>Rank</th><th>Club</th><th>Score</th></tr></thead><tbody>';
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
				echo htmlescape(round($score['score'], 2));
				echo '</td></tr>';
			}
			echo '</tbody></table>';
		} else {
			echo '<em>No results.</em><br />';
		}


		/******************************************************************************/
		// Overall Team Rankings

		echo '<h2>Overall Team Rankings</h2>';

		$teams = database_query('SELECT "clubs"."id" AS "club_id", "clubs"."name" AS "club_name", "teams"."id" AS "team_id", "teams"."name" AS "team_name" FROM "teams" INNER JOIN "clubs" ON "teams"."club" = "clubs"."id" WHERE "teams"."competition" = ? ORDER BY "club_name", "team_name";', [(int)$competition['id']]);
		usort($teams, function ($a, $b) {
			return $a['team_id'] == $b['team_id'] ? strnatcasecmp($a['team_name'], $b['team_name']) : strnatcasecmp($a['club_name'], $b['club_name']);
		});

		$scores = [];
		foreach ($teams as $team) {
			$score = getOverallScoreForTeam((int)$competition['id'], (int)$team['team_id']);
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
							$score = getIndividualScore((int)$event['id'], (int)$team['club_id']);
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
