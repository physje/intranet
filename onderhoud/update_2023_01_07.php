<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();
$sql = array();

$sql[] = "ALTER TABLE $TableVoorganger ADD $VoorgangerHonorarium2023 INT(5) NOT NULL DEFAULT 11000 AFTER $VoorgangerHonorariumNew;";
$sql[] = "ALTER TABLE $TableVoorganger ADD $VoorgangerReiskosten set('0', '1') NOT NULL DEFAULT '0' AFTER $VoorgangerDeclaratie";
$sql[] = "UPDATE $TableVoorganger SET $VoorgangerKM = 29 WHERE $VoorgangerDenom like 'CGK'";
$sql[] = "UPDATE $TableVoorganger SET $VoorgangerReiskosten = '1' WHERE $VoorgangerDenom like 'CGK'";
$sql[] = "UPDATE $TableVoorganger SET $VoorgangerHonorariumSpecial = 22000";
$sql[] = "ALTER TABLE $TableVoorganger DROP $VoorgangerHonorariumOld;";
$sql[] = "DROP TABLE $TableMC;";
$sql[] = "DROP TABLE $TableCommMC;";

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