<?php

define( 'DVWA_WEB_PAGE_TO_ROOT', '' );
require_once DVWA_WEB_PAGE_TO_ROOT . 'dvwa/includes/dvwaPage.inc.php';

/*
 * Before the database exists, setup.php must be reachable to install DVWA.
 * Once it is installed, setup/reset (and the environment details shown on
 * this page) is restricted to the authenticated administrator.
 */
function dvwaSetupDatabaseInstalled() {
	global $_DVWA, $DBMS;

	if( $DBMS != 'MySQL' ) {
		return false;
	}
	try {
		$link = @mysqli_connect( $_DVWA[ 'db_server' ], $_DVWA[ 'db_user' ], $_DVWA[ 'db_password' ], '', $_DVWA[ 'db_port' ] );
		if( !$link ) {
			return false;
		}
		$installed = @mysqli_select_db( $link, $_DVWA[ 'db_database' ] )
			&& ( @mysqli_query( $link, 'SELECT 1 FROM users LIMIT 1' ) !== false );
		mysqli_close( $link );
		return $installed;
	} catch( Throwable $e ) {
		return false;
	}
}

if( dvwaSetupDatabaseInstalled() ) {
	dvwaPageStartup( array( 'authenticated' ) );
	if( dvwaCurrentUser() !== 'admin' ) {
		dvwaMessagePush( 'Only the administrator can reset the database.' );
		dvwaRedirect( DVWA_WEB_PAGE_TO_ROOT . 'index.php' );
	}
}
else {
	dvwaPageStartup( array( ) );
}

$page = dvwaPageNewGrab();
$page[ 'title' ]   = 'Setup' . $page[ 'title_separator' ].$page[ 'title' ];
$page[ 'page_id' ] = 'setup';

if( isset( $_POST[ 'create_db' ] ) ) {
	// Anti-CSRF
	if (array_key_exists ("session_token", $_SESSION)) {
		$session_token = $_SESSION[ 'session_token' ];
	} else {
		$session_token = "";
	}

	checkToken( $_REQUEST[ 'user_token' ], $session_token, 'setup.php' );

	if( $DBMS == 'MySQL' ) {
		include_once DVWA_WEB_PAGE_TO_ROOT . 'dvwa/includes/DBMS/MySQL.php';
	}
	elseif($DBMS == 'PGSQL') {
		// include_once DVWA_WEB_PAGE_TO_ROOT . 'dvwa/includes/DBMS/PGSQL.php';
		dvwaMessagePush( 'PostgreSQL is not yet fully supported.' );
		dvwaPageReload();
	}
	else {
		dvwaMessagePush( 'ERROR: Invalid database selected. Please review the config file syntax.' );
		dvwaPageReload();
	}
}

// Anti-CSRF
generateSessionToken();

$database_type_name = "Unknown - The site is probably now broken";
if( $DBMS == 'MySQL' ) {
	$database_type_name = "MySQL/MariaDB";
} elseif($DBMS == 'PGSQL') {
	$database_type_name = "PostgreSQL";
}

$git_ref = "<em>Unknown</em><br><br>";
$mod_rewrite = "<em>Unknown</em><br>";

if (function_exists('apache_get_modules')) {
	if (in_array('mod_rewrite', apache_get_modules())) {
		$mod_rewrite = "<em><span class='success'>Enabled</span></em><br>";
	} else {
		$mod_rewrite = "<em><span class='failure'>Not Enabled</span></em><br>";
	}
} else {
	if (PHP_OS == "Linux") {
		# Debian based systems
		$out = shell_exec ("apachectl -M | grep rewrite_module");

		# RHEL based ssytems
		$out1 = shell_exec ("/usr/sbin/httpd -M | grep rewrite_module");

		if ($out =="" && $out1 == "") {
			$mod_rewrite = "<em><span class='failure'>Not Enabled</span></em><br>";
		} else {
			$mod_rewrite = "<em><span class='success'>Enabled</span></em><br>";
		}
	}
}

