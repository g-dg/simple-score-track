<?php

require_once('session.php');

require_once('auth.php');

require_once('database.php');

if (!isset($_GET['year_id'])) {
	http_response_code(400);
	exit("Year ID must be specified");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['action'], $_POST['_csrf_token']) && $_POST['_csrf_token'] === $_SESSION['csrf_token']) {
	switch ($_GET['action']) {
		case 'competition_create':
			if (isset($_POST['name'], $_POST['overall_point_multiplier'])) {
				try {
					database_query('INSERT INTO "competitions" ("name", "year", "overall_point_multiplier") VALUES (?, ?, ?);', [$_POST['name'], (int)$_GET['year_id'], (float)$_POST['overall_point_multiplier']]);
				} catch (Exception $e) {
					$_SESSION['competition_manage_error'] = 'An error occurred creating the competition. (Check that a competition with the same name does not already exist)';
				}
			} else {
				http_response_code(400);
			}
			break;
		case 'competition_rename':
			if (isset($_GET['id'], $_POST['name'])) {
				try {
					database_query('UPDATE "competitions" SET "name" = ? WHERE "id" = ?;', [$_POST['name'], (int)$_GET['id']]);
				} catch (Exception $e) {
					$_SESSION['competition_manage_error'] = 'An error occurred renaming the competition. (Check that a competition with the same name does not already exist)';
				}
			} else {
				http_response_code(400);
			}
			break;
		case 'competition_change_overall_point_multiplier':
			if (isset($_GET['id'], $_POST['overall_point_multiplier'])) {
				try {
					database_query('UPDATE "competitions" SET "overall_point_multiplier" = ? WHERE "id" = ?;', [(float)$_POST['overall_point_multiplier'], (int)$_GET['id']]);
				} catch (Exception $e) {
					$_SESSION['competition_manage_error'] = 'An error occurred changing the points for the competition.';
				}
			} else {
				http_response_code(400);
			}
			break;
		case 'competition_delete':
			if (isset($_GET['id'])) {
				try {
					database_query('DELETE FROM "competitions" WHERE "id" = ?;', [(int)$_GET['id']]);
				} catch (Exception $e) {
					$_SESSION['competition_manage_error'] = 'An error occurred deleting the competition.';
				}
			} else {
				http_response_code(400);
			}
			break;
		default:
			$_SESSION['competition_manage_error'] = 'An error occurred.';
			http_response_code(400);
			exit();
	}
	header('Location: manage_competitions.php?year_id=' . urlencode($_GET['year_id']));
	exit();
}

require_once('template.php');

template_header('Manage Competitions');

$year_details = database_query('SELECT "name" FROM "years" WHERE "id" = ?;', [(int)$_GET['year_id']])[0];
echo '<div>';
echo '<a href="manage_years.php">Years</a>';
echo ' &gt; ';
echo htmlescape($year_details['name']);
echo '</div>';

if (isset($_SESSION['competition_manage_error'])) {
	echo '<script>alert(';
	echo json_encode($_SESSION['competition_manage_error']);
	echo ');</script>';
	unset($_SESSION['competition_manage_error']);
}

echo '<h2>Create Competition</h2>';

echo '<form action="manage_competitions.php?year_id=' . htmlescape(urlencode($_GET['year_id'])) . '&amp;action=competition_create" method="post">';
echo '<input name="name" value="" type="text" placeholder="Name" maxlength="250" required="required" />';
echo '<input name="overall_point_multiplier" value="1" type="number" placeholder="Overall Point Multiplier" min="0.0" step="0.01" required="required" />';
echo '<input type="submit" value="Create" />';
echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
echo '</form>';

echo '<h2>Manage Competitions</h2>';
echo '<table><thead><tr><th colspan="2">Manage</th><th>Name</th><th>Overall Point Multiplier</th><th></th></tr></thead><tbody>';

$competitions = database_query('SELECT "id", "name", "overall_point_multiplier" FROM "competitions" WHERE "year" = ? ORDER BY "name";', [(int)$_GET['year_id']]);
usort($competitions, function ($a, $b) {
	return strnatcasecmp($a['name'], $b['name']);
});

foreach ($competitions as $competition) {
	echo '<tr>';

	echo '<td>';
	echo '<a href="manage_events.php?competition_id=' . htmlescape(urlencode($competition['id'])) . '">Events</a>';
	echo '</td>';
	echo '<td>';
	echo '<a href="scores_overview.php?competition_id=' . htmlescape(urlencode($competition['id'])) . '">Scores</a>';
	echo '</td>';

	echo '<td>';
	echo '<form action="manage_competitions.php?year_id=' . htmlescape(urlencode($_GET['year_id'])) . '&amp;action=competition_rename&amp;id=' . htmlescape(urlencode($competition['id'])) . '" method="post">';
	echo '<input name="name" value="' . htmlescape($competition['name']) . '" type="text" placeholder="Name" maxlength="250" required="required" />';
	echo '<input type="submit" value="Rename" />';
	echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
	echo '</form>';
	echo '</td>';

	echo '<td>';
	echo '<form action="manage_competitions.php?year_id=' . htmlescape(urlencode($_GET['year_id'])) . '&amp;action=competition_change_overall_point_multiplier&amp;id=' . htmlescape(urlencode($competition['id'])) . '" method="post">';
	echo '<input name="overall_point_multiplier" value="' . htmlescape(round($competition['overall_point_multiplier'], 2)) . '" placeholder="Overall Point Multiplier" type="number" min="0.0" step="0.01" required="required" />';
	echo '<input type="submit" value="Change" />';
	echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
	echo '</form>';
	echo '</td>';

	echo '<td>';
	echo '<form action="manage_competitions.php?year_id=' . htmlescape(urlencode($_GET['year_id'])) . '&amp;action=competition_delete&amp;id=' . htmlescape(urlencode($competition['id'])) . '" method="post">';
	echo '<input type="submit" value="Delete" data-name="' . htmlescape($competition['name']) . '" onclick="return confirm(&quot;Really delete the competition \\&quot;&quot; + $(this).data(&quot;name&quot;) + &quot;\\&quot;?&quot;);" />';
	echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
	echo '</form>';
	echo '</td>';

	echo '</tr>';
}

echo '</tbody></table>';

template_footer();
