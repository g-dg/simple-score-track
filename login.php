<?php

require_once('session.php');

require_once('config.php');

if (isset($_POST['username'], $_POST['password'], $_POST['_csrf_token']) && $_POST['_csrf_token'] === $_SESSION['csrf_token']) {
	require_once('database.php');

	$result = database_query('SELECT "id", "name", "password" FROM "users" WHERE "name" = ?', [$_POST['username']]);

	if (isset($result[0])) {
		// check the password
		if (password_verify($_POST['password'], $result[0]['password'])) {
			if (password_needs_rehash($result[0]['password'], PASSWORD_DEFAULT))
				database_query('UPDATE "users" SET "password" = ? WHERE "id" = ?;', [password_hash($_POST['password'], PASSWORD_DEFAULT), (int)$result[0]['id']]);
			$_SESSION['user_id'] = (int)$result[0]['id'];
			$_SESSION['user_name'] = $result[0]['name'];
			if (isset($_GET['redirect'])) {
				header('Location: ' . urlencode($_GET['redirect']));
			} else {
				header('Location: index.php');
			}
			exit();
		} else {
			$_SESSION['login_error'] = 'Incorrect password';
			header('Location: login.php');
			exit();
		}
	} else {
		$_SESSION['login_error'] = 'Username doesn\'t exist';
		header('Location: login.php');
		exit();
	}
}

require_once('template.php');

template_header('Log In', false);

echo '<h1>' . htmlescape(APPLICATION_NAME) . '</h1>';
echo '<h2>Log In</h2>';
echo '<form action="login.php' . (isset($_GET['redirect']) ? htmlescape('?redirect=' . urlencode($_GET['redirect'])) : '') . '" method="post" class="form">';
echo '<div class="form-element">';
echo '<div class="form-subelement">';
echo '<label for="username">Username:</label>';
echo '</div>';
echo '<div class="form-subelement">';
echo '<input id="username" name="username" type="text" autofocus="autofocus" maxlength="255" required="required" />';
echo '</div>';
echo '</div>';
echo '<div class="form-element">';
echo '<div class="form-subelement">';
echo '<label for="password">Password:</label>';
echo '</div>';
echo '<div class="form-subelement">';
echo '<input id="password" name="password" type="password" maxlength="255" />';
echo '</div>';
echo '</div>';
echo '<div class="form-element">';
echo '<div class="form-subelement"></div>';
echo '<div class="form-subelement">';
echo '<input type="submit" value="Log In" />';
echo '</div>';
echo '</div>';
echo '<input name="_csrf_token" value="' . htmlescape($_SESSION['csrf_token']) . '" type="hidden" />';
echo '</form>';
if (isset($_SESSION['login_error'])) {
	echo '<div class="error">';
	echo htmlescape($_SESSION['login_error']);
	echo '</div>';
	unset($_SESSION['login_error']);
}

template_footer();
