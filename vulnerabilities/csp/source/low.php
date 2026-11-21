<?php

// Strict policy: scripts only from this origin, no plugins, no <base> hijack.
// Content hosting / CDN domains (pastebin.com, hastebin.com, unpkg.com,
// cdn.jsdelivr.net, ...) are never trusted because anyone can publish
// arbitrary JavaScript on them.
$headerCSP = "Content-Security-Policy: script-src 'self'; object-src 'none'; base-uri 'self';";

header($headerCSP);

?>
<?php
if (isset ($_POST['include'])) {
// The submitted URL is never turned into a <script src>; it is only echoed back as text.
$page[ 'body' ] .= "
	<pre>External scripts cannot be included: " . htmlspecialchars( (string) $_POST['include'], ENT_QUOTES, 'UTF-8' ) . "</pre>
";
}
$page[ 'body' ] .= '
<form name="csp" method="POST">
	<p>You can include scripts from external sources, examine the Content Security Policy and enter a URL to include here:</p>
	<input size="50" type="text" name="include" value="" id="include" />
	<input type="submit" value="Include" />
</form>
';
