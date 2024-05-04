<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();
$sql = array();

$sql[] = "CREATE TABLE $TableLogins ($LoginID smallint(6) NOT NULL, $LoginLid mediumint(9) NOT NULL, $LoginIP text NOT NULL, $LoginTijd timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp());";
$sql[] = "ALTER TABLE $TableLogins ADD PRIMARY KEY ($LoginID);";
$sql[] = "ALTER TABLE $TableLogins MODIFY $LoginID smallint(6) NOT NULL AUTO_INCREMENT;";

foreach($sql as $query) {
	echo $query;
	if(mysqli_query($db, $query)) {
		echo " -> gelukt<br>";	
	} else {
		echo "<b> -> mislukt</b><br>";	
	}
}

$vars[] = array(2, 'twoFactor_lifetime', (7*24*60*60), 'Na hoeveel tijd moet er weer om een 2FA-code gevraagd worden.');

foreach($vars as $var) {
	$sql = "INSERT INTO $TableConfig ($ConfigGroep, $ConfigName, $ConfigValue, $ConfigOpmerking, $ConfigAdded) VALUES (". $var[0] .", '". $var[1] ."', '". $var[2] ."', '". $var[3] ."', ". time() .");";
	if(mysqli_query($db, $sql)) {
		echo $var[1] .' toegevoegd<br>';
	}		
}



# Na uitvoeren bestand verwijderen
if($productieOmgeving) {
	$delen = explode('/', parse_url($_SERVER['PHP_SELF'], PHP_URL_PATH));
	unlink(end($delen));
}

?>