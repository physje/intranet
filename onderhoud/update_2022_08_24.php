<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();
$sql = array();	

$vars[] = array(1, 'configGroups', 8, 'Pastoraat', 'Groep in de config-file');
$vars[] = array(8, 'typePastoraat', 1, 'Huisbezoek', 'mogelijk type pastoraat');
$vars[] = array(8, 'typePastoraat', 2, 'Pastoraal bezoek', 'mogelijk type pastoraat');
$vars[] = array(8, 'typePastoraat', 3, 'Verjaardag', 'mogelijk type pastoraat');
$vars[] = array(8, 'typePastoraat', 4, 'Huwelijksjubileum', 'mogelijk type pastoraat');
$vars[] = array(8, 'typePastoraat', 5, 'Overige', 'mogelijk type pastoraat');
$vars[] = array(8, 'locatiePastoraat', 1, 'Thuis', 'mogelijk locatie pastoraat');
$vars[] = array(8, 'locatiePastoraat', 2, 'Kerk', 'mogelijk locatie pastoraat');
$vars[] = array(8, 'locatiePastoraat', 3, 'Telefonisch', 'mogelijk locatie pastoraat');
$vars[] = array(8, 'locatiePastoraat', 4, 'Wijk-activiteit', 'mogelijk locatie pastoraat');
$vars[] = array(8, 'locatiePastoraat', 5, 'Toevallige ontmoeting', 'mogelijk locatie pastoraat');
$vars[] = array(8, 'locatiePastoraat', 6, 'Overige', 'mogelijk locatie pastoraat');

foreach($vars as $var) {
	$sql[] = "INSERT INTO $TableConfig ($ConfigGroep, $ConfigName, $ConfigKey, $ConfigValue, $ConfigOpmerking, $ConfigAdded) VALUES ('". $var[0] ."', '". $var[1] ."', '". $var[2] ."', '". $var[3] ."', '". $var[4] ."', ". time() .");";
}

$sql[] = "CREATE TABLE `$TablePastoraat` (`$PastoraatID` int(6) NOT NULL, `$PastoraatIndiener` int(6) NOT NULL, `$PastoraatTijdstip` int(11) NOT NULL, `$PastoraatLid` int(6) NOT NULL, `$PastoraatType` int(1) NOT NULL, `$PastoraatLocatie` int(1) NOT NULL, `$PastoraatZichtOud` set('0','1') NOT NULL DEFAULT '0', `$PastoraatZichtPred` set('0','1') NOT NULL DEFAULT '0', `$PastoraatZichtPas` set('0','1') NOT NULL DEFAULT '0' );";

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