<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');

$db = connect_db();
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

# Als bekend is welke wijk
# Dan checken wie er in het wijkteam zitten van die wijk
if(isset($_REQUEST['ID'])) {
	$data			= getMemberDetails($_REQUEST['ID']);
	$wijk			= $data['wijk'];
	$wijkteam = getWijkteamLeden($wijk);
	$pastor		= getPastor($_REQUEST['ID']);
		
	$inWijkteam = false;	
	
	if(array_key_exists($_SESSION['ID'], $wijkteam)) {
		$rol = $wijkteam[$_SESSION['ID']];
		$inWijkteam = true;		
	}
	
	if(in_array(49, getMyGroups($_SESSION['ID']))) {	
		$inWijkteam = true;
		$rol = 1;
	}
	
	# Zit je in het wijkteam, dan mag je verder
	if(($inWijkteam AND $rol <> 3 AND $rol <> 6) OR $_SESSION['ID'] == $pastor) {		
		$bezoeken = getPastoraleBezoeken($_REQUEST['ID'], $_SESSION['ID']);
		
		if(count($bezoeken) > 0) {
			$text[] = "<table>";
			$text[] = "<tr>";
			$text[] = "	<td>&nbsp;</td>";
			$text[] = "	<td><b>Datum</b></td>";
			$text[] = "	<td>&nbsp;</td>";
			$text[] = "	<td><b>Door</b></td>";
			$text[] = "	<td>&nbsp;</td>";
			$text[] = "	<td><b>Type</b></td>";
			$text[] = "	<td>&nbsp;</td>";
			$text[] = "	<td><b>Locatie</b></td>";
			$text[] = "	<td>&nbsp;</td>";
			$text[] = "	<td><b>Aantekening</b></td>";
			$text[] = "</tr>";
			
			foreach($bezoeken as $bezoek) {
				$details = getPastoraalbezoekDetails($bezoek);
				
				$text[] = "<tr>";
				if($details['indiener'] == $_SESSION['ID']) {
					$text[] = "	<td><a href='edit.php?id=$bezoek'><img src='../images/wisselen.png' height='16' title='Wijzig dit bezoek'></a></td>";
				} else {
					$text[] = "	<td>&nbsp;</td>";
				}
				$text[] = "	<td>". time2str("%e %B %Y", $details['datum']) ."</td>";
				$text[] = "	<td>&nbsp;</td>";
				$text[] = "	<td>". makeName($details['indiener'], 5) ."</td>";
				$text[] = "	<td>&nbsp;</td>";
				$text[] = "	<td>". $typePastoraat[$details['type']] ."</td>";
				$text[] = "	<td>&nbsp;</td>";
				$text[] = "	<td>". $locatiePastoraat[$details['locatie']] ."</td>";
				$text[] = "	<td>&nbsp;</td>";
				$text[] = "	<td>". str_rot13(urldecode($details['note'])) ."</td>";
				$text[] = "</tr>";
			}
			$text[] = "</table>";
		} else {
			$text[] = "Geen bezoeken geregistreerd";
		}
		
	} else {
		$text[] = "Foei, mag jij hier wel komen";
	}
} else {
	$text[] = "Geen lid bekend";
}


echo $HTMLHeader;
echo implode("\n", $text);
echo $HTMLFooter;

?>