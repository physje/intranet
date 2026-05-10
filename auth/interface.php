<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('Classes/Member.php');
include_once('Classes/Logging.php');

echo "<html>".NL;
echo "<head>".NL;
echo "	<title>Koningskerk Intranet 4.0.252</title>".NL;
echo "	<link rel='stylesheet' type='text/css' href='http://localhost/3GK/intranet/include/style_default.css?". time() ."'>".NL;
echo "	<link rel='icon' href='http://localhost/3GK/intranet/images/logo.ico'>".NL;
echo "	<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>".NL;
echo "	<meta name='viewport' content='width=device-width, initial-scale=1'>".NL;
echo "</head>".NL;
echo "<body>".NL;
echo "<div class='middenstuk'>".NL;
echo "	<div class='content'>".NL;
echo "		<div class='top_logo'><a href='http://localhost/3GK/intranet/'><img class='logo' src='http://localhost/3GK/intranet/images/logoKoningsKerk.png'></a></div>".NL;
echo "		<form action='". $_SERVER['REQUEST_URI'] ."' METHOD='post'>".NL;

$pname = $pval = "";
foreach ($_POST as $pname => $pval) {
	if ($pname="entered_login" OR $pname="entered_password") {
		echo "<input type='hidden' name='".$pname."' value='".$pval."'>\n";
	}	
}

if ($phpSP_message) {
	echo "<div class='login_error'>$phpSP_message</div>".NL;
}

echo "		<div class='login_box'>".NL;
echo "		<div class='login_header'>Login Scherm</div>".NL;
echo "		<div class='login_username'>".NL;
echo "			<div class='login_text'>Gebruikersnaam</div>".NL;
echo "			<div class='login_input'><input type='text' name='entered_login'></div>".NL;
echo "		</div>".NL;
echo "		<div class='login_password'>".NL;
echo "			<div class='login_text'>Wachtwoord</div>".NL;
echo "			<div class='login_input'><input type='password' name='entered_password'></div>".NL;
echo "		</div>".NL;
echo "		<div class='login_submit'><input type='submit' name='submit' value='Log in'></div>".NL;
echo "		</div>".NL;
echo "		</form>". NL;
echo "		</div>".NL;
echo "	</div>".NL;
echo "</body>".NL;
echo "</html>".NL;


?>