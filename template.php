<?php

require_once('constants.php');

function htmlescape($string)
{
	return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5);
}

function template_header($title = null, $header = true, $additional_header_html = '')
{
	echo '<!DOCTYPE html>'.PHP_EOL;
	echo '<html lang="en">';
	echo '<head>';
	echo '<meta charset="utf-8" />';
	echo '<meta name="viewport" content="width=device-width, initial-scale=1.0" />';
	echo '<title>' . (!is_null($title) ? htmlescape($title) . ' - ' : '') . htmlescape(APPLICATION_NAME) . ' - ' . APPLICATION_COPYRIGHT_HTML . '</title>';
	echo '<link rel="stylesheet" href="res/normalize.min.css" type="text/css" />';
	echo '<link rel="stylesheet" href="res/style.css" type="text/css" />';
	echo '<script src="res/jquery.js"></script>';
	echo '<script src="res/script.js"></script>';
	echo $additional_header_html;
	echo '</head>';
	echo '<body>';
	if ($header) {
		echo '<nav class="no-print">';
		echo '<ul>';
		echo '<li><a href="results.php">Results</a></li>';
		echo '<li><a href="scores.php">Score Entry</a></li>';
		echo '<li><a href="manage_years.php">Management</a></li>';
		echo '<li><a href="manage_users.php">Users</a></li>';
		echo '<li><a href="import_export.php">Import/Export</a></li>';
		echo '<li><a href="logout.php">Log Out</a></li>';
		echo '<li>Welcome, ' . htmlescape($_SESSION['user_name']) . '</li>';
		echo '</ul>';
		echo '</nav>';
	}
	echo '<noscript><strong class="error" style="font-size: 200%; text-decoration: underline;">*** You need to have Javascript enabled to use all features of this application correctly. ***</strong></noscript>';
	if ($header) {
		echo '<main>';
		echo '<header><h1>' . (!is_null($title) ? htmlescape($title) . ' - ' : '') . htmlescape(APPLICATION_NAME) . '</h1></header>';
	} else {
		echo '<main class="no-header">';
	}
}

function template_footer()
{
	echo '<footer>';
	echo APPLICATION_COPYRIGHT_HTML;
	echo '</footer>';
	echo '</main>';
	echo '</body>';
	echo '</html>';
	echo PHP_EOL;
}
