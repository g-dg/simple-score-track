<?php

require_once('config.php');

// check if the database is accessible
if (!is_file(DATABASE_FILE) ||
	!is_readable(DATABASE_FILE) ||
	!is_writable(DATABASE_FILE) ||
	!is_readable(dirname(DATABASE_FILE)) ||
	!is_writable(dirname(DATABASE_FILE))) {
	throw new Exception('The database is inaccessible (must be readable and writable and in a readable and writable directory)');
}
// connect to the database
$database_connection = new PDO('sqlite:' . DATABASE_FILE);
$database_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$database_connection->setAttribute(PDO::ATTR_TIMEOUT, 60);
// use write-ahead logging for performance reasons
database_query('PRAGMA journal_mode=WAL;');
database_query('PRAGMA synchronous=NORMAL;');
// set busy timeout
database_query('PRAGMA busy_timeout = 60000;');
// enable foreign key constraints
database_query('PRAGMA foreign_keys = ON;');

// check if the tables exist
if (count(database_query('SELECT "name" FROM "sqlite_master" WHERE "type" = "table" AND (
	"name" = \'users\' OR
	"name" = \'years\' OR
	"name" = \'clubs\' OR
	"name" = \'competitions\' OR
	"name" = \'teams\' OR
	"name" = \'events\' OR
	"name" = \'point_scores\' OR
	"name" = \'timed_event_details\' OR
	"name" = \'timed_scores\' OR
	"name" = \'individual_scores\'
)')) < 10) {
	// create the tables
	try {
		$database_definition = file_get_contents("./database.sql");

		//$database_connection->beginTransaction();
		$database_connection->exec($database_definition);
		//$database_connection->commit();
	} catch (Exception $e) {
		//$database_connection->rollBack();
		throw new Exception('Could not set up the database. (The database must be readable and writable and in a readable and writable directory)', 0, $e);
	}
}

// check if there is at least one user
$database_connection->beginTransaction();
if ((int)database_query('SELECT COUNT() FROM "users";')[0][0] < 1) {
	database_query('INSERT INTO "users"("name","password")VALUES(?,?);', [DEFAULT_USERNAME, password_hash(DEFAULT_PASSWORD, PASSWORD_DEFAULT)]);
}
$database_connection->commit();

/**
 * Executes an SQL query on the database
 * @param sql The SQL statement
 * @param params The parameters to pass to the SQL statement
 * @return array The result set
 */
function database_query($sql, $params = [])
{
	global $database_connection;
	$stmt = $database_connection->prepare($sql);
	$stmt->execute($params);
	return $stmt->fetchAll();
}
