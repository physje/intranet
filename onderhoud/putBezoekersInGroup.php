<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();

$sql_delete = "DELETE FROM $TableGrpUsr WHERE $GrpUsrGroup = 50";
mysqli_query($db, $sql_delete);

$sql_wijkteam = "SELECT $WijkteamLid FROM $TableWijkteam WHERE $WijkteamRol = 4 OR $WijkteamRol = 5";
$result_wijkteam = mysqli_query($db, $sql_wijkteam);
if($row_wijkteam = mysqli_fetch_array($result_wijkteam)) {	
	do {
		$sql_insert = "INSERT INTO $TableGrpUsr ($GrpUsrGroup, $GrpUsrUser) VALUES (50, ". $row_wijkteam[$WijkteamLid] .")";
		if(mysqli_query($db, $sql_insert)) {
			toLog('debug', $row_wijkteam[$WijkteamLid], 'In groep pastoraal bezoekers geplaatst');
		}
		
	} while($row_wijkteam = mysqli_fetch_array($result_wijkteam));
}

toLog('info', '', 'Pastoraal bezoekers gevuld');

?>