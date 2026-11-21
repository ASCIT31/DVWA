<?php

if( isset( $_POST[ 'btnSign' ] ) ) {
	// Get input
	$message = trim( (string) $_POST[ 'mtxMessage' ] );
	$name    = trim( (string) $_POST[ 'txtName' ] );

	// Server-side validation: enforce the same limits as the form
	// (maxlength 10 / 50) instead of trusting the client.
	if( $name === '' || $message === '' || mb_strlen( $name, 'UTF-8' ) > 10 || mb_strlen( $message, 'UTF-8' ) > 50 ) {
		$html .= '<pre>Name (max 10 characters) and message (max 50 characters) are required.</pre>';
	}
	else {
		// Update database with a parameterised query. The values are stored
		// as plain text and HTML-encoded when rendered (see dvwaGuestbook()).
		$data = $db->prepare( 'INSERT INTO guestbook ( comment, name ) VALUES ( :message, :name );' );
		$data->bindParam( ':message', $message, PDO::PARAM_STR );
		$data->bindParam( ':name', $name, PDO::PARAM_STR );
		$data->execute();
	}
}

?>
