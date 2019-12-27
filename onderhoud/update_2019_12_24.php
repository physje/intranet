<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();
$sql = array();
$sql[] = "ALTER TABLE $TableConfig ADD $ConfigGroep INT(2) NOT NULL DEFAULT '1' AFTER $ConfigID;";
$sql[] = "ALTER TABLE $TableAgenda CHANGE $AgendaID $AgendaID INT(4) NOT NULL;";
$sql[] = "ALTER TABLE $TableAgenda CHANGE $AgendaOwner $AgendaOwner INT(7) NOT NULL;";
$sql[] = "ALTER TABLE $TableGroups CHANGE $GroupID $GroupID INT(2) NOT NULL;";
$sql[] = "ALTER TABLE $TableGroups CHANGE $GroupBeheer $GroupBeheer INT(2) NOT NULL;";
$sql[] = "ALTER TABLE $TableGrpUsr CHANGE $GrpUsrGroup $GrpUsrGroup INT(2) NOT NULL;";
$sql[] = "ALTER TABLE $TableGrpUsr DROP `". $GrpUsrUser ."_old`;";
$sql[] = "ALTER TABLE $TableDiensten CHANGE $DienstID $DienstID INT(4) NOT NULL;";
$sql[] = "ALTER TABLE $TableDiensten CHANGE $DienstVoorganger $DienstVoorganger INT(3) NOT NULL;";
$sql[] = "ALTER TABLE $TableLog CHANGE $LogUser $LogUser INT(7) NOT NULL;";
$sql[] = "ALTER TABLE $TableLog CHANGE $LogSubject $LogSubject INT(7) NOT NULL;";
$sql[] = "ALTER TABLE $TablePlanning CHANGE $PlanningDienst $PlanningDienst INT(4) NOT NULL;";
$sql[] = "ALTER TABLE $TablePlanning CHANGE $PlanningGroup $PlanningGroup INT(2) NOT NULL;";
$sql[] = "ALTER TABLE $TablePlanning DROP `". $PlanningUser ."_old`;";
$sql[] = "ALTER TABLE $TablePlanning CHANGE $PlanningUser $PlanningUser INT(7) NOT NULL;";
$sql[] = "ALTER TABLE $TableRoosOpm CHANGE $RoosOpmID $RoosOpmID INT(4) NOT NULL;";
$sql[] = "ALTER TABLE $TableRoosOpm CHANGE $RoosOpmRoos $RoosOpmRoos INT(2) NOT NULL;";
$sql[] = "ALTER TABLE $TableRoosOpm CHANGE $RoosOpmDienst $RoosOpmDienst INT(4) NOT NULL;";
$sql[] = "ALTER TABLE $TableWijkteam CHANGE $WijkteamLid $WijkteamLid INT(7) NOT NULL;";
$sql[] = "ALTER TABLE $TableVoorganger CHANGE $VoorgangerHonorarium $VoorgangerHonorariumOld INT(5) NOT NULL DEFAULT 9000;";
$sql[] = "ALTER TABLE $TableVoorganger ADD $VoorgangerHonorariumNew INT(5) NOT NULL NOT NULL DEFAULT 9000 AFTER $VoorgangerHonorariumOld;";
$sql[] = "ALTER TABLE $TableVoorganger ADD $VoorgangerHonorariumSpecial INT(5) NOT NULL NOT NULL DEFAULT 9000 AFTER $VoorgangerHonorariumNew;";
$sql[] = "UPDATE $TableVoorganger SET $VoorgangerHonorariumNew = 11000 WHERE $VoorgangerDenom like 'NGK'";
$sql[] = "UPDATE $TableVoorganger SET $VoorgangerHonorariumSpecial = 18000 WHERE $VoorgangerDenom like 'CGK'";
$sql[] = "UPDATE $TableVoorganger SET $VoorgangerHonorariumSpecial = 22000 WHERE $VoorgangerDenom like 'NGK'";

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