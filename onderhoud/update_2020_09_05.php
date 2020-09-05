<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();
$sql = array();

$sql[] = "ALTER TABLE `$TableEBDeclaratie` ADD `$EBDeclaratieHash` TEXT NOT NULL AFTER `$EBDeclaratieID`;";
$sql[] = "ALTER TABLE `$TableEBDeclaratie` ADD `$EBDeclaratieTotaal` FLOAT NOT NULL AFTER `$EBDeclaratieDeclaratie`;";

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