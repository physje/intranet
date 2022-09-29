<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');

$db = connect_db();
#$cfgProgDir = '../auth/';
#include($cfgProgDir. "secure.php");

$importFile = 'ch_events_v3.csv';

$handle = fopen($importFile, "r");
$contents = fread($handle, filesize($importFile));
fclose($handle);

$regels = explode("\n", $contents);
array_shift($regels);

foreach($regels as $regel) {
	$velden = str_getcsv($regel, ";", "\"");
	# "id";"date";"fam_id";"famname";"pastor";"pastorname";"shortnote";"wijk"
	# "id";"date";"fam_id";"famname";"birthday";"pastor";"pastorname";"shortnote";"wijk"
	
	$fam_id		= $velden[2];
	$famname	= str_replace('  ', ' ', $velden[3]);
	$geboorte	= $velden[4];
	$pastor 	= $velden[5];
	$naam		 	= $velden[6];
	$wijk			= $velden[8];
	
	$sql = "SELECT * FROM $TablePastorConvert WHERE $PastorConvertFamID = $fam_id";
	$result = mysqli_query($db, $sql);
	
	if(mysqli_num_rows($result) == 0) {
		$sql_insert = "INSERT INTO $TablePastorConvert ($PastorConvertFamID, $PastorConvertFamName, $PastorConvertWijk) VALUES ('$fam_id', '$famname', '$wijk')";
		mysqli_query($db, $sql_insert);
	}
	
	$sql_pas = "SELECT * FROM $TablePastorConvertPas WHERE $PastorConvertPastor = $pastor";
	$result_pas = mysqli_query($db, $sql_pas);
	
	if(mysqli_num_rows($result_pas) == 0) {
		$sql_insert_pas = "INSERT INTO $TablePastorConvertPas ($PastorConvertPastor, $PastorConvertPastorName) VALUES ('$pastor', '". $naam ."')";
		mysqli_query($db, $sql_insert_pas);
	}	
}

?>