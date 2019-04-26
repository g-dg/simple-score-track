PRAGMA foreign_keys = ON;
BEGIN TRANSACTION;

DROP TABLE IF EXISTS "individual_scores";
DROP TABLE IF EXISTS "timed_scores";
DROP TABLE IF EXISTS "timed_event_details";
DROP TABLE IF EXISTS "point_scores";
DROP TABLE IF EXISTS "events";
DROP TABLE IF EXISTS "teams";
DROP TABLE IF EXISTS "competitions";
DROP TABLE IF EXISTS "clubs";
DROP TABLE IF EXISTS "years";
DROP TABLE IF EXISTS "users";

CREATE TABLE "users" (
	"id" INTEGER PRIMARY KEY,
	"name" TEXT NOT NULL UNIQUE,
	"password" TEXT NOT NULL
);

CREATE TABLE "years" (
	"id" INTEGER PRIMARY KEY,
	"name" TEXT NOT NULL UNIQUE
);

CREATE TABLE "clubs" (
	"id" INTEGER PRIMARY KEY,
	"year" INTEGER NOT NULL REFERENCES "years" ON UPDATE CASCADE ON DELETE CASCADE,
	"name" TEXT NOT NULL UNIQUE
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
	UNIQUE("club", "name")
);

CREATE TABLE "events" (
	"id" INTEGER PRIMARY KEY,
	"name" TEXT NOT NULL,
	"competition" INTEGER NOT NULL REFERENCES "competitions" ON UPDATE CASCADE ON DELETE CASCADE,
	"type" TEXT NOT NULL DEFAULT 'points',
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
	"event" INTEGER PRIMARY KEY NOT NULL REFERENCES "events" ON UPDATE CASCADE ON DELETE CASCADE,
	"min_time" INTEGER NOT NULL,
	"max_time" INTEGER NOT NULL,
	"max_points" INTEGER NOT NULL,
	"error_penalty_time" INTEGER NOT NULL,
	"error_exponent" REAL NOT NULL DEFAULT 1.0,
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
	"club" INTEGER NOT NULL REFERENCES "clubs" ON UPDATE CASCADE ON DELETE CASCADE,
	"event" INTEGER NOT NULL REFERENCES "events" ON UPDATE CASCADE ON DELETE CASCADE,
	"name" TEXT,
	"points" REAL NOT NULL
);

COMMIT TRANSACTION;
