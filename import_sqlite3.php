<?php

require_once('session.php');
require_once('auth.php');
require_once('database.php');

if (isset($_POST['_csrf_token']) && $_POST['_csrf_token'] === $_SESSION['csrf_token']) {
	if (isset($_FILES['import_file']) && is_uploaded_file($_FILES['import_file']['tmp_name'])) {

		$upload_temp_file = $_FILES['import_file']['tmp_name'];
		$import_db_path = tempnam(sys_get_temp_dir(), '');
		move_uploaded_file($upload_temp_file, $import_db_path);

		$database_connection->beginTransaction();
		try {
			$import_db_conn = new PDO('sqlite:' . $import_db_path);

			$import_db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$import_db_conn->setAttribute(PDO::ATTR_TIMEOUT, 60);
			$import_db_conn->exec('PRAGMA journal_mode=WAL;');
			$import_db_conn->exec('PRAGMA synchronous=NORMAL;');
			$import_db_conn->exec('PRAGMA busy_timeout = 60000;');
			$import_db_conn->exec('PRAGMA foreign_keys = ON;');

			$tables = [
				'years',
				'clubs',
				'competitions',
				'teams',
				'events',
				'point_scores',
				'timed_event_details',
				'timed_scores',
				'individual_scores',
			];

			foreach (array_reverse($tables) as $table) {
				$delete_stmt = $database_connection->prepare('DELETE FROM "' . $table . '";');
				$delete_stmt->execute();
				$delete_stmt = null;
			}

			foreach ($tables as $table_name) {
				$select_stmt = $import_db_conn->prepare('SELECT * FROM "' . $table_name . '";');
				$select_stmt->execute();
	
				$insert_stmt = null;
				while ($row = $select_stmt->fetch(PDO::FETCH_NUM)) {
					if ($insert_stmt == null) {
						$column_count = count($row);
						$insert_stmt = $database_connection->prepare('INSERT INTO "' . $table_name . '" VALUES (' . str_repeat('?, ', $column_count - 1) . ' ?);');
					}
	
					$insert_stmt->execute($row);
					$insert_stmt->closeCursor();
				}
				$select_stmt->closeCursor();
	
				$select_stmt = null;
				$insert_stmt = null;
			}

			$database_connection->commit();

			$_SESSION['import_export_error'] = 'Import succeeded.';
			header('Location: import_export.php');
		} catch (PDOException $e) {
			$database_connection->rollBack();
			$_SESSION['import_export_error'] = 'An error occurred while attempting to import the data, changes have been reverted.';
			header('Location: import_export.php');
		}

		unlink($export_db_path);
	} else {
		http_response_code(400);
	}
} else {
	http_response_code(400);
}
