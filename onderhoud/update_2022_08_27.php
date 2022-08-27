<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();
$sql = array();	

$sql[] = "ALTER TABLE `$TablePastoraat` CHANGE `$PastoraatID` `$PastoraatID` INT(6) NOT NULL AUTO_INCREMENT, add PRIMARY KEY (`$PastoraatID`);";
$sql[] = "ALTER TABLE `$TablePastoraat` ADD `$PastoraatNote` TEXT NOT NULL AFTER `$PastoraatZichtPas`;";
$sql[] = "CREATE TABLE `$TablePastorVerdeling` (`$PastorVerdelingLid` int(11) NOT NULL, `$PastorVerdelingPastor` int(11) NOT NULL);";

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