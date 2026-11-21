<?php

if( isset( $_POST[ 'Submit' ]  ) ) {
	// Get input
	$target = trim( (string) $_REQUEST[ 'ip' ] );

	// Allowlist validation: only a syntactically valid IPv4 address is accepted.
	// Shell metacharacters (; | & ` $ newline...) can never pass this check.
	if( filter_var( $target, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) !== false ) {
		// Defence in depth: the argument is still quoted for the shell.
		$arg = escapeshellarg( $target );

		// Determine OS and execute the ping command.
		if( stristr( php_uname( 's' ), 'Windows NT' ) ) {
			// Windows
			$cmd = shell_exec( 'ping  ' . $arg );
		}
		else {
			// *nix
			$cmd = shell_exec( 'ping  -c 4 ' . $arg );
		}

		// Feedback for the end user
		$html .= '<pre>' . htmlspecialchars( (string) $cmd, ENT_QUOTES, 'UTF-8' ) . '</pre>';
	}
	else {
		$html .= '<pre>ERROR: You have entered an invalid IP.</pre>';
	}
}

?>
