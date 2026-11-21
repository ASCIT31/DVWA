<?php

$html = "";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
	// 160 bits from the CSPRNG: unpredictable, not derived from a counter,
	// time or any other guessable value.
	$cookie_value = bin2hex(random_bytes(20));

	$https = ( !empty( $_SERVER['HTTPS'] ) && strtolower( $_SERVER['HTTPS'] ) !== 'off' );
	setcookie("dvwaSession", $cookie_value, [
		'expires'  => time() + 3600,
		'path'     => '/vulnerabilities/weak_id/',
		'secure'   => $https,
		'httponly' => true,
		'samesite' => 'Strict',
	]);
}
?>
