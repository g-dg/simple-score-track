<?php

session_start();

function generate_random_string($length, $chars)
{
	if (function_exists('random_int')) {
		try {
			$string = '';
			for ($i = 0; $i < $length; $i++) {
				$string .= substr($chars, random_int(0, strlen($chars) - 1), 1);
			}
			return $string;
		} catch (\Exception $e) {
		}
	}
	$string = '';
	for ($i = 0; $i < $length; $i++) {
		$string .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
	}
	return $string;
}

// set the csrf token
if (!isset($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = generate_random_string(32, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789');
}
