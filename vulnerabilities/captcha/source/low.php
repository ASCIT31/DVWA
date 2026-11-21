<?php

if( isset( $_POST[ 'Change' ] ) && ( $_POST[ 'step' ] == '1' ) ) {
	// Hide the CAPTCHA form
	$hide_form = true;

	// Get input
	$pass_new  = isset( $_POST[ 'password_new' ] ) ? (string) $_POST[ 'password_new' ] : '';
	$pass_conf = isset( $_POST[ 'password_conf' ] ) ? (string) $_POST[ 'password_conf' ] : '';

	// Fail closed: without a configured reCAPTCHA secret no answer can be
	// verified, so the password change is refused instead of being skipped.
	$resp = false;
	if( $_DVWA[ 'recaptcha_private_key' ] != '' && isset( $_POST[ 'g-recaptcha-response' ] ) ) {
		$resp = recaptcha_check_answer(
			$_DVWA[ 'recaptcha_private_key'],
			$_POST['g-recaptcha-response']
		);
	}

	// Did the CAPTCHA fail?
	if( !$resp ) {
		// What happens when the CAPTCHA was entered incorrectly
		unset( $_SESSION[ 'captcha_passed' ] );
		$html     .= "<pre><br />The CAPTCHA was incorrect. Please try again.</pre>";
		$hide_form = false;
		return;
	}
	else {
		// CAPTCHA was correct. Do both new passwords match?
		if( $pass_new !== '' && hash_equals( $pass_new, $pass_conf ) ) {
			// Remember server-side that THIS user passed the CAPTCHA for THIS
			// password. The browser only gets a confirmation button: no
			// client-controlled flag or password is trusted at step 2.
			$_SESSION[ 'captcha_passed' ] = array(
				'user' => dvwaCurrentUser(),
				'hash' => md5( $pass_new ),
				'time' => time(),
			);

			// Show next stage for the user
			$html .= "
				<pre><br />You passed the CAPTCHA! Click the button to confirm your changes.<br /></pre>
				<form action=\"#\" method=\"POST\">
					<input type=\"hidden\" name=\"step\" value=\"2\" />
					<input type=\"submit\" name=\"Change\" value=\"Change\" />
				</form>";
		}
		else {
			// Both new passwords do not match.
			unset( $_SESSION[ 'captcha_passed' ] );
			$html     .= "<pre>Both passwords must match.</pre>";
			$hide_form = false;
		}
	}
}

if( isset( $_POST[ 'Change' ] ) && ( $_POST[ 'step' ] == '2' ) ) {
	// Hide the CAPTCHA form
	$hide_form = true;

	// Step 2 is only valid if step 1 (CAPTCHA) was completed in this session,
	// by the same user, less than 5 minutes ago. It can be used once.
	$state = isset( $_SESSION[ 'captcha_passed' ] ) ? $_SESSION[ 'captcha_passed' ] : null;
	unset( $_SESSION[ 'captcha_passed' ] );

	if( is_array( $state ) && $state[ 'user' ] === dvwaCurrentUser() && ( time() - $state[ 'time' ] ) <= 300 ) {
		// Update database (parameterised)
		$pass_new     = $state[ 'hash' ];
		$current_user = dvwaCurrentUser();
		$data = $db->prepare( 'UPDATE users SET password = (:password) WHERE user = (:user);' );
		$data->bindParam( ':password', $pass_new, PDO::PARAM_STR );
		$data->bindParam( ':user', $current_user, PDO::PARAM_STR );
		$data->execute();

		// Feedback for the end user
		$html .= "<pre>Password Changed.</pre>";
	}
	else {
		// No server-side proof that the CAPTCHA was solved
		$html .= "<pre>The CAPTCHA must be completed before changing the password.</pre>";
		$hide_form = false;
	}
}

?>
