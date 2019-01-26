<?php

require_once('session.php');

// force logon
if (!isset($_SESSION['user_id'])) {
	header('Location: login.php?redirect=results.php');
	exit();
}

require_once('database.php');

require_once('template.php');

template_header('Results');

/******************************************************************************/
// General Stats

echo '<h1>General Statistics</h1>';

/*
SELECT
	"score_entries",
	"club_count",
	"team_count",
	"event_count"
FROM (SELECT COUNT() AS "score_entries" FROM "scores")
JOIN (SELECT COUNT() AS "club_count" FROM "clubs")
JOIN (SELECT COUNT() AS "team_count" FROM "teams")
JOIN (SELECT COUNT() AS "event_count" FROM "events");
 */
$stats = database_query('SELECT "score_entries","club_count","team_count","event_count" FROM (SELECT COUNT() AS "score_entries" FROM "scores") JOIN (SELECT COUNT() AS "club_count" FROM "clubs") JOIN (SELECT COUNT() AS "team_count" FROM "teams") JOIN (SELECT COUNT() AS "event_count" FROM "events");')[0];
echo '<ul>';
echo '<li><strong>Completion: ';
if (((int)$stats['team_count'] * (int)$stats['event_count']) > 0) {
	echo htmlescape(number_format(round(((int)$stats['score_entries'] / ((int)$stats['team_count'] * (int)$stats['event_count'])) * 100, 2), 2) . '%');
} else {
	echo 'N/A';
}
echo '</strong></li>';
echo '<li>Clubs: ' . htmlescape($stats['club_count']) . '</li>';
echo '<li>Teams: ' . htmlescape($stats['team_count']) . '</li>';
echo '<li>Events: ' . htmlescape($stats['event_count']) . '</li>';
echo '</ul><br />';


/******************************************************************************/
// Overall Club Average Rankings

echo '<hr /><h1>Overall Club Average Rankings</h1>';

/*
SELECT
	"clubs"."id" AS "club_id",
	"clubs"."name" AS "club_name",
	IFNULL(AVG("team_totals"."total_points"), 0.0) AS "average_overall_points"
FROM "clubs"
LEFT JOIN (
	SELECT
		"teams"."id" AS "team_id",
		"teams"."name" AS "team_name",
		"teams"."club" AS "club_id",
		TOTAL("scores"."points" * "events"."overall_point_multiplier") AS "total_points"
	FROM "teams"
	LEFT JOIN "scores" ON "scores"."team" = "teams"."id"
	LEFT JOIN "events" ON "events"."id" = "scores"."event"
	GROUP BY "teams"."id"
) AS "team_totals" ON "club_id" = "clubs"."id"
GROUP BY "clubs"."id"
ORDER BY
	"average_overall_points" DESC,
	"club_name";
 */
