<?php

require_once('session.php');
require_once('auth.php');
require_once('database.php');

if (isset($_POST['_csrf_token']) && $_POST['_csrf_token'] === $_SESSION['csrf_token']) {
	header('Content-Type: application/vnd.sqlite3');
	header('Content-Disposition: attachment; filename=scores_' . date("YmdHis") . '.sqlite3');
	header('Cache-Control: no-store');

	$export_db_path = tempnam(sys_get_temp_dir(), '');

	$export_db_conn = new PDO('sqlite:' . $export_db_path);

	$export_db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$export_db_conn->setAttribute(PDO::ATTR_TIMEOUT, 60);
	$export_db_conn->exec('PRAGMA journal_mode=WAL;');
	$export_db_conn->exec('PRAGMA synchronous=NORMAL;');
	$export_db_conn->exec('PRAGMA busy_timeout = 60000;');
	$export_db_conn->exec('PRAGMA foreign_keys = ON;');

	$database_connection->beginTransaction();

	try {
		$export_db_conn->beginTransaction();

		$export_db_conn->exec('
CREATE TABLE "years" (
	"id" INTEGER PRIMARY KEY,
	"name" TEXT NOT NULL UNIQUE
);

CREATE TABLE "clubs" (
	"id" INTEGER PRIMARY KEY,
	"year" INTEGER NOT NULL REFERENCES "years" ON UPDATE CASCADE ON DELETE CASCADE,
	"name" TEXT NOT NULL,
	UNIQUE("year", "name")
);

CREATE TABLE "competitions" (
	"id" INTEGER PRIMARY KEY,
	"name" TEXT NOT NULL,
	"year" INTEGER NOT NULL REFERENCES "years" ON UPDATE CASCADE ON DELETE CASCADE,
	"overall_point_multiplier" REAL NOT NULL DEFAULT 1.0,
	UNIQUE("name", "year")
);

CREATE TABLE "teams" (
	"id" INTEGER PRIMARY KEY,
	"club" INTEGER NOT NULL REFERENCES "clubs" ON UPDATE CASCADE ON DELETE CASCADE,
	"competition" INTEGER NOT NULL REFERENCES "competitions" ON UPDATE CASCADE ON DELETE CASCADE,
	"name" TEXT NOT NULL,
	UNIQUE("club", "name", "competition")
);

CREATE TABLE "events" (
	"id" INTEGER PRIMARY KEY,
	"name" TEXT NOT NULL,
	"competition" INTEGER NOT NULL REFERENCES "competitions" ON UPDATE CASCADE ON DELETE CASCADE,
	"type" TEXT NOT NULL DEFAULT \'points\',
	"overall_point_multiplier" REAL NOT NULL DEFAULT 1.0,
	UNIQUE("name", "competition")
);

CREATE TABLE "point_scores" (
	"team" INTEGER NOT NULL REFERENCES "teams" ON UPDATE CASCADE ON DELETE CASCADE,
	"event" INTEGER NOT NULL REFERENCES "events" ON UPDATE CASCADE ON DELETE CASCADE,
	"points" REAL NOT NULL,
	PRIMARY KEY ("team", "event") ON CONFLICT REPLACE
);

CREATE TABLE "timed_event_details" (
	"event" INTEGER PRIMARY KEY ON CONFLICT REPLACE NOT NULL REFERENCES "events" ON UPDATE CASCADE ON DELETE CASCADE,
	"min_time" INTEGER NOT NULL,
	"max_time" INTEGER NOT NULL,
	"max_points" INTEGER NOT NULL,
	"max_errors" INTEGER NOT NULL,
	"correctness_points" INTEGER NOT NULL,
	"cap_points" INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE "timed_scores" (
	"team" INTEGER NOT NULL REFERENCES "teams" ON UPDATE CASCADE ON DELETE CASCADE,
	"event" INTEGER NOT NULL REFERENCES "timed_event_details" ON UPDATE CASCADE ON DELETE CASCADE,
	"time" REAL NOT NULL,
	"errors" REAL NOT NULL,
	PRIMARY KEY ("team", "event") ON CONFLICT REPLACE
);

CREATE TABLE "individual_scores" (
	"id" INTEGER PRIMARY KEY,
	"club" INTEGER NOT NULL REFERENCES "clubs" ON UPDATE CASCADE ON DELETE CASCADE,
	"event" INTEGER NOT NULL REFERENCES "events" ON UPDATE CASCADE ON DELETE CASCADE,
	"name" TEXT,
	"points" REAL NOT NULL
);
');

		foreach ([
			'years',
			'clubs',
			'competitions',
			'teams',
			'events',
			'point_scores',
			'timed_event_details',
			'timed_scores',
			'individual_scores',
		] as $table_name) {
			$select_stmt = $database_connection->prepare('SELECT * FROM "' . $table_name . '";');
			$select_stmt->execute();

			$insert_stmt = null;
			while ($row = $select_stmt->fetch(PDO::FETCH_NUM)) {
				if ($insert_stmt == null) {
					$column_count = count($row);
					$insert_stmt = $export_db_conn->prepare('INSERT INTO "' . $table_name . '" VALUES (' . str_repeat('?, ', $column_count - 1) . ' ?);');
				}

				$insert_stmt->execute($row);
				$insert_stmt->closeCursor();
			}
			$select_stmt->closeCursor();

			$select_stmt = null;
			$insert_stmt = null;
		}

		$export_db_conn->commit();
	} catch (PDOException $e) {
		$export_db_conn->rollBack();
	}

	$database_connection->commit();

	$export_db_conn = null;

	readfile($export_db_path);

	unlink($export_db_path);
} else {
	http_response_code(400);
}
