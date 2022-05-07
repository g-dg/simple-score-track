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
		case 'event_edit':
			if (isset($_POST['type'], $_POST['overall_point_multiplier'])) {
				$old_event_type = database_query('SELECT "type" FROM "events" WHERE "id" = ?;', [(int)$_GET['event_id']])[0]['type'];
				switch ($_POST['type']) {
					case 'points':
						database_query('UPDATE "events" SET "type" = ?, "overall_point_multiplier" = ? WHERE "id" = ?;', [$_POST['type'], (float)$_POST['overall_point_multiplier'], (int)$_GET['event_id']]);
						break;
					case 'timed':
						database_query('UPDATE "events" SET "type" = ?, "overall_point_multiplier" = ? WHERE "id" = ?;', [$_POST['type'], (float)$_POST['overall_point_multiplier'], (int)$_GET['event_id']]);
						if ($old_event_type != 'timed') {
							database_query('INSERT INTO "timed_event_details" ("event", "min_time", "max_time", "max_points", "max_errors", "correctness_points", "cap_points") VALUES (?, 0, 600, 50, 10, 50, 1);', [(int)$_GET['event_id']]);
						} else {
							if (isset($_POST['min_time'], $_POST['max_time'], $_POST['max_points'], $_POST['max_errors'], $_POST['correctness_points'], $_POST['cap_points'])) {
								database_query('UPDATE "timed_event_details" SET "min_time" = ?, "max_time" = ?, "max_points" = ?, "max_errors" = ?, "correctness_points" = ?, "cap_points" = ? WHERE "event" = ?;', [(int)$_POST['min_time'], (int)$_POST['max_time'], (int)$_POST['max_points'], (int)$_POST['max_errors'], (int)$_POST['correctness_points'], (int)$_POST['cap_points'], (int)$_GET['event_id']]);
							} else {
								http_response_code(400);
								$_SESSION['event_manage_advanced_error'] = 'Could not edit the event.';
							}
						}
						break;
					case 'individual':
						database_query('UPDATE "events" SET "type" = ?, "overall_point_multiplier" = ? WHERE "id" = ?;', [$_POST['type'], (float)$_POST['overall_point_multiplier'], (int)$_GET['event_id']]);
						break;
					default:
						http_response_code(400);
						break;
				}
			} else {
				http_response_code(400);
				$_SESSION['event_manage_advanced_error'] = 'Could not edit the event.';
			}
			break;

		default:
			$_SESSION['event_manage_advanced_error'] = 'An error occurred.';
			http_response_code(400);
			exit();
	}
	header('Location: manage_events_advanced.php?event_id=' . urlencode($_GET['event_id']));
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

echo '<br />';

$event_details = database_query('SELECT "name", "type", "overall_point_multiplier" FROM "events" WHERE "id" = ? ORDER BY "name";', [(int)$_GET['event_id']])[0];
switch ($event_details['type']) {
	case 'points':
		$event_advanced_details = [];
		break;
	case 'timed':
		$event_advanced_details = database_query('SELECT "min_time", "max_time", "max_points", "max_errors", "correctness_points", "cap_points" FROM "timed_event_details" WHERE "event" = ?;', [(int)$_GET['event_id']])[0];
		break;
	case 'individual':
		$event_advanced_details = [];
		break;
}

echo '<form action="manage_events_advanced.php?event_id=' . htmlescape(urlencode($_GET['event_id'])) . '&amp;action=event_edit" method="post">';

echo '<table><tbody>';

echo '<tr>';
echo '<td>Event Type:</td>';
echo '<td>';
echo '<select name="type">';
echo '<option value="points" ' . ($event_details['type'] == 'points' ? 'selected="selected"' : '') . '>Point-based</option>';
echo '<option value="timed" ' . ($event_details['type'] == 'timed' ? 'selected="selected"' : '') . '>Timed</option>';
echo '<option value="individual" ' . ($event_details['type'] == 'individual' ? 'selected="selected"' : '') . '>Individual</option>';
echo '</select>';
echo '</td>';
echo '</tr>';

echo '<tr>';
echo '<td>Overall Point Multiplier:</td>';
echo '<td>';
echo '<input name="overall_point_multiplier" value="' . htmlescape($event_details['overall_point_multiplier']) . '" type="number" min="0" step="0.01" >';
echo '</td>';
echo '</tr>';

switch ($event_details['type']) {
	case 'points':

		break;
	case 'timed':

		echo '<tr>';
		echo '<td>Minimum time (seconds):</td>';
		echo '<td>';
		echo '<input name="min_time" value="' . htmlescape($event_advanced_details['min_time']) . '" type="number" min="0" step="1" >';
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td>Maximum time (seconds):</td>';
		echo '<td>';
		echo '<input name="max_time" value="' . htmlescape($event_advanced_details['max_time']) . '" type="number" min="0" step="1" >';
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td>Maximum points:</td>';
		echo '<td>';
		echo '<input name="max_points" value="' . htmlescape($event_advanced_details['max_points']) . '" type="number" min="0" step="1" >';
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td>Maximum errors:</td>';
		echo '<td>';
		echo '<input name="max_errors" value="' . htmlescape($event_advanced_details['max_errors']) . '" type="number" min="0" step="1" >';
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td>Correctness points:</td>';
		echo '<td>';
		echo '<input name="correctness_points" value="' . htmlescape($event_advanced_details['correctness_points']) . '" type="number" min="0" step="1" >';
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td>Cap points:</td>';
		echo '<td>';
		echo '<select name="cap_points">';
		echo '<option value="1" ' . ($event_advanced_details['cap_points'] != 0 ? 'selected="selected"' : '') . '>Yes</option>';
		echo '<option value="0" ' . ($event_advanced_details['cap_points'] == 0 ? 'selected="selected"' : '') . '>No</option>';
		echo '</select>';
		echo '</td>';
		echo '</tr>';

		break;
	case 'individual':

		break;
}

echo '<tr>';
echo '<td>';
echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
echo '</td>';
echo '<td>';
echo '<input type="submit" value="Apply/Save" />';
echo '</td>';
echo '</tr>';

echo '</tbody></table>';

echo '</form>';

template_footer();
