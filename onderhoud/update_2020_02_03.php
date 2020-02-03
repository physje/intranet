<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();
$sql = array();
$vars[] = array(1, 'mailVariabele', 1, 'to', 'mogelijke variabelen voor de mailfunctie');
$vars[] = array(1, 'mailVariabele', 2, 'subject', 'mogelijke variabelen voor de mailfunctie');
$vars[] = array(1, 'mailVariabele', 3, 'message', 'mogelijke variabelen voor de mailfunctie');
$vars[] = array(1, 'mailVariabele', 4, 'formeel', 'mogelijke variabelen voor de mailfunctie');
$vars[] = array(1, 'mailVariabele', 5, 'ouderCC', 'mogelijke variabelen voor de mailfunctie');
$vars[] = array(1, 'mailVariabele', 6, 'from', 'mogelijke variabelen voor de mailfunctie');
$vars[] = array(1, 'mailVariabele', 7, 'fromName', 'mogelijke variabelen voor de mailfunctie');
$vars[] = array(1, 'mailVariabele', 8, 'ReplyTo', 'mogelijke variabelen voor de mailfunctie');
$vars[] = array(1, 'mailVariabele', 9, 'ReplyToName', 'mogelijke variabelen voor de mailfunctie');
$vars[] = array(1, 'mailVariabele', 10, 'cc', 'mogelijke variabelen voor de mailfunctie');
$vars[] = array(1, 'mailVariabele', 11, 'bcc', 'mogelijke variabelen voor de mailfunctie');
$vars[] = array(1, 'mailVariabele', 12, 'file', 'mogelijke variabelen voor de mailfunctie');
$vars[] = array(1, 'mailVariabele', 13, 'fileName', 'mogelijke variabelen voor de mailfunctie');

foreach($vars as $var) {
	$sql[] = "INSERT INTO $TableConfig ($ConfigGroep, $ConfigName, $ConfigKey, $ConfigValue, $ConfigOpmerking, $ConfigAdded) VALUES ('". $var[0] ."', '". $var[1] ."', '". $var[2] ."', '". $var[3] ."', '". $var[4] ."', ". time() .");";
}

foreach($sql as $query) {
	echo $query;
	if(mysqli_query($db, $query)) {
		echo " -> gelukt<br>";	
	} else {
		echo "<b> -> mislukt</b><br>";	
	}
}



# Na uitvoeren bestand verwijderen
if($productieOmgeving) {
	$delen = explode('/', parse_url($_SERVER['PHP_SELF'], PHP_URL_PATH));
	unlink(end($delen));
}

?>