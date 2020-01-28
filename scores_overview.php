<?php

require_once('session.php');

require_once('auth.php');

require_once('database.php');

if (!isset($_GET['competition_id'])) {
	http_response_code(400);
	exit("Competition ID must be specified");
}

require_once('template.php');

template_header('Scores Overview');

$competition_details = database_query('SELECT "name", "year" FROM "competitions" WHERE "id" = ?;', [(int)$_GET['competition_id']])[0];
$year_details = database_query('SELECT "name" FROM "years" WHERE "id" = ?;', [$competition_details['year']])[0];
echo '<div>';
echo '<a href="manage_years.php">Years</a>';
echo ' &gt; ';
echo htmlescape($year_details['name']);
echo ' &gt; ';
echo '<a href="manage_competitions.php?year_id=' . htmlescape(urlencode($competition_details['year'])) . '">Competitions</a>';
echo ' &gt; ';
echo htmlescape($competition_details['name']);
echo '</div><br />';

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

$events = database_query('SELECT "id", "name", "type" FROM "events" WHERE "competition" = ? ORDER BY "name";', [(int)$_GET['competition_id']]);
usort($events, function ($a, $b) {
	return strnatcasecmp($a['name'], $b['name']);
});

$teams = database_query('SELECT "teams"."id" AS "id", "clubs"."id" AS "club_id", "clubs"."name" AS "club_name", "teams"."name" AS "team_name" FROM "teams" INNER JOIN "clubs" ON "teams"."club" = "clubs"."id" WHERE "teams"."competition" = ? ORDER BY "clubs"."name", "teams"."name";', [(int)$_GET['competition_id']]);
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

$present_score_entries = 0;
$missing_score_entries = 0;

foreach ($teams as $team) {
	echo '<tr>';

	echo '<td>';
	echo htmlescape($team['club_name'] . ' - ' . $team['team_name']);
	echo '</td>';

	foreach ($events as $event) {
		switch ($event['type']) {
			case 'points':
				$score = database_query('SELECT "points" FROM "point_scores" WHERE "team" = ? AND "event" = ?;', [(int)$team['id'], (int)$event['id']]);
				if (isset($score[0])) {
					$present_score_entries++;
					$score = round($score[0]['points'], 2);
					if ($score == 0) {
						echo '<td class="zero">0</td>';
					} else {
						echo '<td class="score">' . htmlescape($score) . '</td>';
					}
				} else {
					$missing_score_entries++;
					echo '<td class="empty"></td>';
				}
				break;
			case 'timed':
				$score = database_query('SELECT "time", "errors" FROM "timed_scores" WHERE "team" = ? AND "event" = ?;', [(int)$team['id'], (int)$event['id']]);
				if (isset($score[0])) {
					$present_score_entries++;
					$minutes = floor((float)$score[0]['time'] / 60);
					$seconds = (float)$score[0]['time'] - ($minutes * 60);
					echo '<td class="score">' . htmlescape($minutes) . ':' . htmlescape(sprintf('%06.3f', round($seconds, 3))) . '; ' . htmlescape(round($score[0]['errors'], 2)) . '</td>';
				} else {
					$missing_score_entries++;
					echo '<td class="empty"></td>';
				}
				break;
			case 'individual':
				$score = getIndividualScore((int)$event['id'], (int)$team['club_id']);
				if ($score !== null) {
					$present_score_entries++;
					echo '<td class="score">' . htmlescape(round($score, 2)) . '</td>';
				} else {
					$missing_score_entries++;
					echo '<td class="empty"></td>';
				}
				break;
		}
	}

	echo '</tr>';
}

echo '</tbody></table>';

echo '<p>Scores entered: ' . htmlescape($present_score_entries) . ' out of ' . htmlescape($present_score_entries + $missing_score_entries) . ' (' . (($present_score_entries + $missing_score_entries) > 0 ? htmlescape(round(($present_score_entries / ($present_score_entries + $missing_score_entries)) * 100, 2)) : '0') . '%)</p>';

template_footer();
