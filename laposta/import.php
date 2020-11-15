<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/LP_functions.php');

# Verwijder alle lijsten (muv TestLijst) in LaPosta
# run makeLists.php (kan lokaal; check wel of alle meerkeuze opties goed doorkomen)
# run output in phpMyAdmin (online)
# Leeg de lokale 'lp_data' (online)
# run firstRun.php (online)
# https://us20.admin.mailchimp.com/lists/exports?id=55789 -> EXPORT AS CSV
# UNZIP
# pas regel 15 aan naar juiste bestandsnaam
# run import.php
$filename = 'subscribed_members_export_482dc3d96e.csv';

$fp = fopen($filename, 'r');
$data = fread($fp, filesize($filename));
fclose($fp);

$regels = explode("\n", $data);

$regels = array_slice($regels, 1);

foreach($regels as $persoon) {
	# 3 seconden per persoon moet voldoende zijn
	set_time_limit(5);
	
	$velden = str_getcsv ($persoon, ",",'"');
		
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
	
	if(in_array('"man"', $tags)) {
		$custom_fields_short['geslacht'] = 'Man';
	} elseif(in_array('"vrouw"', $tags)) {
		$custom_fields_short['geslacht'] = 'Vrouw';
	} else {
		$custom_fields_short['geslacht'] = '';
	}
	
	echo $voornaam .' '. $achternaam;
			
	foreach($mailings as $list) {
		$list = trim($list);
		
		if(lp_onList($LPLedenListID, $email)) {
			$custom_fields_short['3gkadres'] = 'Ja';
		} else {
			$custom_fields_short['3gkadres'] = 'Nee';
		}
		
		
		if($list == 'Wijkmail') {
			foreach($tags as $tag) {				
				if(substr($tag, 1, 4) == 'Wijk' AND strlen($tag) == 8) {				
					$wijk = substr($tag, -2, 1);
					if(lp_onList($LPWijkListID[$wijk], $email)) {
						lp_updateMember($LPWijkListID[$wijk], $email, $custom_fields_short);
					} else {
						lp_addMember($LPWijkListID[$wijk], $email, $custom_fields_short);
					}					
					echo ', wijk '. $wijk;
				}
			}			
		}
		
		if($list == 'Trinitas') {
			if(lp_onList($LPTrinitasListID, $email)) {
				lp_updateMember($LPTrinitasListID, $email, $custom_fields_short);
			} else {
				lp_addMember($LPTrinitasListID, $email, $custom_fields_short);
			}
			
			echo ', trinitas';
		}

		if($list == 'Wekelijkse Trinitas') {
			if(lp_onList($LPWeekTrinitasListID, $email)) {
				lp_updateMember($LPWeekTrinitasListID, $email, $custom_fields_short);
			} else {
				lp_addMember($LPWeekTrinitasListID, $email, $custom_fields_short);
			}
			
			echo ', trinitas (w)';
		}
		
		if($list == 'Adventsmail') {
			if(lp_onList($LPAdventListID, $email)) {
				lp_updateMember($LPAdventListID, $email, $custom_fields_short);
			} else {
				lp_addMember($LPAdventListID, $email, $custom_fields_short);
			}
			
			echo ', advent';
		}
				
		if($list == 'Koningsmail') {
			if(lp_onList($LPKoningsmailListID, $email)) {
				lp_updateMember($LPKoningsmailListID, $email, $custom_fields_short);
			} else {
				lp_addMember($LPKoningsmailListID, $email, $custom_fields_short);
			}			
			echo ', koningsmail';
		}
		
		if($list == 'Maandelijkse gebedskalender') {
			if(lp_onList($LPGebedMaandListID, $email)) {
				lp_updateMember($LPGebedMaandListID, $email, $custom_fields_short);
			} else {
				lp_addMember($LPGebedMaandListID, $email, $custom_fields_short);
			}
			echo ', gebed (m)';
		}

		if($list == 'Wekelijkse gebedskalender') {
			if(lp_onList($LPGebedWeekListID, $email)) {
				lp_updateMember($LPGebedWeekListID, $email, $custom_fields_short);
			} else {
				lp_addMember($LPGebedWeekListID, $email, $custom_fields_short);
			}
			echo ', gebed (w)';
		}
		
		if($list == 'Dagelijkse gebedskalender') {
			if(lp_onList($LPGebedDagListID, $email)) {
				lp_updateMember($LPGebedDagListID, $email, $custom_fields_short);
			} else {
				lp_addMember($LPGebedDagListID, $email, $custom_fields_short);
			}
			echo ', gebed (d)';
		}		
	}
	
	echo '<br>';	
}


?>