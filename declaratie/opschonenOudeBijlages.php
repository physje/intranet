<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$requiredUserGroups = array(1, 38);
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
			$bijlages = $JSON['bijlage'];
			
			foreach($bijlages as $id => $bijlage) {						
				if(file_exists($bijlage)) {
					unlink($bijlage);
					toLog('debug', '', '', 'Bijlage '. ($id+1) .' van declaratie ['. $row[$EBDeclaratieHash] .'] van '. makeName($row[$EBDeclaratieIndiener], 6) .' van '. time2str('%A %e %B %Y', $row[$EBDeclaratieTijd]) .' verwijderd');
					$JSON['bijlage_verwijderd'] = true;
				} else {
					toLog('info', '', '', 'Bijlage '. ($id+1) .' van declaratie ['. $row[$EBDeclaratieHash] .'] lijkt vermist');
					$JSON['bijlage_vermist'] = true;
				}
			}
			
			$sql_update = "UPDATE $TableEBDeclaratie SET $EBDeclaratieDeclaratie = '". encode_clean_JSON($JSON) ."' WHERE $EBDeclaratieHash like '". $row[$EBDeclaratieHash] ."'";			
			mysqli_query($db, $sql_update);
			
			echo 'Bijlages van de declaratie van '. makeName($row[$EBDeclaratieIndiener], 6) .' van '. time2str('%A %e %B %Y', $row[$EBDeclaratieTijd]) .' verwijderd';
		}	
	} while($row = mysqli_fetch_array($result));
}