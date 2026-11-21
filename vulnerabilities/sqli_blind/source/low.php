<?php

if( isset( $_GET[ 'Submit' ] ) ) {
	// Get input
	$id = $_GET[ 'id' ];
	$exists = false;

	// Only accept a positive integer user id; anything else (quotes, boolean
	// conditions, SLEEP(), comments...) is rejected before reaching the database.
	if( ctype_digit( (string) $id ) ) {
		$id = intval( $id );

		switch ($_DVWA['SQLI_DB']) {
			case MYSQL:
				// Parameterised query: user input is bound as data, never concatenated into SQL
				try {
					$data = $db->prepare( 'SELECT first_name, last_name FROM users WHERE user_id = (:id) LIMIT 1;' );
					$data->bindParam( ':id', $id, PDO::PARAM_INT );
					$data->execute();
					$exists = ( $data->fetch() !== false );
				} catch (PDOException $e) {
					error_log( 'sqli_blind: ' . $e->getMessage() );
					$exists = false;
				}
				break;
			case SQLITE:
				global $sqlite_db_connection;

				try {
					$stmt = $sqlite_db_connection->prepare( 'SELECT first_name, last_name FROM users WHERE user_id = :id LIMIT 1;' );
					$stmt->bindValue( ':id', $id, SQLITE3_INTEGER );
					$result = $stmt->execute();
					$exists = $result ? ( $result->fetchArray() !== false ) : false;
				} catch (Exception $e) {
					error_log( 'sqli_blind: ' . $e->getMessage() );
					$exists = false;
				}
				break;
		}
	}

	if ($exists) {
		// Feedback for end user
		$html .= '<pre>User ID exists in the database.</pre>';
	} else {
		// User wasn't found, so the page wasn't!
		header( $_SERVER[ 'SERVER_PROTOCOL' ] . ' 404 Not Found' );

		// Feedback for end user
		$html .= '<pre>User ID is MISSING from the database.</pre>';
	}

}

?>
