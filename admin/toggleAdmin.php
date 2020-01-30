<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

$authorisatieArray = getMyGroups($_SESSION['ID']);

# Verwijder
if(in_array(1, $authorisatieArray)) {
	$sql = "DELETE FROM $TableGrpUsr WHERE $GrpUsrGroup = 1 AND $GrpUsrUser = ". $_SESSION['ID'];
	
# Voeg toe
} else {
	$sql = "INSERT INTO $TableGrpUsr ($GrpUsrGroup, $GrpUsrUser) VALUES (1, ". $_SESSION['ID'] .")";
}

mysqli_query($db, $sql);

?>