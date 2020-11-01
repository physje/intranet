<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();
$sql = array();

$vars[] = array(4, 'cfgGBR', 43885, 'Kerkenraad en Moderamen', 'Grootboekrekening');
$vars[] = array(4, 'cfgGBR', 43875, 'Organistie en beheer', 'Grootboekrekening');
$vars[] = array(4, 'cfgGBR', 43845, 'Erediensten', 'Grootboekrekening');
$vars[] = array(4, 'cfgGBR', 43895, 'Missionair', 'Grootboekrekening');
$vars[] = array(4, 'cfgGBR', 43865, 'Jeugd en Gezin', 'Grootboekrekening');
$vars[] = array(4, 'cfgGBR', 41412, 'Kerkverbanden afdrachten', 'Grootboekrekening');

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