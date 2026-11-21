<?php

if( isset( $_POST[ 'Change' ] ) ) {
	// State-changing request: POST only, with a valid anti-CSRF token
	checkToken( isset( $_POST[ 'user_token' ] ) ? $_POST[ 'user_token' ] : '', isset( $_SESSION[ 'session_token' ] ) ? $_SESSION[ 'session_token' ] : null, 'index.php' );

	// Get input
	$pass_curr = isset( $_POST[ 'password_current' ] ) ? (string) $_POST[ 'password_current' ] : '';
	$pass_new  = isset( $_POST[ 'password_new' ] ) ? (string) $_POST[ 'password_new' ] : '';
	$pass_conf = isset( $_POST[ 'password_conf' ] ) ? (string) $_POST[ 'password_conf' ] : '';

	// Re-authenticate: the current password must be supplied and correct
	$current_user = dvwaCurrentUser();
	$data = $db->prepare( 'SELECT password FROM users WHERE user = (:user) LIMIT 1;' );
	$data->bindParam( ':user', $current_user, PDO::PARAM_STR );
	$data->execute();
	$row = $data->fetch();
	$current_ok = ( $row !== false ) && hash_equals( (string) $row[ 'password' ], md5( $pass_curr ) );

	// Do both new passwords match and does the current password match the user?
	if( $current_ok && $pass_new !== '' && hash_equals( $pass_new, $pass_conf ) ) {
		$pass_new = md5( $pass_new );

		// Update database with new password (parameterised)
		$data = $db->prepare( 'UPDATE users SET password = (:password) WHERE user = (:user);' );
		$data->bindParam( ':password', $pass_new, PDO::PARAM_STR );
		$data->bindParam( ':user', $current_user, PDO::PARAM_STR );
		$data->execute();

		// Feedback for the user
		$html .= "<pre>Password Changed.</pre>";
	}
	else {
		// Issue with passwords matching
		$html .= "<pre>Passwords did not match or current password incorrect.</pre>";
	}
}

// Generate Anti-CSRF token
generateSessionToken();

?>
