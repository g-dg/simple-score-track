<?php

require_once('session.php');

// forces logon
if (!isset($_SESSION['user_id'])) {
	header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
	exit();
}
