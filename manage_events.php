<?php

require_once('session.php');

require_once('auth.php');

require_once('database.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['action'], $_POST['_csrf_token']) && $_POST['_csrf_token'] === $_SESSION['csrf_token']) {
	switch ($_GET['action']) {
		case 'event_create':
			if (isset($_POST['name'], $_POST['overall_point_multiplier'])) {
				try {
					database_query('INSERT INTO "events" ("name", "overall_point_multiplier") VALUES (?, ?);', [$_POST['name'], (float)$_POST['overall_point_multiplier']]);
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
		case 'event_change_overall_point_multiplier':
			if (isset($_GET['id'], $_POST['overall_point_multiplier'])) {
				try {
					database_query('UPDATE "events" SET "overall_point_multiplier" = ? WHERE "id" = ?;', [(float)$_POST['overall_point_multiplier'], (int)$_GET['id']]);
				} catch (Exception $e) {
					$_SESSION['event_manage_error'] = 'An error occurred changing the points for the event.';
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
	header('Location: manage_events.php');
	exit();
}

require_once('template.php');

template_header('Manage Events');

if (isset($_SESSION['event_manage_error'])) {
	echo '<script>alert(';
	echo json_encode($_SESSION['event_manage_error']);
	echo ');</script>';
	unset($_SESSION['event_manage_error']);
}

echo '<h1>Create Event</h1>';

echo '<form action="manage_events.php?action=event_create" method="post">';
echo '<input name="name" value="" type="text" placeholder="Name" maxlength="255" required="required" />';
echo '<input name="overall_point_multiplier" value="" type="number" placeholder="Overall Point Multiplier" min="0.0" step="0.01" required="required" />';
echo '<input type="submit" value="Create" />';
echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
echo '</form>';

echo '<h1>Manage Events</h1>';
echo '<table><thead><tr><th>Name</th><th>Overall Point Multiplier</th><th></th></tr></thead><tbody>';

$events = database_query('SELECT "id", "name", "overall_point_multiplier" FROM "events" ORDER BY "name";');
usort($events, function ($a, $b) {
	return strnatcasecmp($a['name'], $b['name']);
});

foreach ($events as $event) {
	echo '<tr>';

	echo '<td>';
	echo '<form action="manage_events.php?action=event_rename&amp;id=' . htmlescape(urlencode($event['id'])) . '" method="post">';
	echo '<input name="name" value="' . htmlescape($event['name']) . '" type="text" placeholder="Name" maxlength="255" required="required" />';
	echo '<input type="submit" value="Rename" />';
	echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
	echo '</form>';
	echo '</td>';

	echo '<td>';
	echo '<form action="manage_events.php?action=event_change_overall_point_multiplier&amp;id=' . htmlescape(urlencode($event['id'])) . '" method="post">';
	echo '<input name="overall_point_multiplier" value="' . htmlescape(round($event['overall_point_multiplier'], 2)) . '" placeholder="Overall Point Multiplier" type="number" min="0.0" step="0.01" required="required" />';
	echo '<input type="submit" value="Change" />';
	echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
	echo '</form>';
	echo '</td>';

	echo '<td>';
	echo '<form action="manage_events.php?action=event_delete&amp;id=' . htmlescape(urlencode($event['id'])) . '" method="post">';
	echo '<input type="submit" value="Delete" data-name="' . htmlescape($event['name']) . '" onclick="return confirm(&quot;Really delete the event \\&quot;&quot; + $(this).data(&quot;name&quot;) + &quot;\\&quot;?&quot;);" />';
	echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
	echo '</form>';
	echo '</td>';

	echo '</tr>';
}

echo '</tbody></table>';

template_footer();
