<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('Classes/Member.php');
include_once('Classes/Logging.php');

echo "<html>".NL;
echo "<head>".NL;
echo "	<title>$ScriptTitle $Version</title>".NL;
echo "	<link rel='stylesheet' type='text/css' href='". $ScriptURL ."include/style_default.css?". time() ."'>".NL;
echo "	<link rel='icon' href='". $ScriptURL ."images/logo.ico'>".NL;
echo "	<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>".NL;
echo "	<meta name='viewport' content='width=device-width, initial-scale=1'>".NL;
echo "</head>".NL;
echo "<body>".NL;
echo "<div class='middenstuk'>".NL;
echo "	<div class='content'>".NL;
echo "		<div class='top_logo'><a href='". $ScriptURL ."'><img class='logo' src='". $ScriptURL ."images/logoKoningsKerk.png'></a></div>".NL;
echo "		<form action='". $_SERVER['REQUEST_URI'] ."' METHOD='post'>".NL;

if ($phpSP_message) {
	echo "<div class='login_error'>$phpSP_message</div>".NL;
}

echo "		<div class='login_box'>".NL;
echo "			<div class='login_header'>Login Scherm</div>".NL;
echo "			<div class='login_username'>".NL;
echo "				<div class='login_text'>2FA-code</div>".NL;
echo "				<div class='login_input'><input type='text' name='entered_2FA'></div>".NL;
echo "			</div>".NL;
echo "			<div class='login_submit'><input type='submit' name='submit' value='Ga door'></div>".NL;
echo "		</div>".NL;
echo "		</form>". NL;
echo "		<div class='empty'>&nbsp;</div>".NL;
echo "	</div>".NL;
echo "</div>".NL;
echo "</body>".NL;
echo "</html>".NL;


?>