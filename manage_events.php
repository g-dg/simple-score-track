<?php

require_once('session.php');

require_once('auth.php');

require_once('database.php');

if (!isset($_GET['competition_id'])) {
	http_response_code(400);
	exit("Competition ID must be specified");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['action'], $_POST['_csrf_token']) && $_POST['_csrf_token'] === $_SESSION['csrf_token']) {
	switch ($_GET['action']) {
		case 'event_create':
			if (isset($_POST['name'], $_POST['type']) && ($_POST['type'] === 'points' || $_POST['type'] === 'timed' || $_POST['type'] === 'individual')) {
				try {
					database_query('INSERT INTO "events" ("name", "competition", "type", "overall_point_multiplier") VALUES (?, ?, ?, 1);', [$_POST['name'], (int)$_GET['competition_id'], $_POST['type']]);
					$event_id = (int)database_query('SELECT last_insert_rowid();')[0][0];
					switch ($_POST['type']) {
						case 'points':

							break;
						case 'timed':
							database_query('INSERT INTO "timed_event_details" ("event", "min_time", "max_time", "max_points", "error_penalty_time", "error_exponent", "cap_points") VALUES (?, 0, 600, 100, 30, 2, 1);', [$event_id]);
							break;
						case 'individual':

							break;
					}
				} catch (Exception $e) {
					$_SESSION['event_manage_error'] = 'An error occurred creating the event. (Check that an event with the same name does not already exist)';
				}
			} else {
				http_response_code(400);
			}
			break;
		case 'event_rename':
			if (isset($_GET['id'], $_POST['name'])) {
				try {
					database_query('UPDATE "events" SET "name" = ? WHERE "id" = ?;', [$_POST['name'], (int)$_GET['id']]);
				} catch (Exception $e) {
					$_SESSION['event_manage_error'] = 'An error occurred renaming the event. (Check that an event with the same name does not already exist)';
				}
			} else {
				http_response_code(400);
			}
			break;
		case 'event_change_type':
			if (isset($_GET['id'], $_POST['type'])) {
				try {
					database_query('UPDATE "events" SET "type" = ? WHERE "id" = ?;', [$_POST['type'], (int)$_GET['id']]);
					switch ($_POST['type']) {
						case 'points':
							database_query('DELETE FROM "timed_event_details" WHERE "event" = ?;', [(int)$_GET['id']]);
							break;
						case 'timed':
							database_query('INSERT INTO "timed_event_details" ("event", "min_time", "max_time", "max_points", "error_penalty_time", "error_exponent", "cap_points") VALUES (?, 0, 600, 100, 30, 2, 1);', [$event_id]);
							break;
						case 'individual':
							database_query('DELETE FROM "timed_event_details" WHERE "event" = ?;', [(int)$_GET['id']]);
							break;
					}
				} catch (Exception $e) {
					$_SESSION['event_manage_error'] = 'An error occurred changing the type of event.';
				}
			} else {
				http_response_code(400);
			}
			break;
		case 'event_delete':
			if (isset($_GET['id'])) {
				try {
					database_query('DELETE FROM "events" WHERE "id" = ?;', [(int)$_GET['id']]);
				} catch (Exception $e) {
					$_SESSION['event_manage_error'] = 'An error occurred deleting the event.';
				}
			} else {
				http_response_code(400);
			}
			break;
		default:
			$_SESSION['event_manage_error'] = 'An error occurred.';
			http_response_code(400);
			exit();
	}
	header('Location: manage_events.php?competition_id=' . urlencode($_GET['competition_id']));
	exit();
}

require_once('template.php');

template_header('Manage Events');

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
echo '</div>';

if (isset($_SESSION['event_manage_error'])) {
	echo '<script>alert(';
	echo json_encode($_SESSION['event_manage_error']);
	echo ');</script>';
	unset($_SESSION['event_manage_error']);
}

echo '<h2>Create Event</h2>';

echo '<form action="manage_events.php?competition_id=' . htmlescape(urlencode($_GET['competition_id'])) . '&amp;action=event_create" method="post">';
echo '<input name="name" value="" type="text" placeholder="Name" maxlength="250" required="required" />';
echo '<select name="type" required="required">';
echo '<option value="" disabled="disabled" selected="selected">-- Select Event Type --</option>';
echo '<option value="points">Point-based</option>';
echo '<option value="timed">Timed</option>';
echo '<option value="individual">Individual</option>';
echo '</select>';
echo '<input type="submit" value="Create" />';
echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
echo '</form>';

echo '<h2>Manage Events</h2>';
echo '<table><thead><tr><th>Name</th><th>Type</th><th></th><th></th></tr></thead><tbody>';

$events = database_query('SELECT "id", "name", "type" FROM "events" WHERE "competition" = ? ORDER BY "name";', [(int)$_GET['competition_id']]);
usort($events, function ($a, $b) {
	return strnatcasecmp($a['name'], $b['name']);
});

foreach ($events as $event) {
	echo '<tr>';

	echo '<td>';
	echo '<form action="manage_events.php?competition_id=' . htmlescape(urlencode($_GET['competition_id'])) . '&amp;action=event_rename&amp;id=' . htmlescape(urlencode($event['id'])) . '" method="post">';
	echo '<input name="name" value="' . htmlescape($event['name']) . '" type="text" placeholder="Name" maxlength="250" required="required" />';
	echo '<input type="submit" value="Rename" />';
	echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
	echo '</form>';
	echo '</td>';

	echo '<td>';
	echo '<form action="manage_events.php?competition_id=' . htmlescape(urlencode($_GET['competition_id'])) . '&amp;action=event_change_type&amp;id=' . htmlescape(urlencode($event['id'])) . '" method="post">';
	echo '<select name="type">';
	echo '<option value="points" ' . ($event['type'] == 'points' ? 'selected="selected"' : '') . '>Point-based</option>';
	echo '<option value="timed" ' . ($event['type'] == 'timed' ? 'selected="selected"' : '') . '>Timed</option>';
	echo '<option value="individual" ' . ($event['type'] == 'individual' ? 'selected="selected"' : '') . '>Individual</option>';
	echo '</select>';
	echo '<input type="submit" value="Change Type" />';
	echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
	echo '</form>';
	echo '</td>';

	echo '<td>';
	echo '<a href="manage_events_advanced.php?event_id=' . htmlescape(urlencode($event['id'])) . '">Advanced</a>';
	echo '</td>';

	echo '<td>';
	echo '<form action="manage_events.php?competition_id=' . htmlescape(urlencode($_GET['competition_id'])) . '&amp;action=event_delete&amp;id=' . htmlescape(urlencode($event['id'])) . '" method="post">';
	echo '<input type="submit" value="Delete" data-name="' . htmlescape($event['name']) . '" onclick="return confirm(&quot;Really delete the event \\&quot;&quot; + $(this).data(&quot;name&quot;) + &quot;\\&quot;?&quot;);" />';
	echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
	echo '</form>';
	echo '</td>';

	echo '</tr>';
}

echo '</tbody></table>';

template_footer();
