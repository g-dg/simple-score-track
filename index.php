<?php

require_once('session.php');

// force logon
if (!isset($_SESSION['user_id'])) {
	header('Location: login.php?redirect=index.php');
	exit();
}

require_once('database.php');

header('Location: results.php');