$scores = database_query('SELECT "clubs"."id" AS "club_id","clubs"."name" AS "club_name",IFNULL(AVG("total_points"),0.0) AS "average_overall_points" FROM "clubs" LEFT JOIN (SELECT "teams"."id" AS "team_id","teams"."name" AS "team_name","teams"."club" AS "club_id",TOTAL("scores"."points"*"events"."overall_point_multiplier") AS "total_points" FROM "teams" LEFT JOIN "scores" ON "scores"."team"="teams"."id" LEFT JOIN "events" ON "events"."id"="scores"."event" GROUP BY "teams"."id") AS "team_totals" ON "club_id"="clubs"."id" GROUP BY "clubs"."id" ORDER BY "average_overall_points" DESC,"club_name";');
if (count($scores) > 0) {
	echo '<table class="ranking"><thead><tr><th>Rank</th><th>Club</th><th>Score</th></tr></thead><tbody>';
	$rank = 1;
	$highest_score = (float)$scores[0]['average_overall_points'];
	foreach ($scores as $score) {
		// check if next rank (i.e. not tied)
		if (round((float)$score['average_overall_points'], RANKING_PRECISION) < round($highest_score, RANKING_PRECISION)) {
			$rank++;
			$highest_score = $score['average_overall_points'];
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
		echo htmlescape(number_format(round($score['average_overall_points'], 2), 2));
		echo '</td></tr>';
	}
	echo '</tbody></table>';
} else {
	echo 'No results.';
}
echo '<br />';


/******************************************************************************/
// Overall Team Rankings

echo '<hr /><h1>Overall Team Rankings</h1>';

/*
SELECT
	"scores"."team" AS "team_id",
	"clubs"."name" AS "club_name",
	"teams"."name" AS "team_name",
	TOTAL("scores"."points" * "events"."overall_point_multiplier") AS "overall_points"
FROM "scores"
INNER JOIN "events" ON "events"."id" = "scores"."event"
INNER JOIN "teams" ON "teams"."id" = "scores"."team"
INNER JOIN "clubs" ON "clubs"."id" = "teams"."club"
GROUP BY "events"."team"
ORDER BY 
	"overall_points" DESC,
	"clubs"."name", "teams"."name";
 */
$scores = database_query('SELECT "scores"."team" AS "team_id", "clubs"."name" AS "club_name", "teams"."name" AS "team_name", TOTAL("scores"."points" * "events"."overall_point_multiplier") AS "overall_points" FROM "scores" INNER JOIN "events" ON "events"."id" = "scores"."event" INNER JOIN "teams" ON "teams"."id" = "scores"."team" INNER JOIN "clubs" ON "clubs"."id" = "teams"."club" GROUP BY "scores"."team" ORDER BY "overall_points" DESC, "clubs"."name", "teams"."name";');
if (count($scores) > 0) {
	echo '<table class="ranking"><thead><tr><th>Rank</th><th>Club</th><th>Team</th><th>Score</th></tr></thead><tbody>';
	$rank = 1;
	$highest_score = (float)$scores[0]['overall_points'];
	foreach ($scores as $score) {
		// check if next rank (i.e. not tied)
		if (round((float)$score['overall_points'], RANKING_PRECISION) < round($highest_score, RANKING_PRECISION)) {
			$rank++;
			$highest_score = $score['overall_points'];
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
		echo htmlescape(round($score['overall_points'], 2));
		echo '</td></tr>';
	}
	echo '</tbody></table>';
} else {
	echo 'No results.<br />';
}
echo '<br />';


/******************************************************************************/
// Team Event Rankings

echo '<hr /><h1>Team Event Rankings</h1>';

/*
SELECT
	"scores"."points" AS "points",
	"teams"."name" AS "team_name",
	"clubs"."name" AS "club_name"
FROM "scores"
INNER JOIN "teams" ON "scores"."team" = "teams"."id"
INNER JOIN "clubs" ON "teams"."club" = "clubs"."id"
WHERE "scores"."event" = ?
ORDER BY
	"scores"."points" DESC,
	"clubs"."name", "teams"."name";
 */
$events = database_query('SELECT "id", "name" FROM "events" ORDER BY "name";');
usort($events, function ($a, $b) {
	return strnatcasecmp($a['name'], $b['name']);
});
if (count($events) > 0) {
	foreach ($events as $event) {
		echo '<h2>' . htmlescape($event['name']) . '</h2>';
		$scores = database_query('SELECT "scores"."points" AS "points", "teams"."name" AS "team_name", "clubs"."name" AS "club_name" FROM "scores" INNER JOIN "teams" ON "scores"."team" = "teams"."id" INNER JOIN "clubs" ON "teams"."club" = "clubs"."id" WHERE "scores"."event" = ? ORDER BY "scores"."points" DESC, "clubs"."name", "teams"."name";', [(int)$event['id']]);
		if (count($scores) > 0) {
			echo '<table class="ranking"><thead><tr><th>Rank</th><th>Club</th><th>Team</th><th>Score</th></tr></thead><tbody>';
			$rank = 1;
			$highest_score = (float)$scores[0]['points'];
			foreach ($scores as $score) {
				// check if next rank (i.e. not tied)
				if (round((float)$score['points'], RANKING_PRECISION) < round($highest_score, RANKING_PRECISION)) {
					$rank++;
					$highest_score = $score['points'];
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
				echo htmlescape(round($score['points'], 2));
				echo '</td></tr>';
			}
			echo '</tbody></table>';
		} else {
			echo 'No results.<br />';
		}
		echo '<br />';
	}
} else {
	echo 'No events.<br />';
}

/******************************************************************************/

template_footer();
