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
// enable foreign key constraints
database_query('PRAGMA foreign_keys = ON;');

// check if the tables exist
if (count(database_query('SELECT "name" FROM "sqlite_master" WHERE "type" = "table" AND ("name" = \'users\' OR "name" = \'clubs\' OR "name" = \'teams\' OR "name" = \'events\' OR "name" = \'scores\')')) < 5) {
	// create the tables
	try {
		$database_connection->beginTransaction();
		$database_connection->exec('DROP TABLE IF EXISTS "scores";DROP TABLE IF EXISTS "events";DROP TABLE IF EXISTS "teams";DROP TABLE IF EXISTS "clubs";DROP TABLE IF EXISTS "users";CREATE TABLE "users"("id" INTEGER PRIMARY KEY,"name" TEXT NOT NULL UNIQUE,"password" TEXT NOT NULL);CREATE TABLE "clubs"("id" INTEGER PRIMARY KEY,"name" TEXT NOT NULL UNIQUE);CREATE TABLE "teams"("id" INTEGER PRIMARY KEY,"club" INTEGER NOT NULL REFERENCES "clubs" ON UPDATE CASCADE ON DELETE CASCADE,"name" TEXT NOT NULL,UNIQUE("club","name") ON CONFLICT ABORT);CREATE TABLE "events"("id" INTEGER PRIMARY KEY,"name" TEXT NOT NULL UNIQUE,"overall_point_multiplier" REAL NOT NULL DEFAULT 1);CREATE TABLE "scores"("team" INTEGER NOT NULL REFERENCES "teams" ON UPDATE CASCADE ON DELETE CASCADE,"event" INTEGER NOT NULL REFERENCES "events" ON UPDATE CASCADE ON DELETE CASCADE,"points" REAL NOT NULL,PRIMARY KEY("team","event") ON CONFLICT REPLACE);');
		$database_connection->commit();
	} catch (Exception $e) {
		$database_connection->rollBack();
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
	$done_retrying = false;
	$start_time = time();
	while (!$done_retrying) {
		try {
			$stmt = $database_connection->prepare($sql);
			$stmt->execute($params);
			$database_affected_row_count = $stmt->rowCount();
			$done_retrying = true;
		} catch (PDOException $e) {
			// keep retrying if locked
			if (substr_count($e->getMessage(), 'database is locked') == 0) {
				throw new Exception($e->getMessage(), 0, $e);
			} else {
				if (time() - $start_time > 60) {
					throw new Exception($e->getMessage(), 0, $e);
				}
				usleep(mt_rand(1000, 10000));
			}
		}
	}
	return $stmt->fetchAll();
}
