<?php

require_once('session.php');

require_once('auth.php');

require_once('database.php');

if (!isset($_GET['competition_id'])) {
	http_response_code(400);
	exit();
}

// disable caching
header('Cache-Control: no-store, no-cache, must-revalidate, proxy-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// set filetype
header('Content-Type: text/csv');
if (!isset($_GET['nodownload'])) {
	header('Content-Disposition: attachment; filename="' . (isset($_GET['name']) ? preg_replace('/[^0-9A-Za-z\_\-]/', '_', $_GET['name']) : 'data') . '.csv"');
}

$events = database_query('SELECT "id" AS "event_id", "name" AS "event_name" FROM "events" WHERE "competition" = ? AND "type" = "points" ORDER BY "event_name";', [(int)$_GET['competition_id']]);
$teams = database_query('SELECT "teams"."club" AS "club_id", "clubs"."name" AS "club_name", "teams"."id" AS "team_id", "teams"."name" AS "team_name" FROM "teams" INNER JOIN "clubs" ON "clubs"."id" = "teams"."club" WHERE "teams"."competition" = ? ORDER BY "club_name", "team_name";', [(int)$_GET['competition_id']]);

$fh = fopen('php://output', 'w');

// generate the header
$csv_header = ['Club', 'Team'];
foreach ($events as $event) {
	$csv_header[] = $event['event_name'];
}
// output the header
fputcsv($fh, $csv_header);

// iterate through each team
foreach ($teams as $team) {
	$csv_record = [$team['club_name'], $team['team_name']];
	// iterate through the events
	foreach ($events as $event) {
		$score = database_query('SELECT "points" FROM "point_scores" WHERE "team" = ? AND "event" = ?;', [$team['team_id'], $event['event_id']]);
		if (count($score) > 0) {
			$score = round((float)$score[0]['points'], 2);
		} else {
			$score = '';
		}
		$csv_record[] = $score;
	}
	fputcsv($fh, $csv_record);
}

fclose($fh);
