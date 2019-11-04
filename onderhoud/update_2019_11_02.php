<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();

# Tabel aanpassen
$sql = "ALTER TABLE $TableRoosters CHANGE $RoostersGroep $RoostersGroep INT(3) NOT NULL, CHANGE $RoostersID $RoostersID INT(3) NOT NULL, ADD $RoostersBeheerder INT(3) NOT NULL AFTER $RoostersNaam, ADD $RoostersPlanner INT(3) NOT NULL AFTER $RoostersGroep;";
mysqli_query($db, $sql);

# Nieuwe kolommen vullen
$roosters = getRoosters();

foreach($roosters as $rooster) {
	$details = getRoosterDetails($rooster);	
	$groep = $details['groep'];
	$groepDetails = getGroupDetails($groep);
	
	$beheerder = $groepDetails['beheer'];
	$planner = $beheerder;
	
	$sql = "UPDATE $TableRoosters SET $RoostersBeheerder = $beheerder, $RoostersPlanner = $planner WHERE $RoostersID = $rooster";
	mysqli_query($db, $sql);
}

# User-Tabel aanpassen voor mailadres en password
$sql = "ALTER TABLE $TableUsers ADD $UserFormeelMail TEXT NOT NULL AFTER $UserMail, ADD $UserNewPassword TEXT NOT NULL AFTER $UserPassword;";
mysqli_query($db, $sql);

?>