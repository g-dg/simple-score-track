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
		case 'team_create':
			if (isset($_GET['club_id'], $_POST['name'])) {
				try {
					database_query('INSERT INTO "teams" ("club", "name", "competition") VALUES (?, ?, ?);', [(int)$_GET['club_id'], $_POST['name'], (int)$_GET['competition_id']]);
				} catch (Exception $e) {
					$_SESSION['team_manage_error'] = 'An error occurred creating the team. (Check that a team with the same name does not already exist in this club)';
				}
			} else {
				http_response_code(400);
			}
			break;
		case 'team_rename':
			if (isset($_GET['id'], $_POST['name'])) {
				try {
					database_query('UPDATE "teams" SET "name" = ? WHERE "id" = ?;', [$_POST['name'], (int)$_GET['id']]);
				} catch (Exception $e) {
					$_SESSION['team_manage_error'] = 'An error occurred renaming the team. (Check that a team with the same name does not already exist in this club)';
				}
			} else {
				http_response_code(400);
			}
			break;
		case 'team_delete':
			if (isset($_GET['id'])) {
				try {
					database_query('DELETE FROM "teams" WHERE "id" = ?;', [(int)$_GET['id']]);
				} catch (Exception $e) {
					$_SESSION['team_manage_error'] = 'An error occurred deleting the team.';
				}
			} else {
				http_response_code(400);
			}
			break;
		default:
			$_SESSION['team_manage_error'] = 'An error occurred.';
			http_response_code(400);
			exit();
	}
	header('Location: manage_teams.php?competition_id=' . urlencode($_GET['competition_id']));
	exit();
}

require_once('template.php');

template_header('Manage Teams');

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

if (isset($_SESSION['team_manage_error'])) {
	echo '<script>alert(';
	echo json_encode($_SESSION['team_manage_error']);
	echo ');</script>';
	unset($_SESSION['team_manage_error']);
}

$clubs = database_query('SELECT "id", "name" FROM "clubs" WHERE "year" = ? ORDER BY "name";', [(int)$competition_details['year']]);
usort($clubs, function ($a, $b) {
	return strnatcasecmp($a['name'], $b['name']);
});

foreach ($clubs as $club) {
	echo '<h2>';
	echo htmlescape($club['name']);
	echo '</h2>';

	echo '<h3>Create Team</h3>';
	echo '<form action="manage_teams.php?competition_id=' . htmlescape(urlencode($_GET['competition_id'])) . '&amp;action=team_create&amp;club_id=' . htmlescape(urlencode($club['id'])) . '" method="post">';
	echo '<input name="name" value="" type="text" placeholder="Name" maxlength="250" required="required" />';
	echo '<input type="submit" value="Create" />';
	echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
	echo '</form>';

	echo '<h3>Manage Teams</h3>';

	$teams = database_query('SELECT "id", "name" FROM "teams" WHERE "club" = ? AND "competition" = ?;', [(int)$club['id'], (int)$_GET['competition_id']]);
	usort($teams, function ($a, $b) {
		return strnatcasecmp($a['name'], $b['name']);
	});

	echo '<table><tbody>';

	foreach ($teams as $team) {
		echo '<tr>';

		echo '<td>';
		echo '<form action="manage_teams.php?competition_id=' . htmlescape(urlencode($_GET['competition_id'])) . '&amp;action=team_rename&amp;id=' . htmlescape(urlencode($team['id'])) . '" method="post">';
		echo '<input name="name" value="' . htmlescape($team['name']) . '" type="text" placeholder="Name" maxlength="250" required="required" />';
		echo '<input type="submit" value="Rename" />';
		echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
		echo '</form>';
		echo '</td>';

		echo '<td>';
		echo '<form action="manage_teams.php?competition_id=' . htmlescape(urlencode($_GET['competition_id'])) . '&amp;action=team_delete&amp;id=' . htmlescape(urlencode($team['id'])) . '" method="post">';
		echo '<input type="submit" value="Delete" data-name="' . htmlescape($club['name'] . ' - ' . $team['name']) . '" onclick="return confirm(&quot;Really delete the team \\&quot;&quot; + $(this).data(&quot;name&quot;) + &quot;\\&quot;?&quot;);" />';
		echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
		echo '</form>';
		echo '</td>';

		echo '</tr>';
	}

	echo '</tbody></table>';

	echo '<br />';
	echo '<hr />';
}

template_footer();
