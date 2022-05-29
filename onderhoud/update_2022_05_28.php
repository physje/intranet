<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();
$sql = array();	

$sql[] = "ALTER TABLE `$TableUsers` ADD `$UsersLastSeen` INT(11) NOT NULL AFTER `$UserLastVisit`;";

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