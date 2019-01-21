<?php

require_once('session.php');

// force logon
if (!isset($_SESSION['user_id'])) {
	header('Location: login.php?redirect=users.php');
	exit();
}

require_once('database.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['action'], $_POST['_csrf_token']) && $_POST['_csrf_token'] === $_SESSION['csrf_token']) {
	switch ($_GET['action']) {
		case 'user_create':
			if (isset($_POST['name'], $_POST['password1'], $_POST['password2'])) {
				if ($_POST['password1'] === $_POST['password2']) {
					try {
						database_query('INSERT INTO "users" ("name", "password") VALUES (?, ?);', [$_POST['name'], password_hash($_POST['password1'], PASSWORD_DEFAULT)]);
					} catch (Exception $e) {
						$_SESSION['user_admin_error'] = 'An error occurred creating the user. (Check that a user with the same name does not already exist)';
					}
				} else {
					$_SESSION['user_admin_error'] = 'Passwords don\'t match.';
				}
			} else {
				http_response_code(400);
			}
			break;
		case 'user_rename':
			if (isset($_GET['id'], $_POST['name'])) {
				try {
					database_query('UPDATE "users" SET "name" = ? WHERE "id" = ?;', [$_POST['name'], (int)$_GET['id']]);
				} catch (Exception $e) {
					$_SESSION['user_admin_error'] = 'An error occurred renaming the user. (Check that a user with the same name does not already exist)';
				}
			} else {
				http_response_code(400);
			}
			break;
		case 'user_change_password':
			if (isset($_GET['id'], $_POST['password1'], $_POST['password2'])) {
				if ($_POST['password1'] === $_POST['password2']) {
					try {
						database_query('UPDATE "users" SET "password" = ? WHERE "id" = ?;', [password_hash($_POST['password1'], PASSWORD_DEFAULT), (int)$_GET['id']]);
					} catch (Exception $e) {
						$_SESSION['user_admin_error'] = 'An error occurred changing the password.';
					}
				} else {
					$_SESSION['user_admin_error'] = 'Passwords don\'t match.';
				}
			} else {
				http_response_code(400);
			}
			break;
		case 'user_delete':
			if (isset($_GET['id'])) {
				try {
					database_query('DELETE FROM "users" WHERE "id" = ?;', [(int)$_GET['id']]);
				} catch (Exception $e) {
					$_SESSION['user_admin_error'] = 'An error occurred deleting the user.';
				}
			} else {
				http_response_code(400);
			}
			break;
		default:
			$_SESSION['user_admin_error'] = 'An error occurred.';
			http_response_code(400);
	}
	header('Location: users.php');
	exit();
}

require_once('template.php');

template_header('Manage Users');

if (isset($_SESSION['user_admin_error'])) {
	echo '<script>alert(';
	echo json_encode($_SESSION['user_admin_error']);
	echo ');</script>';
	unset($_SESSION['user_admin_error']);
}

echo '<h1>Create User</h1>';

echo '<form action="users.php?action=user_create" method="post">';
echo '<input name="name" value="" type="text" placeholder="Username" maxlength="255" required="required" />';
echo '<input name="password1" value="" type="password" placeholder="Password" maxlength="255" />';
echo '<input name="password2" value="" type="password" placeholder="Confirm Password" maxlength="255" />';
echo '<input type="submit" value="Create" />';
echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
echo '</form>';

echo '<h1>Manage Users</h1>';
echo '<table><thead><tr><th>Username</th><th>Password</th><th></th></tr></thead><tbody>';

$users = database_query('SELECT "id", "name" FROM "users" ORDER BY "name";');
usort($users, function ($a, $b) {
	return strnatcasecmp($a['name'], $b['name']);
});

foreach ($users as $user) {
	echo '<tr>';

	echo '<td>';
	echo '<form action="users.php?action=user_rename&amp;id=' . htmlescape(urlencode($user['id'])) . '" method="post">';
	echo '<input name="name" value="' . htmlescape($user['name']) . '" type="text" placeholder="Username" maxlength="255" required="required" />';
	echo '<input type="submit" value="Rename" />';
	echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
	echo '</form>';
	echo '</td>';

	echo '<td>';
	echo '<form action="users.php?action=user_change_password&amp;id=' . htmlescape(urlencode($user['id'])) . '" method="post">';
	echo '<input name="password1" value="" type="password" placeholder="Password" maxlength="255" />';
	echo '<input name="password2" value="" type="password" placeholder="Confirm Password" maxlength="255" />';
	echo '<input type="submit" value="Change Password" data-name="' . htmlescape($user['name']) . '" onclick="return confirm(&quot;Really change the password for \\&quot;&quot; + $(this).data(&quot;name&quot;) + &quot;\\&quot;?&quot;);" />';
	echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
	echo '</form>';
	echo '</td>';

	echo '<td>';
	echo '<form action="users.php?action=user_delete&amp;id=' . htmlescape(urlencode($user['id'])) . '" method="post">';
	echo '<input type="submit" value="Delete" data-name="' . htmlescape($user['name']) . '" onclick="return confirm(&quot;Really delete the user \\&quot;&quot; + $(this).data(&quot;name&quot;) + &quot;\\&quot;?&quot;);" ';
	if ((int)$user['id'] === $_SESSION['user_id']) {
		echo 'disabled="disabled" title="You cannot delete your own account"';
	}
	echo '/>';
	echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
	echo '</form>';
	echo '</td>';

	echo '</tr>';
}

echo '</tbody></table>';

template_footer();
