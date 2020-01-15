<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();
$sql = array();

$temp = "CREATE TABLE $TableLP (";
$temp .= "  $LPID int(7) NOT NULL,";
$temp .= "  $LPgeslacht set('M','V') NOT NULL DEFAULT '',";
$temp .= "  $LPVoornaam text NOT NULL,";
$temp .= "  $LPTussenvoegsel text NOT NULL,";
$temp .= "  $LPAchternaam text NOT NULL,";
$temp .= "  $LPmail text NOT NULL,";
$temp .= "  $LPwijk text NOT NULL,";
$temp .= "  $LPstatus set('actief','uitgeschreven','opgezegd') NOT NULL DEFAULT 'actief',";
$temp .= "  $LPrelatie text NOT NULL,";
$temp .= "  $LPdoop text NOT NULL,";
$temp .= "  $LPlastSeen int(11) NOT NULL,";
$temp .= "  $LPlastChecked int(11) NOT NULL";
$temp .= ") ENGINE=InnoDB DEFAULT CHARSET=latin1;";
$sql[] = $temp;

$temp = "ALTER TABLE $TableLP";
$temp .= "  ADD PRIMARY KEY ($LPID);";
$sql[] = $temp;

$vars[] = array(1, 'configGroups', '7', 'LaPosta', 'Groep in de config-file');
$vars[] = array(7, 'LaPostaAPIKey', '', 'nog invullen', 'API-key voor LaPosta');
$vars[] = array(7, 'LPLedenListID', '', 'nog invullen', 'LaPosta ID voor ledenlijst');
$vars[] = array(7, 'LPKoningsmailListID', '', 'nog invullen', 'LaPosta ID voor Koningsmail');
$vars[] = array(7, 'LPTrinitasListID', '', 'nog invullen', 'LaPosta ID voor Trinitas');
$vars[] = array(7, 'LPGebedDagListID', '', 'nog invullen', 'LaPosta ID voor dagelijkse gebedslist');
$vars[] = array(7, 'LPGebedWeekListID', '', 'nog invullen', 'LaPosta ID voor wekelijkse gebedslijst');
$vars[] = array(7, 'LPGebedMaandListID', '', 'nog invullen', 'LaPosta ID voor maandelijkse gebedslijst');
$vars[] = array(7, 'LPFullUpdate', '', 'false', 'Bij een update van LaPosta, alles (true) of alleen de gewijzigde gegevens (false)');
$vars[] = array(7, 'LPWijkListID', 'A', 'nog invullen', 'LaPosta ID voor wijkmail wijk A');
$vars[] = array(7, 'LPWijkListID', 'B', 'nog invullen', 'LaPosta ID voor wijkmail wijk B');
$vars[] = array(7, 'LPWijkListID', 'C', 'nog invullen', 'LaPosta ID voor wijkmail wijk C');
$vars[] = array(7, 'LPWijkListID', 'D', 'nog invullen', 'LaPosta ID voor wijkmail wijk D');
$vars[] = array(7, 'LPWijkListID', 'E', 'nog invullen', 'LaPosta ID voor wijkmail wijk E');
$vars[] = array(7, 'LPWijkListID', 'F', 'nog invullen', 'LaPosta ID voor wijkmail wijk F');
$vars[] = array(7, 'LPWijkListID', 'G', 'nog invullen', 'LaPosta ID voor wijkmail wijk G');
$vars[] = array(7, 'LPWijkListID', 'H', 'nog invullen', 'LaPosta ID voor wijkmail wijk H');
$vars[] = array(7, 'LPWijkListID', 'I', 'nog invullen', 'LaPosta ID voor wijkmail wijk I');
$vars[] = array(7, 'LPWijkListID', 'J', 'nog invullen', 'LaPosta ID voor wijkmail wijk J');

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