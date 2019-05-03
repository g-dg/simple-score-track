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
		case 'club_create':
			if (isset($_POST['name'])) {
				try {
					database_query('INSERT INTO "clubs" ("name", "year") VALUES (?, ?);', [$_POST['name'], (int)$_GET['year_id']]);
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
		default:
			$_SESSION['team_manage_error'] = 'An error occurred.';
			http_response_code(400);
			exit();
	}
	header('Location: manage_clubs.php?year_id=' . urlencode($_GET['year_id']));
	exit();
}

require_once('template.php');

template_header('Manage Clubs');

$year_details = database_query('SELECT "name" FROM "years" WHERE "id" = ?;', [(int)$_GET['year_id']])[0];
echo '<div>';
echo '<a href="manage_years.php">Years</a>';
echo ' &gt; ';
echo htmlescape($year_details['name']);
echo '</div>';

if (isset($_SESSION['club_manage_error'])) {
	echo '<script>alert(';
	echo json_encode($_SESSION['club_manage_error']);
	echo ');</script>';
	unset($_SESSION['club_manage_error']);
}

echo '<h2>Create Club</h2>';

echo '<form action="manage_clubs.php?year_id=' . htmlescape(urlencode($_GET['year_id'])) . '&amp;action=club_create" method="post">';
echo '<input name="name" value="" type="text" placeholder="Name" maxlength="250" required="required" />';
echo '<input type="submit" value="Create" />';
echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
echo '</form>';

echo '<br />';

echo '<h2>Manage Clubs</h2>';

$clubs = database_query('SELECT "id", "name" FROM "clubs" WHERE "year" = ? ORDER BY "name";', [(int)$_GET['year_id']]);
usort($clubs, function ($a, $b) {
	return strnatcasecmp($a['name'], $b['name']);
});

echo '<table><tbody>';

foreach ($clubs as $club) {
	echo '<tr>';
	echo '<td>';
	echo '<form action="manage_clubs.php?year_id=' . htmlescape(urlencode($_GET['year_id'])) . '&amp;action=club_rename&amp;id=' . htmlescape(urlencode($club['id'])) . '" method="post">';
	echo '<input name="name" value="' . htmlescape($club['name']) . '" type="text" placeholder="Name" maxlength="250" required="required" />';
	echo '<input type="submit" value="Rename" />';
	echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
	echo '</form>';
	echo '</td>';

	echo '<td>';
	echo '<form action="manage_clubs.php?year_id=' . htmlescape(urlencode($_GET['year_id'])) . '&amp;action=club_delete&amp;id=' . htmlescape(urlencode($club['id'])) . '" method="post">';
	echo '<input type="submit" value="Delete" data-name="' . htmlescape($club['name']) . '" onclick="return confirm(&quot;Really delete the club \\&quot;&quot; + $(this).data(&quot;name&quot;) + &quot;\\&quot;?&quot;);" />';
	echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
	echo '</form>';
	echo '</td>';
	echo '</tr>';
}

echo '</tbody></table>';

template_footer();
