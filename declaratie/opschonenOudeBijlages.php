<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

$grens = mktime(0,0,0,(date('n')-3));

# Vraag declaraties op waar de laatste actie 3 maanden geleden is en die de status afgerond (5) hebben
$sql = "SELECT * FROM $TableEBDeclaratie WHERE $EBDeclaratieLastAction < $grens AND $EBDeclaratieStatus = 5";
$result = mysqli_query($db, $sql);

if($row = mysqli_fetch_array($result)) {
	do {
		$JSON = json_decode($row[$EBDeclaratieDeclaratie], true);
		
		if(!isset($JSON['bijlage_verwijderd']) AND !isset($JSON['bijlage_vermist'])) {
			$bijlage = $JSON['bijlage'][0];
		
			if(file_exists($bijlage)) {
				unlink($bijlage);
				toLog('info', '', '', 'Bijlages van declaratie ['. $row[$EBDeclaratieHash] .'] van '. makeName($row[$EBDeclaratieIndiener], 15) .' van '. time2str('%A %e %B', $row[$EBDeclaratieTijd]) .' verwijderd');
				$JSON['bijlage_verwijderd'] = true;
			} else {
				toLog('info', '', '', 'Bijlage van declaratie ['. $row[$EBDeclaratieHash] .'] lijkt vermist');
				$JSON['bijlage_vermist'] = true;
			}
		}	
	} while($row = mysqli_fetch_array($result));
}