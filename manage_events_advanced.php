<?php

require_once('session.php');

require_once('auth.php');

require_once('database.php');

if (!isset($_GET['event_id'])) {
	http_response_code(400);
	exit("Event ID must be specified");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['action'], $_POST['_csrf_token']) && $_POST['_csrf_token'] === $_SESSION['csrf_token']) {
	switch ($_GET['action']) {
		
		default:
			$_SESSION['event_manage_advanced_error'] = 'An error occurred.';
			http_response_code(400);
			exit();
	}
	header('Location: manage_events_advanced.php?competition_id=' . urlencode($_GET['event_id']));
	exit();
}

require_once('template.php');

template_header('Advanced - Manage Events');

$event_details = database_query('SELECT "name", "competition" FROM "events" WHERE "id" = ?;', [(int)$_GET['event_id']])[0];
$competition_details = database_query('SELECT "name", "year" FROM "competitions" WHERE "id" = ?;', [$event_details['competition']])[0];
$year_details = database_query('SELECT "name" FROM "years" WHERE "id" = ?;', [$competition_details['year']])[0];
echo '<div>';
echo '<a href="manage_years.php">Years</a>';
echo ' &gt; ';
echo htmlescape($year_details['name']);
echo ' &gt; ';
echo '<a href="manage_competitions.php?year_id=' . htmlescape(urlencode($competition_details['year'])) . '">Competitions</a>';
echo ' &gt; ';
echo htmlescape($competition_details['name']);
echo ' &gt; ';
echo '<a href="manage_events.php?competition_id=' . htmlescape(urlencode($event_details['competition'])) . '">Events</a>';
echo ' &gt; ';
echo htmlescape($event_details['name']);
echo '</div>';

if (isset($_SESSION['event_manage_advanced_error'])) {
	echo '<script>alert(';
	echo json_encode($_SESSION['event_manage_advanced_error']);
	echo ');</script>';
	unset($_SESSION['event_manage_advanced_error']);
}

template_footer();
