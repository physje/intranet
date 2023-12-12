<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

$grens = mktime(0,0,0,(date('n')-4));
$uploadDir = 'uploads';

if(isset($_REQUEST['file'])) {
	if(isset($_REQUEST['remove'])) {
		unlink('uploads/'.$_REQUEST['file']);
	} elseif(isset($_REQUEST['merge'])) {
		$sql = "SELECT * FROM $TableEBDeclaratie WHERE $EBDeclaratieHash like '". $_REQUEST['key'] ."'";
		$result = mysqli_query($db, $sql);
		$row = mysqli_fetch_array($result);
				
		$JSON = json_decode($row[$EBDeclaratieDeclaratie], true);
		
		$JSON['bijlage'][] = $uploadDir.'/'.$_REQUEST['file'];
		$JSON['bijlage_naam'][] = 'Weesbestand '. time2str('%e_%b_%Y_%H_%M');
		
		$sql_update = "UPDATE $TableEBDeclaratie SET $EBDeclaratieDeclaratie = '". encode_clean_JSON($JSON) ."' WHERE $EBDeclaratieHash like '". $_REQUEST['key'] ."'";
		mysqli_query($db, $sql_update);
	}
}

$files = scandir($uploadDir, SCANDIR_SORT_DESCENDING);

foreach($files as $file) {	
	if($file != '.' AND $file != '..' AND $file != 'index.php') {		
		# Hoe oud is het bestand
		$tijdstip = filectime('uploads/'.$file);		
		
		# Ouder dan 3 maanden? -> Verwijderen
		if($tijdstip < $grens) {
			echo $file ." is van ". time2str('%a %e %b %Y %H:%M', $tijdstip).' en verwijderd<br>';
			unlink($uploadDir.'/'.$file);
		} else {			
			# Bestaat hij in de database
			$sql_in_db = "SELECT $EBDeclaratieHash FROM $TableEBDeclaratie WHERE $EBDeclaratieDeclaratie like '%". $file ."%'";
			$result_in_db = mysqli_query($db, $sql_in_db);
			if(mysqli_num_rows($result_in_db) == 0) {
				# Welke declaratie is vlak daarna ingediend
				$sql_declaratie = "SELECT * FROM $TableEBDeclaratie WHERE $EBDeclaratieTijd > ". $tijdstip ." AND $EBDeclaratieDeclaratie NOT like '%". $file ."%' ORDER BY $EBDeclaratieTijd ASC LIMIT 0,1";
				$result_declaratie = mysqli_query($db, $sql_declaratie);
				$row_declaratie = mysqli_fetch_array($result_declaratie);
										
				echo "Hoort <a href='". $uploadDir."/". $file ."' target='file_window'>file.pdf</a> bij <a href='overzichtDeclaraties.php?key=". $row_declaratie[$EBDeclaratieHash] ."' target='declaratie_window'>deze declaratie</a> van ". makeName($row_declaratie[$EBDeclaratieIndiener], 5) ." van ". time2str('%a %e %b %Y %H:%M', $row_declaratie[$EBDeclaratieTijd]) ." (bestand is van ". time2str('%a %e %b %Y %H:%M', $tijdstip) .")<br>";
				echo "<a href='?file=$file&merge=1&key=". $row_declaratie[$EBDeclaratieHash] ."'>Ja</a> (voeg samen) | <a href='?file=$file&remove=1'>Nee</a> (verwijder)<br>";
				echo '<br>';
			}
		}		
	}	
}
