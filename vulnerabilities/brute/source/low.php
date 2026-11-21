<?php

if( isset( $_POST[ 'Login' ] ) && isset( $_POST[ 'username' ] ) && isset( $_POST[ 'password' ] ) ) {
	// Check Anti-CSRF token (also prevents replaying the same request in a loop)
	checkToken( isset( $_POST[ 'user_token' ] ) ? $_POST[ 'user_token' ] : '', isset( $_SESSION[ 'session_token' ] ) ? $_SESSION[ 'session_token' ] : null, 'index.php' );

	// Get input (credentials are only accepted in the POST body, never in the URL)
	$user = (string) $_POST[ 'username' ];
	$pass = md5( (string) $_POST[ 'password' ] );

	// Lockout policy
	$total_failed_login = 3;
	$lockout_time       = 15;
	$account_locked     = false;

	// Check the database (user lockout state)
	$data = $db->prepare( 'SELECT failed_login, last_login FROM users WHERE user = (:user) LIMIT 1;' );
	$data->bindParam( ':user', $user, PDO::PARAM_STR );
	$data->execute();
	$row = $data->fetch();

	// Is the account locked out?
	if( ( $row !== false ) && ( $row[ 'failed_login' ] >= $total_failed_login ) ) {
		$last_login = strtotime( $row[ 'last_login' ] );
		$timeout    = $last_login + ( $lockout_time * 60 );
		if( time() < $timeout ) {
			$account_locked = true;
		}
	}

	// Check the credentials (parameterised)
	$data = $db->prepare( 'SELECT * FROM users WHERE user = (:user) AND password = (:password) LIMIT 1;' );
	$data->bindParam( ':user', $user, PDO::PARAM_STR );
	$data->bindParam( ':password', $pass, PDO::PARAM_STR );
	$data->execute();
	$row = $data->fetch();

	// Valid login and account not locked?
	if( ( $row !== false ) && ( $account_locked == false ) ) {
		$avatar       = htmlspecialchars( $row[ 'avatar' ], ENT_QUOTES, 'UTF-8' );
		$failed_login = (int) $row[ 'failed_login' ];
		$last_login   = htmlspecialchars( $row[ 'last_login' ], ENT_QUOTES, 'UTF-8' );
		$user_html    = htmlspecialchars( $user, ENT_QUOTES, 'UTF-8' );

		// Login successful
		$html .= "<p>Welcome to the password protected area <em>{$user_html}</em></p>";
		$html .= "<img src=\"{$avatar}\" />";

		// Had the account been locked out since last login?
		if( $failed_login >= $total_failed_login ) {
			$html .= "<p><em>Warning</em>: Someone might have been brute forcing your account.</p>";
			$html .= "<p>Number of login attempts: <em>{$failed_login}</em>.<br />Last login attempt was at: <em>{$last_login}</em>.</p>";
		}

		// Reset bad login count
		$data = $db->prepare( 'UPDATE users SET failed_login = "0" WHERE user = (:user) LIMIT 1;' );
		$data->bindParam( ':user', $user, PDO::PARAM_STR );
		$data->execute();
	}
	else {
		// Login failed: progressive delay to slow down automated guessing
		sleep( rand( 2, 4 ) );

		// Generic feedback (no user enumeration)
		$html .= "<pre><br />Username and/or password incorrect.<br /><br/>Alternatively, the account has been locked because of too many failed logins.<br />If this is the case, <em>please try again in {$lockout_time} minutes</em>.</pre>";

		// Update bad login count
		$data = $db->prepare( 'UPDATE users SET failed_login = (failed_login + 1) WHERE user = (:user) LIMIT 1;' );
		$data->bindParam( ':user', $user, PDO::PARAM_STR );
		$data->execute();
	}

	// Set the last login time
	$data = $db->prepare( 'UPDATE users SET last_login = now() WHERE user = (:user) LIMIT 1;' );
	$data->bindParam( ':user', $user, PDO::PARAM_STR );
	$data->execute();
}

// Generate Anti-CSRF token
generateSessionToken();

?>
