<?php

require_once('config.php');

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
	echo '<link rel="stylesheet" href="style.css" type="text/css" />';
	echo '<script src="jquery.js"></script>';
	echo '<script src="script.js"></script>';
	echo $additional_header_html;
	echo '</head>';
	echo '<body>';
	if ($header) {
		echo '<nav>Welcome, ' . htmlescape($_SESSION['user_name']) . ' | <a href="results.php">Results</a> | <a href="scores.php">Score Entry</a> | <a href="scores_overview.php">Scores Overview</a> | <a href="teams.php">Manage Clubs/Teams</a> | <a href="events.php">Manage Events</a> | <a href="users.php">Manage Users</a> | <a href="import_export.php">Import/Export Data</a> | <a href="logout.php">Log Out</a></nav>';
		echo '<header><h1>' . (!is_null($title) ? htmlescape($title) . ' - ' : '') . htmlescape(APPLICATION_NAME) . '</h1></header>';
	}
	echo '<noscript><strong class="error" style="font-size: 200%; text-decoration: underline;">*** WARNING: You need to have Javascript enabled to use all features of this application correctly. ***</strong></noscript>';
	echo '<main>';
}

function template_footer()
{
	echo '</main>';
	echo '<footer><br /><br />' . APPLICATION_COPYRIGHT_HTML . '</footer>';
	echo '</body>';
	echo '</html>';
	echo PHP_EOL;
}
