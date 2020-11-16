<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/LP_functions.php');

$start = getParam('start', 0);
$stap = 1;

$db = connect_db();

$listIDs['leden']					= $LPLedenListID;
$listIDs['trinitas']			= $LPTrinitasListID;
$listIDs['trinitas (week)']	= $LPWeekTrinitasListID;
$listIDs['trinitas (week)']	= $LPWeekTrinitasListID;
$listIDs['koningsmail']		= $LPKoningsmailListID;
$listIDs['gebed (dag)'] 	= $LPGebedDagListID;
$listIDs['gebed (week)'] 	= $LPGebedWeekListID;
$listIDs['gebed (maand)']	= $LPGebedMaandListID;

$listIDs = $listIDs+$LPWijkListID;	

# Ga op zoek naar alle personen met een mailadres
# Mailadres is daarbij alles met een @-teken erin
$sql = "SELECT * FROM $TableUsers WHERE $UserMail like '%@%' AND $UserStatus NOT LIKE 'actief' GROUP BY $UserMail LIMIT $start, $stap";
$result = mysqli_query($db, $sql);
$row = mysqli_fetch_array($result);

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
	# 5 seconden per persoon moet voldoende zijn
	set_time_limit(5);
	
	# identifier is het id binnen scipio
	$scipioID = $row[$UserID];
		
	# Haal alle gegevens op
	$data = getMemberDetails($scipioID); 
	$email = $data['mail'];
	
	foreach($listIDs as $naam => $listID) {
		if(lp_onList($listID, $email)) {
			$unsubscribeMember = lp_unsubscribeMember($listID, $email);
			if($unsubscribeMember === true) {
				toLog('debug', '', $scipioID, 'Uitgeschreven voor '. $listID);
				echo makeName($scipioID, 5);
			} else {
				toLog('error', '', $scipioID, "Uitschrijven voor $listID: ". $unsubscribeMember['error']);
			}
		}
	}
} while($row = mysqli_fetch_array($result));

toLog('info', '', '', 'Synchronisatie naar LaPosta uitgevoerd');
?>