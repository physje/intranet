<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/LP_functions.php');

# Deze alleen de eerste keer gebruiken.
# Run daarvoor wel makeLists.php om de lijsten bij LaPosta aan te maken.
# Voor reguliere sync kan je beter sync.php gebruiken.

$start = getParam('start', 0);
$stap = 5;

$db = connect_db();

$listIDs['leden']					= $LPLedenListID;
$listIDs['trinitas']			= $LPTrinitasListID;
$listIDs['koningsmail']		= $LPKoningsmailListID;
$listIDs['gebed (dag)'] 	= $LPGebedDagListID;
$listIDs['gebed (week)'] 	= $LPGebedWeekListID;
$listIDs['gebed (maand)']	= $LPGebedMaandListID;
$listIDs = $listIDs+$LPWijkListID;	

# Ga op zoek naar alle personen met een mailadres
# Mailadres is daarbij alles met een @-teken erin
$sql = "SELECT * FROM $TableUsers WHERE $UserMail like '%@%' GROUP BY $UserMail LIMIT $start, $stap";
$result = mysqli_query($db, $sql);
$row = mysqli_fetch_array($result);

# Als de stapgrootte netzo groot is als het aantal resultaten
# de pagina opnieuw opvragen
if(mysqli_num_rows($result) == $stap) {
	echo '<html>';
	echo '<head>';
	echo '	<meta http-equiv="refresh" content="0; url=?start='. ($start+$stap) .'" />';
	echo '</head>';
	echo '<body>';
} else {
	echo '<b>Laatste keer</b>';
	echo '<br>';
}

do {
	# 3 seconden per persoon moet voldoende zijn
	set_time_limit(5);
	
	# identifier is het id binnen scipio
	$scipioID = $row[$UserID];
		
	# Haal alle gegevens op
	$data = getMemberDetails($scipioID); 
	$email = $data['mail'];
	
	$custom_fields['voornaam'] = $data['voornaam'];
	$custom_fields['tussenvoegsel'] = $data['tussenvoegsel'];
	$custom_fields['achternaam'] = $data['achternaam'];
	$custom_fields['geslacht'] = ($data['geslacht'] == 'M'?'Man':'Vrouw');
	$custom_fields_short = $custom_fields;
		
	$custom_fields['wijk'] = $wijk = $data['wijk'];
	$custom_fields['geboortedatum'] = $data['geboorte'];
	$custom_fields['relatie'] = $data['relatie'];
	$custom_fields['status'] = $data['belijdenis'];
	$custom_fields['scipioid'] = $scipioID;
	
	# Komt hij niet voor dan moet hij aan LP worden toegevoegd
	#  en alle variabelen ingesteld
	if(!lp_onList($LPLedenListID, $email)) {			
		# Toevoegen aan de leden-lijst
		$addMember = lp_addMember($LPLedenListID, $email, $custom_fields);
		if($addMember === true) {				
			echo makeName($scipioID, 6) ." toegevoegd aan de LaPosta ledenlijst<br>\n";
		} else {
			toLog('error', '', $scipioID, 'ledenlijst: '. $addMember['error']);
		}
		
		# De wijzigingen aan de LP kant moeten ook verwerkt worden in mijn lokale mailchimp-database
		$sql_lp_insert = "INSERT INTO $TableLP ($LPID, $LPgeslacht, $LPmail, $LPVoornaam, $LPTussenvoegsel, $LPAchternaam, $LPwijk, $LPstatus, $LPrelatie, $LPdoop, $LPlastChecked, $LPlastSeen) VALUES ($scipioID, '". $data['geslacht'] ."', '". $data['mail'] ."', '". $data['voornaam'] ."', '". urlencode($data['tussenvoegsel']) ."', '". $data['achternaam'] ."', '". $data['wijk'] ."', 'actief', '". $data['relatie'] ."', '". $data['belijdenis'] ."', ". time() .", ". time() .")";
		if(!mysqli_query($db, $sql_lp_insert)) {			
			echo $sql_lp_insert;
			toLog('error', '', $scipioID, 'Kon na sync niets toevoegen in lokale LP-tabel');
		}		
	} else {			
		# Updaten in leden-lijst
		$updateMember = lp_updateMember($LPLedenListID, $email, $custom_fields);
		if($updateMember === true) {				
			echo makeName($scipioID, 6) ." geupdate<br>\n";
		} else {
			toLog('error', '', $scipioID, 'update: '. $updateMember['error']);
		}	
		
		$sql_update = array();
		$sql_update[] = "$LPlastSeen = ". time();
		$sql_update[] = "$LPstatus = 'actief'";
		$sql_update[] = "$LPVoornaam = '". urlencode($data['voornaam']) ."'";
		$sql_update[] = "$LPTussenvoegsel = '". urlencode($data['tussenvoegsel']) ."'";
		$sql_update[] = "$LPAchternaam = '". urlencode($data['achternaam']) ."'";
		$sql_update[] = "$LPgeslacht = '". $data['geslacht'] ."'";
		$sql_update[] = "$LPmail = '". $data['mail'] ."'";	
		$sql_update[] = "$LPwijk = '$wijk'";
		$sql_update[] = "$LPdoop = '". $data['belijdenis'] ."'";
		$sql_update[] = "$LPrelatie = '". $data['relatie'] ."'";
		
		# De wijzigingen aan de LP kant moeten ook verwerkt worden in mijn lokale mailchimp-database
		$sql_lp_update = "UPDATE $TableLP SET ". implode(', ', $sql_update)." WHERE $LPID like $scipioID";
		mysqli_query($db, $sql_lp_update);	
	}
} while($row = mysqli_fetch_array($result));
?>