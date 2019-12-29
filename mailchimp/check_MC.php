<?php
include_once('../include/functions.php');
include_once('../include/MC_functions.php');
include_once('../include/config.php');

$db = connect_db();

# 1000 / 25 = 40

$count = 25;
$offset = ((fmod(date('z'),2)*24)+date('G'))*$count;

$data = mc_getmembers($offset, $count);

if(count($data) > 0) {
	foreach($data as $rij) {
		# 3 seconden per persoon moet voldoende zijn
		set_time_limit(3);
	
		$email = $rij['email'];
				
		$sql = "SELECT * FROM $TableMC WHERE $MCmail like '$email'";
		$result = mysqli_query($db, $sql);
				
		if(mysqli_num_rows($result) == 1) {
			$row = mysqli_fetch_array($result);
			$scipioID	= $row[$MCID];
			$wijk			=	$row[$MCwijk];			
			$relatie	=	$row[$MCrelatie];
			$status		=	$row[$MCdoop];
			$geslacht	=	$row[$MCgeslacht];		
			
			$segment_id 	= $tagWijk[$wijk];
			$relatie_id 	= $tagRelatie[$relatie];
			$status_id		= $tagStatus[$status];
			$geslacht_id	= $tagGeslacht[$geslacht];
			
			/*
			echo $row[$MCID] .'|'. $rij['scipio'] .'<br>';
			echo $row[$MCstatus] .'|'. $rij['status'] .'<br>';
			echo $row[$MCfname] .'|'. $rij['voornaam'] .'<br>';
			echo $row[$MCtname] .'|'. $rij['tussen'] .'<br>';	
			echo $row[$MClname] .'|'. $rij['achter'] .'<br>';	
		
			echo 'tagScipio|'. $tagScipio .'<br>';
			echo 'segment_id|'. $segment_id .'<br>';
			echo 'relatie_id|'. $relatie_id .'<br>';
			echo 'geslacht_id|'. $geslacht_id .'<br>';
			echo 'status_id|'. $status_id .'<br>';
			*/

			# 'rij' is data uit MailChimp
			# 'row' is data uit de lokale database
			
			# Algemene data			
			if($scipioID != $rij['scipio'] AND $row[$MCstatus] = 'subscribed')			toLog('error', '', $scipioID, "ScipioID in MailChimp (".$rij['scipio'].") en lokale database (". $row[$MCID] .") komen niet overeen ($email)");			
			if($row[$MCstatus] != $rij['status'] AND $row[$MCstatus] != 'block')		toLog('error', '', $scipioID, "Volgens MailChimp is $email ". $rij['status'] .", volgende de lokale database niet");			
			if($row[$MCfname] != $rij['voornaam'])																	toLog('error', '', $scipioID, "Volgens Mailchimp is de voornaam van $email ". $rij['voornaam'] .", volgens de lokale database ". $row[$MCfname]);
			if(urldecode($row[$MCtname]) != $rij['tussen'])													toLog('error', '', $scipioID, "Volgens Mailchimp is het tussenvoegsel van $email .". $rij['tussen'] .", volgens de lokale database ". $row[$MCtname]);
			if($row[$MClname] != $rij['achter'])																		toLog('error', '', $scipioID, "Volgens Mailchimp is de achternaam van $email ". $rij['achternaam'] .", volgens de lokale database ". $row[$MClname]);		
			//if($rij['hash'] == '')																									toLog('error', '', $scipioID, "Hash van $email is leeg binnen Mailchimp");			
			if(!array_key_exists($tagScipio, $rij['tags']))													toLog('error', '', $scipioID, "Scipio-tag ontbreekt in MailChimp ($email staat wel in lokale database)");
			if(!array_key_exists($segment_id, $rij['tags']) AND $wijk != '')				toLog('error', '', $scipioID, "Wijk-tag (wijk $wijk) ontbreekt in MailChimp ($email staat wel in lokale database)");	
			if(!array_key_exists($relatie_id, $rij['tags']) AND $relatie != '')			toLog('error', '', $scipioID, "Relatie-tag ($relatie) ontbreekt in MailChimp ($email staat wel in lokale database)");
			if(!array_key_exists($status_id, $rij['tags']) AND $status != '')				toLog('error', '', $scipioID, "Status-tag ($status) ontbreekt in MailChimp ($email staat wel in lokale database)");
			if(!array_key_exists($geslacht_id, $rij['tags']) AND $geslacht != '')		toLog('error', '', $scipioID, "Geslacht-tag ($geslacht) ontbreekt in MailChimp ($email staat wel in lokale database)");
			

			
			
			# Commissies
			foreach($rij['tags'] as $tag => $dummy) {
				# Aantal tags zijn voor geslacht etc.
				# Die moeten even worden uitgesloten
				if($tag != $tagScipio AND $tag != $segment_id AND	$tag != $relatie_id AND $tag != $geslacht_id AND $tag != $status_id) {
					$groep = getGroupIDbyMCtag($tag);
					
					if(is_numeric($groep)) {
						$groepData = getGroupDetails($groep);											
						$sql_local = "SELECT * FROM $TableCommMC WHERE $CommMCID = $scipioID AND $CommMCGroupID = $groep";
						$result = mysqli_query($db, $sql_local);
						
						# Combi persoon-groep bestaat niet in de lokale database
						# zal dus wel verwijderd zijn					
						if(mysqli_num_rows($result) == 0) {
							toLog('error', '', $scipioID, "Groep ". $groepData['naam'] ." uit MailChimp staat niet in lokale database");
						} elseif(mysqli_num_rows($result) == 1) {
							$sql_update = "UPDATE $TableCommMC SET $ComMClastChecked = ". time() ." WHERE $CommMCID = $scipioID AND $CommMCGroupID = $groep";
							if(!mysqli_query($db, $sql_update)) {								
								toLog('error', '', $scipioID, "Gecheckt, nog steeds lid van mailchimp-groep ". $groepData['naam'] .", maar kon lokaal niet updaten");
							}		
						}
					}
				}
			}
		} elseif(mysqli_num_rows($result) > 1) {
			toLog('error', '', $rij['scipio'], "$email komt meer dan 1x voor in de lokale database");
		} elseif(array_key_exists($tagScipio, $rij['tags']) AND $rij['status'] != 'unsubscribed') {
			toLog('error', '', $rij['scipio'], $rij['scipio'] ." komt wel voor in MailChimp, maar niet in lokale database");
		}	
	}	
}

toLog('info', '', '', "Data vanuit MailChimp naast de lokale database gelegd ($offset, $count)");

?>
