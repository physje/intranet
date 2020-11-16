<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/LP_functions.php');

# Dit is om de verschillende mailinglijsten uit MailChimp over te zetten naar LaPosta
# Verwijder dus alle lijsten (muv TestLijst en Ledenlijst) in LaPosta
# run makeLists.php
# Download huidige leden via : https://us20.admin.mailchimp.com/lists/exports?id=55789 -> EXPORT AS CSV
# pas regel 15 aan naar juiste bestandsnaam
# run import.php
#
# Om de Ledenlijst te vullen, gebruik firstRun.php
#
$filename = 'subscribed_members_export_482dc3d96e.csv';

# De eerste regel bevat koppen, die hoeven we niet mee te nemen
$start = getParam('start', 1);

$importSucces = true;

$fp = fopen($filename, 'r');
$data = fread($fp, filesize($filename));
fclose($fp);

$regels = explode("\n", $data);
$aantal = count($regels);
$uitsnede = array_slice($regels, $start, 1);

foreach($uitsnede as $persoon) {
	# 3 seconden per persoon moet voldoende zijn
	set_time_limit(6);
	
	$velden = str_getcsv ($persoon, ",",'"');
		
	$email					= $velden[0];
	$voornaam				= $velden[1];
	$tussenvoegsel	= $velden[2];
	$achternaam			= $velden[3];
	$scipioID			= $velden[7];
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
	
	$naamMC = str_replace('  ', ' ', $voornaam .' '. $tussenvoegsel .' '. $achternaam);
	$naamScipio = makeName($scipioID, 5);
	
	$waarde = levenshtein($naamMC, $naamScipio);
	
	echo $naamMC ."|".$naamScipio;
	
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
					
					if(!lp_onList($LPWijkListID[$wijk], $email)) {
						$addMember = lp_addMember($LPWijkListID[$wijk], $email, $custom_fields_short);
						echo ', wijk '. $wijk;
						if(is_array($addMember))    $importSucces = false;
					}					
				}
			}
		}
		

		if($list == 'Trinitas') {
			if(!lp_onList($LPTrinitasListID, $email)) {
				$addMember = lp_addMember($LPTrinitasListID, $email, $custom_fields_short);
				echo ', trinitas';
				if(is_array($addMember))    $importSucces = false;
			}
		}

		if($list == 'Wekelijkse Trinitas') {
			if(!lp_onList($LPWeekTrinitasListID, $email)) {
				$addMember = lp_addMember($LPWeekTrinitasListID, $email, $custom_fields_short);
				echo ', trinitas (w)';
				if(is_array($addMember))    $importSucces = false;
			}
		}
		
		if($list == 'Adventsmail') {
			if(!lp_onList($LPAdventListID, $email)) {
				$addMember = lp_addMember($LPAdventListID, $email, $custom_fields_short);
				echo ', advent';
				if(is_array($addMember))    $importSucces = false;
			}
		}
				
		if($list == 'Koningsmail') {
			if(!lp_onList($LPKoningsmailListID, $email)) {
				$addMember = lp_addMember($LPKoningsmailListID, $email, $custom_fields_short);
				echo ', koningsmail';
				if(is_array($addMember))    $importSucces = false;
			}
		}
		
		if($list == 'Maandelijkse gebedskalender') {
			if(!lp_onList($LPGebedMaandListID, $email)) {
				$addMember = lp_addMember($LPGebedMaandListID, $email, $custom_fields_short);
				echo ', gebed (m)';
				if(is_array($addMember))    $importSucces = false;
			}
		}

		if($list == 'Wekelijkse gebedskalender') {
			if(!lp_onList($LPGebedWeekListID, $email)) {
				$addMember = lp_addMember($LPGebedWeekListID, $email, $custom_fields_short);
				echo ', gebed (w)';
				if(is_array($addMember))    $importSucces = false;
			}
		}
		
		if($list == 'Dagelijkse gebedskalender') {
			if(!lp_onList($LPGebedDagListID, $email)) {
				$addMember = lp_addMember($LPGebedDagListID, $email, $custom_fields_short);
				echo ', gebed (d)';
				if(is_array($addMember))    $importSucces = false;
			}
		}		
	}
	
	echo '<br>';
}

if($importSucces)   $start++;

echo '<html>';
echo '<head>';
echo '  <title>'. ($aantal-$start) .' | '. $naamScipio .'</title>';
if($aantal > $start) echo '	<meta http-equiv="refresh" content="3; url=?start='. $start .'" />';
echo '</head>';
echo '<body>';

?>
