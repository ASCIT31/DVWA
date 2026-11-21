<?php

if( isset( $_POST[ 'Upload' ] ) ) {
	// File information
	$uploaded_name = isset( $_FILES[ 'uploaded' ][ 'name' ] ) ? (string) $_FILES[ 'uploaded' ][ 'name' ] : '';
	$uploaded_ext  = strtolower( pathinfo( $uploaded_name, PATHINFO_EXTENSION ) );
	$uploaded_size = isset( $_FILES[ 'uploaded' ][ 'size' ] ) ? (int) $_FILES[ 'uploaded' ][ 'size' ] : 0;
	$uploaded_tmp  = isset( $_FILES[ 'uploaded' ][ 'tmp_name' ] ) ? (string) $_FILES[ 'uploaded' ][ 'tmp_name' ] : '';

	// Where are we going to be writing to?
	$target_path   = DVWA_WEB_PAGE_TO_ROOT . 'hackable/uploads/';

	// Detect the real content type server-side (never trust the client supplied
	// name or Content-Type header).
	$image_info = ( $uploaded_tmp !== '' && is_uploaded_file( $uploaded_tmp ) ) ? @getimagesize( $uploaded_tmp ) : false;
	$allowed    = array(
		'jpg'  => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'png'  => 'image/png',
	);

	// Is it a JPEG/PNG image whose extension matches its real content?
	if( isset( $allowed[ $uploaded_ext ] ) &&
		( $uploaded_size > 0 && $uploaded_size < 100000 ) &&
		$image_info !== false &&
		$image_info[ 'mime' ] === $allowed[ $uploaded_ext ] ) {

		// Random server-side name with a safe extension: the client never
		// chooses the stored file name (no .php / .html / .phtml possible).
		$safe_ext    = ( $image_info[ 'mime' ] === 'image/png' ) ? 'png' : 'jpg';
		$target_file = bin2hex( random_bytes( 16 ) ) . '.' . $safe_ext;
		$temp_file   = ( ( ini_get( 'upload_tmp_dir' ) == '' ) ? ( sys_get_temp_dir() ) : ( ini_get( 'upload_tmp_dir' ) ) );
		$temp_file  .= DIRECTORY_SEPARATOR . $target_file;

		// Re-encode the image to drop any embedded payload or metadata
		if( $image_info[ 'mime' ] === 'image/jpeg' ) {
			$img = @imagecreatefromjpeg( $uploaded_tmp );
			$ok  = $img && imagejpeg( $img, $temp_file, 100 );
		}
		else {
			$img = @imagecreatefrompng( $uploaded_tmp );
			$ok  = $img && imagepng( $img, $temp_file, 9 );
		}
		if( $img ) {
			imagedestroy( $img );
		}

		// Can we move the file to the upload folder?
		if( $ok && rename( $temp_file, ( getcwd() . DIRECTORY_SEPARATOR . $target_path . $target_file ) ) ) {
			// Yes!
			$html .= "<pre><a href='{$target_path}{$target_file}'>{$target_file}</a> succesfully uploaded!</pre>";
		}
		else {
			// No
			$html .= '<pre>Your image was not uploaded.</pre>';
		}

		// Delete any temp files
		if( file_exists( $temp_file ) ) {
			unlink( $temp_file );
		}
	}
	else {
		// Invalid file
		$html .= '<pre>Your image was not uploaded. We can only accept JPEG or PNG images.</pre>';
	}
}

?>
