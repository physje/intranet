<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();
$sql = array();	

$vars[] = array(1, 'openKerkTemplateNamen', 1, 'Standaard', 'Naam van Open Kerk Template');
$vars[] = array(1, 'openKerkTemplateNamen', 2, 'Vakantie', 'Naam van Open Kerk Template');

foreach($vars as $var) {
	$sql[] = "INSERT INTO $TableConfig ($ConfigGroep, $ConfigName, $ConfigKey, $ConfigValue, $ConfigOpmerking, $ConfigAdded) VALUES ('". $var[0] ."', '". $var[1] ."', '". $var[2] ."', '". $var[3] ."', '". $var[4] ."', ". time() .");";
}

$sql[] = "ALTER TABLE `$TableOpenKerkTemplate` ADD `$OKTemplateTemplate` int(1) NOT NULL FIRST;";
$sql[] = "UPDATE `$TableOpenKerkTemplate` SET `$OKTemplateTemplate` = 1;";

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