if (PHP_OS == "Linux") {
	if (is_dir (".git")) {
		$git_log = shell_exec ("git -c 'safe.directory=*' log -1");
		if (!is_null ($git_log)) {
			$tmp = explode ("\n", $git_log);
			$date = str_replace ("Date: ", "Date: <em>", $tmp[2]);
			$git_ref = "<ul><li>" . str_replace ("commit ", "Git reference: <em>", $tmp[0]) . "</em></li><li>" . $date . "</em></li></ul>";
		}
	}
}

if (!is_dir ("./vulnerabilities/api/vendor")) {
	$vendor = "<em><span class='failure'>Not Installed</span></em><br><br>";
	$vendor .= "For information on how to install these, see the <a href='https://github.com/digininja/DVWA/blob/master/README.md#vendor-files'>README</a>.<br>";
} else {
	$vendor = "<em><span class='success'>Installed</span></em><br>";
}

$phpVersionWarning = "";

if (version_compare(phpversion(), '6', '<')) {
	$phpVersionWarning = "<span class=\"failure\">Versions of PHP below 7.x are not supported, please upgrade.</span><br /><br />";
} elseif (version_compare(phpversion(), '7.3', '<')) {
	$phpVersionWarning = "<span class=\"failure\">Versions of PHP below 7.3 may work but have known problems, please upgrade.</span><br /><br />";
}

$page[ 'body' ] .= "
<div class=\"body_padded\">
	<h1>Database Setup <img src=\"" . DVWA_WEB_PAGE_TO_ROOT . "dvwa/images/spanner.png\" /></h1>

	<p>Click on the 'Create / Reset Database' button below to create or reset your database.<br />
	If you get an error make sure you have the correct user credentials in: <em>" . realpath(  getcwd() . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "config.inc.php" ) . "</em></p>

	<p>If the database already exists, <em>it will be cleared and the data will be reset</em>.<br />
	A new random password is generated for the administrator account (<em>admin</em>) every time the database is created or reset.<br />
	It is displayed once, right after setup: store it then.</p>
	<hr />
	<br />

	<h2>Setup Check</h2>

	<em>General</em><br />
	{$DVWAOS}<br />
	<br>
	DVWA version: {$git_ref}
	<br>
	{$DVWARecaptcha}<br />
	<br />
	{$DVWAUploadsWrite}<br />
	{$bakWritable}
	<br />
	<br>

	<em>Apache</em><br>
	{$SERVER_NAME}<br /><br>
	mod_rewrite: {$mod_rewrite}
	mod_rewrite is required for the API labs.<br><br>

	<em>PHP</em><br>
	PHP version: <em>" . phpversion() . "</em><br />
	{$phpVersionWarning}
	{$phpDisplayErrors}<br />
	{$phpDisplayStartupErrors}<br />
	{$phpURLInclude}<br/ >
	{$phpURLFopen}<br />
	{$phpGD}<br />
	{$phpMySQL}<br />
	{$phpPDO}<br />
	<br />
	<em>Database</em><br>
	Backend database: <em>{$database_type_name}</em><br />
	{$MYSQL_USER}<br />
	{$MYSQL_PASS}<br />
	{$MYSQL_DB}<br />
	{$MYSQL_SERVER}<br />
	{$MYSQL_PORT}<br />
	<br />
	<em>API</em><br>
	<i>This section is only important if you want to use the API module.</i><br>
	Vendor files installed: {$vendor}<br>

	<i><span class=\"failure\">Status in red</span>, indicate there will be an issue when trying to complete some modules.</i><br />
	<br />
	If you see disabled on either <i>allow_url_fopen</i> or <i>allow_url_include</i>, set the following in your php.ini file and restart Apache.<br />
	<pre><code>allow_url_fopen = On
allow_url_include = On</code></pre>
	These are only required for the file inclusion labs so unless you want to play with those, you can ignore them.

	<br /><br /><br />

	<!-- Create db button -->
	<form action=\"#\" method=\"post\">
		<input name=\"create_db\" type=\"submit\" value=\"Create / Reset Database\">
		" . tokenField() . "
	</form>
	<br />
	<hr />
</div>";

dvwaHtmlEcho( $page );

?>
