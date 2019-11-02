<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();

$sql = "ALTER TABLE $TableVoorganger ADD $VoorgangerHonorarium INT(5) NOT NULL DEFAULT '9500' AFTER $VoorgangerDeclaratie, ADD $VoorgangerKM INT(3) NOT NULL DEFAULT '35' AFTER $VoorgangerHonorarium, ADD $VoorgangerVertrekpunt TEXT NOT NULL AFTER $VoorgangerKM, ADD $VoorgangerEBRelatie INT(3) NOT NULL AFTER $VoorgangerVertrekpunt;";
mysqli_query($db, $sql);

$IDS = array(
	4 => 96,
	15 => 18,
	52 => 19,
	12 => 24,
	34 => 56,
	17 => 58,
	22 => 65,
	31 => 70,
	14 => 74,
	13 => 77,
	46 => 85,
	27 => 89,
	48 => 94,
	26 => 107,
	51 => 108,
	8 => 128,
	10 => 129,
	56 => 131,
	18 => 137,
	45 => 142,
	33 => 158,
	53 => 159,
	24 => 6,
	25 => 40,
	35 => 54
);

foreach($IDS as $voorganger => $EBID) {
	$sql = "UPDATE $TableVoorganger SET $VoorgangerEBRelatie = $EBID WHERE $VoorgangerID = $voorganger";
	mysqli_query($db, $sql);
}

$sql = "ALTER TABLE $TableDiensten ADD $DienstDeclStatus INT(1) NOT NULL DEFAULT '0' AFTER $DienstCollecte_2;";
mysqli_query($db, $sql);

?>