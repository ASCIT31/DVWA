<?php

// The page we wish to display
$file = isset( $_GET[ 'page' ] ) ? (string) $_GET[ 'page' ] : 'include.php';

// Strict allowlist: only the module's own pages can be included.
// Path traversal (../../etc/passwd), absolute paths and stream wrappers
// (php://filter, data://, http://...) can never match an entry.
$allowedPages = [
	'include.php',
	'file1.php',
	'file2.php',
	'file3.php',
];

if( !in_array( $file, $allowedPages, true ) ) {
	// This isn't the page we want!
	echo "ERROR: File not found!";
	exit;
}

?>
