<?php

if( isset( $_REQUEST[ 'Submit' ] ) ) {
	// Get input
	$id = $_REQUEST[ 'id' ];

	// Only accept a positive integer user id; anything else is rejected
	// before it reaches the database.
	if( !ctype_digit( (string) $id ) ) {
		$html .= '<pre>Invalid user ID.</pre>';
	}
	else {
		$id = intval( $id );

		switch ($_DVWA['SQLI_DB']) {
			case MYSQL:
				// Parameterised query: user input is bound as data, never concatenated into SQL
				try {
					$data = $db->prepare( 'SELECT first_name, last_name FROM users WHERE user_id = (:id) LIMIT 1;' );
					$data->bindParam( ':id', $id, PDO::PARAM_INT );
					$data->execute();
					$row = $data->fetch();
				} catch (PDOException $e) {
					// Never echo database errors back to the user
					error_log( 'sqli: ' . $e->getMessage() );
					$row = false;
				}

				if( $row ) {
					// Get values (HTML-encoded on output)
					$first = htmlspecialchars( $row[ 'first_name' ], ENT_QUOTES, 'UTF-8' );
					$last  = htmlspecialchars( $row[ 'last_name' ], ENT_QUOTES, 'UTF-8' );

					// Feedback for end user
					$html .= "<pre>ID: {$id}<br />First name: {$first}<br />Surname: {$last}</pre>";
				}
				break;
			case SQLITE:
				global $sqlite_db_connection;

				try {
					$stmt = $sqlite_db_connection->prepare( 'SELECT first_name, last_name FROM users WHERE user_id = :id LIMIT 1;' );
					$stmt->bindValue( ':id', $id, SQLITE3_INTEGER );
					$result = $stmt->execute();
					$row = $result ? $result->fetchArray() : false;
				} catch (Exception $e) {
					error_log( 'sqli: ' . $e->getMessage() );
					$row = false;
				}

				if( $row ) {
					// Get values (HTML-encoded on output)
					$first = htmlspecialchars( $row[ 'first_name' ], ENT_QUOTES, 'UTF-8' );
					$last  = htmlspecialchars( $row[ 'last_name' ], ENT_QUOTES, 'UTF-8' );

					// Feedback for end user
					$html .= "<pre>ID: {$id}<br />First name: {$first}<br />Surname: {$last}</pre>";
				}
				break;
		}
	}
}

?>
