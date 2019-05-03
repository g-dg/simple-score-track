<?php

require_once('session.php');

require_once('auth.php');

require_once('database.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['action'], $_POST['_csrf_token']) && $_POST['_csrf_token'] === $_SESSION['csrf_token']) {
	switch ($_GET['action']) {
		case 'year_create':
			if (isset($_POST['name'])) {
				try {
					database_query('INSERT INTO "years" ("name") VALUES (?);', [$_POST['name']]);
				} catch (Exception $e) {
					$_SESSION['year_manage_error'] = 'An error occurred creating the year. (Check that an year with the same name does not already exist)';
				}
			} else {
				http_response_code(400);
			}
			break;
		case 'year_rename':
			if (isset($_GET['id'], $_POST['name'])) {
				try {
					database_query('UPDATE "years" SET "name" = ? WHERE "id" = ?;', [$_POST['name'], (int)$_GET['id']]);
				} catch (Exception $e) {
					$_SESSION['year_manage_error'] = 'An error occurred renaming the year. (Check that an year with the same name does not already exist)';
				}
			} else {
				http_response_code(400);
			}
			break;
		case 'year_delete':
			if (isset($_GET['id'])) {
				try {
					database_query('DELETE FROM "years" WHERE "id" = ?;', [(int)$_GET['id']]);
				} catch (Exception $e) {
					$_SESSION['year_manage_error'] = 'An error occurred deleting the year.';
				}
			} else {
				http_response_code(400);
			}
			break;
		default:
			$_SESSION['year_manage_error'] = 'An error occurred.';
			http_response_code(400);
			exit();
	}
	header('Location: manage_years.php');
	exit();
}

require_once('template.php');

template_header('Manage Years');

if (isset($_SESSION['year_manage_error'])) {
	echo '<script>alert(';
	echo json_encode($_SESSION['year_manage_error']);
	echo ');</script>';
	unset($_SESSION['year_manage_error']);
}

echo '<h1>Create Year</h1>';

echo '<form action="manage_years.php?action=year_create" method="post">';
echo '<input name="name" value="" type="text" placeholder="Name" maxlength="250" required="required" />';
echo '<input type="submit" value="Create" />';
echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
echo '</form>';

echo '<h1>Manage Years</h1>';
echo '<table><thead><tr><th colspan="2">Manage</th><th>Name</th><th></th></tr></thead><tbody>';

$years = database_query('SELECT "id", "name" FROM "years" ORDER BY "name";');
usort($years, function ($a, $b) {
	return strnatcasecmp($a['name'], $b['name']);
});

foreach ($years as $year) {
	echo '<tr>';

	echo '<td>';
	echo '<a href="manage_competitions.php?year_id=' . htmlescape(urlencode($year['id'])) . '">Competitions</a>';
	echo '</td>';
	echo '<td>';
	echo '<a href="manage_clubs.php?year_id=' . htmlescape(urlencode($year['id'])) . '">Clubs</a>';
	echo '</td>';

	echo '<td>';
	echo '<form action="manage_years.php?action=year_rename&amp;id=' . htmlescape(urlencode($year['id'])) . '" method="post">';
	echo '<input name="name" value="' . htmlescape($year['name']) . '" type="text" placeholder="Name" maxlength="250" required="required" />';
	echo '<input type="submit" value="Rename" />';
	echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
	echo '</form>';
	echo '</td>';

	echo '<td>';
	echo '<form action="manage_years.php?action=year_delete&amp;id=' . htmlescape(urlencode($year['id'])) . '" method="post">';
	echo '<input type="submit" value="Delete" data-name="' . htmlescape($year['name']) . '" onclick="return confirm(&quot;Really delete the year \\&quot;&quot; + $(this).data(&quot;name&quot;) + &quot;\\&quot;?&quot;);" />';
	echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
	echo '</form>';
	echo '</td>';

	echo '</tr>';
}

echo '</tbody></table>';

template_footer();
