<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();
$sql = array();

$vars[] = array(2, 'SMTPHost', '', 'nog invullen', 'Host om via SMTP mails te versturen');
$vars[] = array(2, 'SMTPPort', '', 'nog invullen', 'Poortnummer van de host om via SMTP mails te versturen');
$vars[] = array(2, 'SMTPSSL', '', 'nog invullen', 'Welke type SSL (SSL/TLS) moet worden gebruikt');
$vars[] = array(2, 'SMTPUsername', '', 'nog invullen', 'Gebruikersnaam voor SMTP-server');
$vars[] = array(2, 'SMTPPassword', '', 'nog invullen', 'Bijbehorende wachtwoord');

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