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
			
		$sql_user = "SELECT $TableGrpUsr.$GrpUsrGroup, $TableGroups.$GroupMCTag FROM $TableGrpUsr, $TableGroups WHERE $TableGroups.$GroupID = $TableGrpUsr.$GrpUsrGroup AND $TableGroups.$GroupMCTag != '' AND $GrpUsrUser = $scipioID";
		$result_user = mysqli_query($db, $sql_user);
		$row_user = mysqli_fetch_array($result_user);
		
		do {			
			$groep = $row_user[$GrpUsrGroup];
			$tag = $row_user[$GroupMCTag];
			
			echo " - $groep<br>\n";
			
			$sql_local = "SELECT * FROM $TableCommMC WHERE $CommMCID = $scipioID AND $CommMCGroupID = $groep";
			$result_local = mysqli_query($db, $sql_local);
			
			# Komt hij niet voor dan moet hij aan MC worden toegevoegd en aan de juiste wijk worden toegekend	
			if(mysqli_num_rows($result_local) == 0) {			
				mc_addtag($email, $tag);
				$sql_insert = "INSERT INTO $TableCommMC ($CommMCID, $CommMCGroupID, $ComMClastSeen) VALUES ($scipioID, $groep, ". time() .")";
				mysqli_query($db, $sql_insert);
			} else {
				$sql_update = "UPDATE $TableCommMC SET $ComMClastSeen = ". time() ." WHERE $CommMCID = $scipioID AND $CommMCGroupID = $groep";
				mysqli_query($db, $sql_update);	
			}		
		} while($row_user = mysqli_fetch_array($result_user));
	} else {
		echo "heeft geen mail<br>\n";
	}
} while($row = mysqli_fetch_array($result));
			
		
	