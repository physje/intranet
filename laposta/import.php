<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/LP_functions.php');

$filename = 'subscribed_members_export_0510be5db7.txt';

$fp = fopen($filename, 'r');
$data = fread($fp, filesize($filename));
fclose($fp);

$regels = explode("\n", $data);

$regels = array_slice($regels, 1);

foreach($regels as $persoon) {
	# 3 seconden per persoon moet voldoende zijn
	set_time_limit(5);
	
	$velden = str_getcsv ($persoon, ",",'"');
	
	//foreach($velden as $key => $value) {
	//	echo $key .' -> '. $value .'<br>';
	//}
	
	$email					= $velden[0];
	$voornaam				= $velden[1];
	$tussenvoegsel	= $velden[2];
	$achternaam			= $velden[3];
	$mailing				= $velden[9];
	$tag						= $velden[26];
	
	$mailings				= explode(',', $mailing);
	$tags						= explode(',', $tag);
	
	$custom_fields_short['voornaam'] = $voornaam;
	$custom_fields_short['tussenvoegsel'] = $tussenvoegsel;
	$custom_fields_short['achternaam'] = $achternaam;
	
	echo $voornaam .' '. $achternaam;
			
	foreach($mailings as $list) {
		$list = trim($list);
		
		if($list == 'Wijkmail') {
			foreach($tags as $tag) {				
				if(substr($tag, 1, 4) == 'Wijk' AND strlen($tag) == 8) {				
					$wijk = substr($tag, -2, 1);
					lp_addMember($LPWijkListID[$wijk], $email, $custom_fields_short);
					echo ', wijk '. $wijk;
				}
			}			
		}
		
		if($list == 'Trinitas') {
			lp_addMember($LPTrinitasListID, $email, $custom_fields_short);
			echo ', trinitas';
		}
		
		if($list == 'Koningsmail') {
			lp_addMember($LPKoningsmailListID, $email, $custom_fields_short);
			echo ', koningsmail';
		}
		
		if($list == 'Maandelijkse gebedskalender') {
			lp_addMember($LPGebedMaandListID, $email, $custom_fields_short);
			echo ', gebed (m)';
		}

		if($list == 'Wekelijkse gebedskalender') {
			lp_addMember($LPGebedWeekListID, $email, $custom_fields_short);
			echo ', gebed (w)';
		}
		
		if($list == 'Dagelijkse gebedskalender') {
			lp_addMember($LPGebedDagListID, $email, $custom_fields_short);
			echo ', gebed (d)';
		}		
	}
	
	echo '<br>';	
}


?>