<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');

$grens = mktime(0, 0, 0, 1, 1, 2019);
#$grens = mktime(0, 0, 0, 1, 1, 1900);

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
	
	$tijd			= mktime(12,12,0,substr($velden[1], 5, 2),substr($velden[1], 8, 2),substr($velden[1], 0, 4));
	$fam_id 	= $velden[2];
	$pastor 	= trim($velden[5]);
	$note		 	= $velden[7];
	$wijk			= $velden[8];
		
	if($tijd > $grens) {
		$sql_lid = "SELECT * FROM $TablePastorConvert WHERE $PastorConvertFamID = $fam_id AND $PastorConvertScipioID > 2";
		$result_lid = mysqli_query($db, $sql_lid);
		if($row = mysqli_fetch_array($result_lid)) {
			$scipioID = $row[$PastorConvertScipioID];
			
			$sql_pastor = "SELECT * FROM $TablePastorConvertPas WHERE $PastorConvertPastor = $pastor AND $PastorConvertPastorScipio > 2";
			$result_pastor = mysqli_query($db, $sql_pastor);
			if($row_pastor = mysqli_fetch_array($result_pastor)) {
				$sql_insert = "INSERT INTO $TablePastoraat ($PastoraatIndiener, $PastoraatTijdstip, $PastoraatLid, $PastoraatType, $PastoraatLocatie, $PastoraatNote) VALUES (". $row_pastor[$PastorConvertPastorScipio] .", $tijd, $scipioID, 5, 6, '". urlencode(str_rot13($note)) ."')";
				if(!mysqli_query($db, $sql_insert)) {
					echo "Importeren van id ". $velden[0] ." ging niet goed<br>";
					echo $sql_insert ."<br>";
				}
			} else {
				echo $pastor .' ('. $velden[6] .') nog toevoegen<br>';
			}
		}
	}	
}



?>