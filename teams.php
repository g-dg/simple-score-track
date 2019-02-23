<?php

require_once('session.php');

// force logon
if (!isset($_SESSION['user_id'])) {
	header('Location: login.php?redirect=teams.php');
	exit();
}

require_once('database.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['action'], $_POST['_csrf_token']) && $_POST['_csrf_token'] === $_SESSION['csrf_token']) {
	switch ($_GET['action']) {
		case 'club_create':
			if (isset($_POST['name'])) {
				try {
					database_query('INSERT INTO "clubs" ("name") VALUES (?);', [$_POST['name']]);
				} catch (Exception $e) {
					$_SESSION['team_manage_error'] = 'An error occurred creating the club. (Check that a club with the same name does not already exist)';
				}
			} else {
				http_response_code(400);
			}
			break;
		case 'club_rename':
			if (isset($_GET['id'], $_POST['name'])) {
				try {
					database_query('UPDATE "clubs" SET "name" = ? WHERE "id" = ?;', [$_POST['name'], (int)$_GET['id']]);
				} catch (Exception $e) {
					$_SESSION['team_manage_error'] = 'An error occurred renaming the club. (Check that a club with the same name does not already exist)';
				}
			} else {
				http_response_code(400);
			}
			break;
		case 'club_delete':
			if (isset($_GET['id'])) {
				try {
					database_query('DELETE FROM "clubs" WHERE "id" = ?;', [(int)$_GET['id']]);
				} catch (Exception $e) {
					$_SESSION['team_manage_error'] = 'An error occurred deleting the club.';
				}
			} else {
				http_response_code(400);
			}
			break;
		case 'team_create':
			if (isset($_GET['club_id'], $_POST['name'])) {
				try {
					database_query('INSERT INTO "teams" ("club", "name") VALUES (?, ?);', [(int)$_GET['club_id'], $_POST['name']]);
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
	header('Location: teams.php');
	exit();
}

require_once('template.php');

template_header('Manage Clubs/Teams');

if (isset($_SESSION['team_manage_error'])) {
	echo '<script>alert(';
	echo json_encode($_SESSION['team_manage_error']);
	echo ');</script>';
	unset($_SESSION['team_manage_error']);
}

echo '<h1>Create Club</h1>';

echo '<form action="teams.php?action=club_create" method="post">';
echo '<input name="name" value="" type="text" placeholder="Name" maxlength="255" required="required" />';
echo '<input type="submit" value="Create" />';
echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
echo '</form>';

echo '<br />';

echo '<h1>Manage Clubs</h1>';

$clubs = database_query('SELECT "id", "name" FROM "clubs" ORDER BY "name";');
usort($clubs, function ($a, $b) {
	return strnatcasecmp($a['name'], $b['name']);
});

foreach ($clubs as $club) {
	echo '<h2>';
	echo htmlescape($club['name']);
	echo '</h2>';

	echo '<h3>Manage Club</h3>';

	echo '<table><tbody><tr>';
	echo '<td>';
	echo '<form action="teams.php?action=club_rename&amp;id=' . htmlescape(urlencode($club['id'])) . '" method="post">';
	echo '<input name="name" value="' . htmlescape($club['name']) . '" type="text" placeholder="Name" maxlength="255" required="required" />';
	echo '<input type="submit" value="Rename" />';
	echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
	echo '</form>';
	echo '</td>';

	echo '<td>';
	echo '<form action="teams.php?action=club_delete&amp;id=' . htmlescape(urlencode($club['id'])) . '" method="post">';
	echo '<input type="submit" value="Delete" data-name="' . htmlescape($club['name']) . '" onclick="return confirm(&quot;Really delete the club \\&quot;&quot; + $(this).data(&quot;name&quot;) + &quot;\\&quot;?&quot;);" />';
	echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
	echo '</form>';
	echo '</td>';
	echo '</tr></tbody></table>';

	echo '<h3>Create Team</h3>';
	echo '<form action="teams.php?action=team_create&amp;club_id=' . htmlescape(urlencode($club['id'])) . '" method="post">';
	echo '<input name="name" value="" type="text" placeholder="Name" maxlength="255" required="required" />';
	echo '<input type="submit" value="Create" />';
	echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
	echo '</form>';
	
	echo '<h3>Manage Teams</h3>';

	$teams = database_query('SELECT "id", "name" FROM "teams" WHERE "club" = ?;', [(int)$club['id']]);
	usort($teams, function ($a, $b) {
		return strnatcasecmp($a['name'], $b['name']);
	});

	echo '<table><tbody>';

	foreach ($teams as $team) {
		echo '<tr>';

		echo '<td>';
		echo '<form action="teams.php?action=team_rename&amp;id=' . htmlescape(urlencode($team['id'])) . '" method="post">';
		echo '<input name="name" value="' . htmlescape($team['name']) . '" type="text" placeholder="Name" maxlength="255" required="required" />';
		echo '<input type="submit" value="Rename" />';
		echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
		echo '</form>';
		echo '</td>';

		echo '<td>';
		echo '<form action="teams.php?action=team_delete&amp;id=' . htmlescape(urlencode($team['id'])) . '" method="post">';
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
