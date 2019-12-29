<?php
include_once('../include/functions.php');
include_once('../include/MC_functions.php');
include_once('../include/config.php');

# https://github.com/drewm/mailchimp-api

$db = connect_db();

# Ga op zoek naar alle personen die in een commissie zitten
# Gebruik daarvoor de tabel met de vulling van de commissies
$sql = "SELECT $TableGrpUsr.$GrpUsrUser FROM $TableGrpUsr, $TableGroups WHERE $TableGroups.$GroupID = $TableGrpUsr.$GrpUsrGroup AND $TableGroups.$GroupMCTag != '' GROUP BY $TableGrpUsr.$GrpUsrUser";
$result = mysqli_query($db, $sql);
$row = mysqli_fetch_array($result);
do {
	# 3 seconden per persoon moet voldoende zijn
	set_time_limit(3);
	
	# identifier is het id binnen scipio
	$scipioID = $row[$GrpUsrUser];
	$details = getMemberDetails($scipioID);
	$email = $details['mail'];
	
	echo makeName($scipioID, 5)." (<a href='../profiel.php?id=$scipioID' target='_new'>$scipioID</a>) ";
	
	if($email != '') {
		echo "$email<br>\n";
			
		$sql_user = "SELECT $TableGrpUsr.$GrpUsrGroup, $TableGroups.$GroupMCTag FROM $TableGrpUsr, $TableGroups WHERE $TableGroups.$GroupID = $TableGrpUsr.$GrpUsrGroup AND $TableGroups.$GroupMCTag != '' AND $TableGrpUsr.$GrpUsrUser = $scipioID";
		$result_user = mysqli_query($db, $sql_user);
		$row_user = mysqli_fetch_array($result_user);
		
		do {			
			$groep = $row_user[$GrpUsrGroup];						
			$groepData = getGroupDetails($groep);
			$tag = $groepData['tag'];
			
			echo " - ".$groepData['naam'] ."<br>\n";
			
			$sql_local = "SELECT * FROM $TableCommMC WHERE $CommMCID = $scipioID AND $CommMCGroupID = $groep";
			$result_local = mysqli_query($db, $sql_local);
			
			# Komt hij niet voor dan moet hij aan MC worden toegevoegd en aan de juiste groep worden toegekend	
			if(mysqli_num_rows($result_local) == 0) {			
				if(mc_addtag($email, $tag)) {
					toLog('info', '', $scipioID, "In MailChimp toegevoegd aan groep ". $groepData['naam']);
				} else {
					toLog('error', '', $scipioID, "Kon in MailChimp groep ". $groepData['naam'] ." niet toevoegen");
				}

				$sql_insert = "INSERT INTO $TableCommMC ($CommMCID, $CommMCGroupID, $ComMClastSeen) VALUES ($scipioID, $groep, ". time() .")";			
				if(!mysqli_query($db, $sql_insert)) {
					toLog('error', '', $scipioID, "Kon lokaal groep ". $groepData['naam'] ." niet toevoegen");					
				} else {
					toLog('info', '', $scipioID, "Lokaal groep ". $groepData['naam'] ." toegevoegd aan MailChimp");
				}				
			} else {
				$sql_update = "UPDATE $TableCommMC SET $ComMClastSeen = ". time() ." WHERE $CommMCID = $scipioID AND $CommMCGroupID = $groep";
				if(!mysqli_query($db, $sql_update)) {					
					toLog('error', '', $scipioID, "Kon lokaal mailchimp-groep ". $groepData['naam'] ." niet updaten");
				}
			}		
		} while($row_user = mysqli_fetch_array($result_user));
		echo "<br>\n";
	} else {
		echo "heeft geen mail<br>\n";
	}
} while($row = mysqli_fetch_array($result));


# Verwijder alle groepsleden die al sinds eergisteren niet meer gezien zijn
$dagen = mktime (0, 0, 0, date("n"), (date("j")-2));
$sql_unsub = "SELECT * FROM $TableCommMC WHERE $ComMClastSeen < ". $dagen;
$result_unsub = mysqli_query($db, $sql_unsub);
if($row_unsub = mysqli_fetch_array($result_unsub)) {
	do {
		set_time_limit(3);
		$scipioID = $row_unsub[$CommMCID];		
		$groep = $row_unsub[$CommMCGroupID];
				
		$memberData = getMemberDetails($scipioID);
		$groepData = getGroupDetails($groep);
		
		$email = $memberData['mail'];		
		$segment_id = $groepData['tag'];
		
		if(mc_rmtag($email, $segment_id)) {
			toLog('info', '', $scipioID, 'Verlaten '. $groepData['naam'].' gesynced naar MailChimp');
			
			if(!mysqli_query($db, "DELETE FROM $TableCommMC WHERE $CommMCID = $scipioID AND $CommMCGroupID = $groep")) {
				toLog('error', '', $scipioID, 'Kon verlaten '. $groepData['naam'].' lokaal niet verwerken');
			}			
		} else {
			toLog('error', '', $scipioID, 'Kon verlaten '. $groepData['naam'].' niet syncen naar MailChimp');
		}		
	} while($row_unsub = mysqli_fetch_array($result_unsub));
}			
		
	