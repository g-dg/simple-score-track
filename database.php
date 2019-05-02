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
		$database_connection->beginTransaction();
		$database_connection->exec('PRAGMA foreign_keys=ON;BEGIN TRANSACTION;DROP TABLE IF EXISTS "individual_scores";DROP TABLE IF EXISTS "timed_scores";DROP TABLE IF EXISTS "timed_event_details";DROP TABLE IF EXISTS "point_scores";DROP TABLE IF EXISTS "events";DROP TABLE IF EXISTS "teams";DROP TABLE IF EXISTS "competitions";DROP TABLE IF EXISTS "clubs";DROP TABLE IF EXISTS "years";DROP TABLE IF EXISTS "users";CREATE TABLE "users"("id" INTEGER PRIMARY KEY,"name" TEXT NOT NULL UNIQUE,"password" TEXT NOT NULL);CREATE TABLE "years"("id" INTEGER PRIMARY KEY,"name" TEXT NOT NULL UNIQUE);CREATE TABLE "clubs"("id" INTEGER PRIMARY KEY,"year" INTEGER NOT NULL REFERENCES "years" ON UPDATE CASCADE ON DELETE CASCADE,"name" TEXT NOT NULL UNIQUE);CREATE TABLE "competitions"("id" INTEGER PRIMARY KEY,"name" TEXT NOT NULL,"year" INTEGER NOT NULL REFERENCES "years" ON UPDATE CASCADE ON DELETE CASCADE,"overall_point_multiplier" REAL NOT NULL DEFAULT 1.0,UNIQUE("name","year"));CREATE TABLE "teams"("id" INTEGER PRIMARY KEY,"club" INTEGER NOT NULL REFERENCES "clubs" ON UPDATE CASCADE ON DELETE CASCADE,"competition" INTEGER NOT NULL REFERENCES "competitions" ON UPDATE CASCADE ON DELETE CASCADE,"name" TEXT NOT NULL,UNIQUE("club","name"));CREATE TABLE "events"("id" INTEGER PRIMARY KEY,"name" TEXT NOT NULL,"competition" INTEGER NOT NULL REFERENCES "competitions" ON UPDATE CASCADE ON DELETE CASCADE,"type" TEXT NOT NULL DEFAULT \'points\',"overall_point_multiplier" REAL NOT NULL DEFAULT 1.0,UNIQUE("name","competition"));CREATE TABLE "point_scores"("team" INTEGER NOT NULL REFERENCES "teams" ON UPDATE CASCADE ON DELETE CASCADE,"event" INTEGER NOT NULL REFERENCES "events" ON UPDATE CASCADE ON DELETE CASCADE,"points" REAL NOT NULL,PRIMARY KEY("team","event") ON CONFLICT REPLACE);CREATE TABLE "timed_event_details"("event" INTEGER PRIMARY KEY NOT NULL REFERENCES "events" ON UPDATE CASCADE ON DELETE CASCADE,"min_time" INTEGER NOT NULL,"max_time" INTEGER NOT NULL,"max_points" INTEGER NOT NULL,"error_penalty_time" INTEGER NOT NULL,"error_exponent" REAL NOT NULL DEFAULT 1.0,"cap_points" INTEGER NOT NULL DEFAULT 1);CREATE TABLE "timed_scores"("team" INTEGER NOT NULL REFERENCES "teams" ON UPDATE CASCADE ON DELETE CASCADE,"event" INTEGER NOT NULL REFERENCES "timed_event_details" ON UPDATE CASCADE ON DELETE CASCADE,"time" REAL NOT NULL,"errors" REAL NOT NULL DEFAULT 0.0,PRIMARY KEY("team","event") ON CONFLICT REPLACE);CREATE TABLE "individual_scores"("id" INTEGER PRIMARY KEY,"club" INTEGER NOT NULL REFERENCES "clubs" ON UPDATE CASCADE ON DELETE CASCADE,"event" INTEGER NOT NULL REFERENCES "events" ON UPDATE CASCADE ON DELETE CASCADE,"name" TEXT,"points" REAL NOT NULL);COMMIT TRANSACTION;');
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
