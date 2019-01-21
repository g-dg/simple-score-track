<?php

require_once('session.php');

// force logon
if (!isset($_SESSION['user_id'])) {
	header('Location: login.php?redirect=export_json.php');
	exit();
}

require_once('database.php');

// disable caching
header('Cache-Control: no-store, no-cache, must-revalidate, proxy-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// set filetype
header('Content-Type: application/json');
if (!isset($_GET['nodownload'])) {
	header('Content-Disposition: attachment; filename="' . (isset($_GET['name']) ? preg_replace('/[^0-9A-Za-z\_\-]/', '_', $_GET['name']) : 'data') . '.json"');
}

$json = [];

// get clubs
$json['clubs'] = [];
foreach (database_query('SELECT * FROM "clubs";') as $record) {
	$json['clubs'][] = [
		'id' => (int)$record['id'],
		'name' => $record['name']
	];
}

// get teams
$json['teams'] = [];
foreach (database_query('SELECT * FROM "teams";') as $record) {
	$json['teams'][] = [
		'id' => (int)$record['id'],
		'club' => (int)$record['club'],
		'name' => $record['name']
	];
}

// get events
$json['events'] = [];
foreach (database_query('SELECT * FROM "events";') as $record) {
	$json['events'][] = [
		'id' => (int)$record['id'],
		'name' => $record['name'],
		"overall_point_multiplier" => round((float)$record['overall_point_multiplier'], 2)
	];
}

// get scores
$json['scores'] = [];
foreach (database_query('SELECT * FROM "scores";') as $record) {
	$json['scores'][] = [
		'team' => (int)$record['team'],
		'event' => (int)$record['event'],
		"points" => round((float)$record['points'], 2)
	];
}

echo json_encode($json);
