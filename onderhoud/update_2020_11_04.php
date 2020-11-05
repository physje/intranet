<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();
$sql = array();

$vars[] = array(4, 'cfgGBRPreek', 40491, 'Preekvoorziening', 'Grootboekrekening');
$vars[] = array(4, 'cfgGBR', 43855, 'Gemeenteopbouw', 'Grootboekrekening');
$vars[] = array(1, 'clusterCoordinatoren', 4, 164201, 'Cluster-coordinator');

$sql[] = "UPDATE $TableConfig SET $ConfigValue = 983803 WHERE $ConfigName like 'clusterCoordinatoren' AND $ConfigKey = 2";

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