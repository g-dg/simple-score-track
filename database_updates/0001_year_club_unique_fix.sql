PRAGMA foreign_keys = OFF;

BEGIN TRANSACTION;

CREATE TABLE "clubs_temp" (
	"id" INTEGER,
	"year" INTEGER,
	"name" TEXT
);

INSERT INTO "clubs_temp" ("id", "year", "name") SELECT "id", "year", "name" FROM "clubs";

DROP TABLE "clubs";

CREATE TABLE "clubs" (
	"id" INTEGER PRIMARY KEY,
	"year" INTEGER NOT NULL REFERENCES "years" ON UPDATE CASCADE ON DELETE CASCADE,
	"name" TEXT NOT NULL,
	UNIQUE("year", "name")
);

INSERT INTO "clubs" ("id", "year", "name") SELECT "id", "year", "name" FROM "clubs_temp" WHERE true ON CONFLICT("id") DO UPDATE SET "year" = excluded."year", "name" = excluded."name";

DROP TABLE "clubs_temp";

COMMIT;

PRAGMA foreign_keys = ON;
