<?php
// The token is issued by the server for the phrase it rendered ("ChangeMe").
// No token-generation logic or secret is exposed to the client any more.
$serverToken = dvwaJavascriptToken( "ChangeMe" );
$page[ 'body' ] .= <<<EOF
<script>
	document.getElementById("token").value = "{$serverToken}";
</script>
EOF;
?>
