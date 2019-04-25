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
				
		if(mysqli_num_rows($result) > 0) {
			$row = mysqli_fetch_array($result);
			$wijk	=	$row[$MCwijk];			
			$segment_id = $tagWijk[$wijk];
			
			echo $row[$MCID] .'|'. $rij['scipio'] .'<br>';
			echo $row[$MCstatus] .'|'. $rij['status'] .'<br>';
			echo $row[$MCfname] .'|'. $rij['voornaam'] .'<br>';
			echo $row[$MCtname] .'|'. $rij['tussen'] .'<br>';	
			echo $row[$MClname] .'|'. $rij['achter'] .'<br>';	
		
			
			if($row[$MCID] != $rij['scipio'])									toLog('error', '', $row[$MCID], "ScipioID in MailChimp en lokale database komen niet overeen ($email)");			
			if($row[$MCstatus] != $rij['status'])							toLog('error', '', $row[$MCID], "Volgens MailChimp is $email ". $rij['status'] .", volgende de lokale database niet");			
			if($row[$MCfname] != $rij['voornaam'])						toLog('error', '', $row[$MCID], "Volgens Mailchimp is de voornaam van $email .". $rij['voornaam'] .", volgens de lokale database ". $row[$MCfname]);
			if($row[$MCtname] != urldecode($rij['tussen']))		toLog('error', '', $row[$MCID], "Volgens Mailchimp is het tussenvoegsel van $email .". $rij['tussen'] .", volgens de lokale database ". $row[$MCtname]);
			if($row[$MClname] != $rij['achter'])							toLog('error', '', $row[$MCID], "Volgens Mailchimp is de voornaam van $email .". $rij['achternaam'] .", volgens de lokale database ". $row[$MClname]);
			if(!array_key_exists($tagScipio, $rij['tags']))		toLog('error', '', $row[$MCID], "Scipio-tag ontbreekt in MailChimp ($email staat wel in lokale database)");
			if(!array_key_exists($segment_id, $rij['tags']))	toLog('error', '', $row[$MCID], "Wijk-tag ontbreekt in MailChimp ($email staat wel in lokale database)");
		} elseif(array_key_exists($tagScipio, $rij['tags']) AND $rij['status'] != 'unsubscribed') {
			toLog('error', '', $rij['scipio'], $rij['scipio'] ." komt wel voor in MailChimp, maar niet in lokale database");
		} else {
			toLog('debug', '', '', "$email niet lokaal gevonden, maar lijkt geen probleem");
		}
	}	
}

toLog('info', '', '', "Data vanuit MailChimp naast de lokale database gelegd ($offset, $count)");

?>
