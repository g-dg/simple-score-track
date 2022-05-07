BEGIN TRANSACTION;

ALTER TABLE "timed_event_details" ADD COLUMN "max_errors" INTEGER NOT NULL DEFAULT 10;
ALTER TABLE "timed_event_details" ADD COLUMN "correctness_points" INTEGER NOT NULL DEFAULT 50;

ALTER TABLE "timed_event_details" DROP COLUMN "error_penalty_time";
ALTER TABLE "timed_event_details" DROP COLUMN "error_exponent";

COMMIT;
