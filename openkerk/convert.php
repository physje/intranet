<?php
include_once('config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');

$db = connect_db();
$cfgProgDir = '../auth/';
$requiredUserGroups = array(1, 43);
include($cfgProgDir. "secure.php");

#Opschonen
$sql_delete	= "DELETE * FROM $TableOpenKerkRooster WHERE $OKRoosterStart < ". mktime(0, 0, 0, (date('n')-1));
$result 		= mysqli_query($db, $sql_delete);

$sql_delete	= "DELETE * FROM $TableOpenKerkTemplate WHERE $OKTemplateTijd = '10'";
$result 		= mysqli_query($db, $sql_delete);

# Rooster
$sql		= "SELECT $OKRoosterStart FROM $TableOpenKerkRooster GROUP BY $OKRoosterStart";
$result = mysqli_query($db, $sql);
$row		= mysqli_fetch_array($result);

do {
	$eind = $row[$OKRoosterStart] + (60*60);
	
	$sql_update = "UPDATE $TableOpenKerkRooster SET $OKRoosterEind = '$eind' WHERE $OKRoosterStart = ". $row[$OKRoosterStart];
	
	mysqli_query($db, $sql_update);	
} while($row = mysqli_fetch_array($result));

# Template
$convert[11] = 1;
$convert[12] = 2;
$convert[13] = 4;
$convert[14] = 5;
$convert[15] = 6;

foreach($convert as $old => $new) {
	$sql_update = "UPDATE $TableOpenKerkTemplate SET $OKTemplateTijd = '$new' WHERE $OKTemplateTijd = '$old'";
	mysqli_query($db, $sql_update);
}
