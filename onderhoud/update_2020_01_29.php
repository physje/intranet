<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();
$sql = array();
$sql[] = "ALTER TABLE $TableArchief ADD $ArchiefHash TEXT NOT NULL AFTER $ArchiefNr, ADD $ArchiefSend SET('0','1') NOT NULL DEFAULT '0' AFTER $ArchiefName";
$sql[] = "UPDATE $TableArchief SET $ArchiefSend = '1'";

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