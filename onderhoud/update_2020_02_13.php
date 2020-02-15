<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();
$sql = array();
/*
$vars[] = array(1, 'clusters', 1, 'Gemeenteopbouw', 'Cluster Gemeenteopbouw');
$vars[] = array(1, 'clusters', 2, 'Jeugd & Gezin', 'Cluster Jeugd & Gezin');
$vars[] = array(1, 'clusters', 3, 'Eredienst', 'Cluster Eredienst');
$vars[] = array(1, 'clusters', 4, 'Missionaire Activiteiten', 'Cluster Missionaire Activiteiten');
$vars[] = array(1, 'clusters', 5, 'Organisatie & Beheer', 'Cluster Organisatie & Beheer');
$vars[] = array(1, 'clusterCoordinatoren', 3, 108202, 'Cluster-coordinator');
$vars[] = array(1, 'clusterCoordinatoren', 2, 164201, 'Cluster-coordinator');
$vars[] = array(1, 'clusterCoordinatoren', 5, 102001, 'Cluster-coordinator');


foreach($vars as $var) {
	$sql[] = "INSERT INTO $TableConfig ($ConfigGroep, $ConfigName, $ConfigKey, $ConfigValue, $ConfigOpmerking, $ConfigAdded) VALUES ('". $var[0] ."', '". $var[1] ."', '". $var[2] ."', '". $var[3] ."', '". $var[4] ."', ". time() .");";
}
*/

$sql[] = "RENAME TABLE `eboekhouden` TO $TableEBoekhouden";
$sql[] = "ALTER TABLE $TableUsers ADD $UserEBRelatie INT(3) NOT NULL AFTER $UserWijk;";
$sql[] = "CREATE TABLE $TableEBDeclaratie ($EBDeclaratieID int(4) NOT NULL, $EBDeclaratieIndiener int(4) NOT NULL, $EBDeclaratieCluster int(1) NOT NULL, $EBDeclaratieStatus int(1) NOT NULL, $EBDeclaratieDeclaratie text NOT NULL, $EBDeclaratieTijd int(11) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
$sql[] = "ALTER TABLE $TableEBDeclaratie ADD PRIMARY KEY ($EBDeclaratieID);";
$sql[] = "ALTER TABLE $TableEBDeclaratie MODIFY $EBDeclaratieID int(4) NOT NULL AUTO_INCREMENT;";

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