<?php

require_once('session.php');

unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['csrf_token']);

header('Location: login.php');
