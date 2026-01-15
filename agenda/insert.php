<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Member.php');
include_once('../Classes/Agenda.php');
include_once('../Classes/Logging.php');

$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

$onderdelen[0] = 'Startdatum';
$onderdelen[1] = 'Starttijd';
$onderdelen[2] = 'Einddatum';
$onderdelen[3] = 'Eindtijd';
$onderdelen[4] = 'Onderwerp';
//$onderdelen[5] = 'Lokatie';
$onderdelen[6] = 'Beschrijving';
$onderdelen[10] = 'Dummy';

if(isset($_POST['screen']) && $_POST['screen'] == '1') {
	foreach($onderdelen as $id => $onderdeel) {
		$k_id = array_search($id, $_POST['kolom']);
		if(is_numeric($k_id)) {
			$kolommen[$id] = $k_id;
		}		
	}
		
	foreach($_POST['veld'] as $rij) {
		$Beschrijving = $Eindtijd = $Einddatum = $Starttijd = $Startdatum = $Onderwerp = '';

		if(isset($kolommen[0]))	$Startdatum = $rij[$kolommen[0]];
		if(isset($kolommen[1]))	$Starttijd = $rij[$kolommen[1]];
		if(isset($kolommen[2]))	$Einddatum = $rij[$kolommen[2]];
		if(isset($kolommen[3]))	$Eindtijd = $rij[$kolommen[3]];
		if(isset($kolommen[4]))	$Onderwerp = $rij[$kolommen[4]];
		//$Lokatie = $rij[$kolommen[5]];
		if(isset($kolommen[6]))	$Beschrijving = $rij[$kolommen[6]];
		
		if($Startdatum == '') {
			$text[] = "Kan niet; Startdatum onbekend";
			exit;
		} elseif($Onderwerp == '') {
			$text[] = "Kan niet; Onderwerp onbekend";
			exit;
		} else {
			$sDatumArray = explode($_POST['datum'], $Startdatum);
					
			if(isset($Starttijd) && $Starttijd != '') {
				var_dump($_POST['tijd'], $Starttijd);
				$sTijdArray = explode($_POST['tijd'], $Starttijd);
				$start = mktime($sTijdArray[0], $sTijdArray[1], 0, $sDatumArray[1], $sDatumArray[0], $sDatumArray[2]);
			} else {
				$start = mktime(0, 0, 0, $sDatumArray[1], $sDatumArray[0], $sDatumArray[2]);
			}
			
			if((!isset($Einddatum) || $Einddatum == '') && (!isset($Eindtijd) || $Eindtijd== '')) {
				$eind = mktime(0, 0, 0, $sDatumArray[1], $sDatumArray[0]+1, $sDatumArray[2]);
			} elseif(isset($Einddatum) && $Einddatum != '' && (!isset($Eindtijd) || $Eindtijd== '')) {
				$eDatumArray = explode($_POST['datum'], $Einddatum);				
				$eind = mktime(0, 0, 0, $eDatumArray[1], $eDatumArray[0]+1, $eDatumArray[2]);
			} elseif((!isset($Einddatum) || $Einddatum == '') && isset($Eindtijd) && $Eindtijd != '') {
				$eTijdArray = explode($_POST['tijd'], $Eindtijd);
				$eind = mktime($eTijdArray[0], $eTijdArray[1], 0, $sDatumArray[1], $sDatumArray[0], $sDatumArray[2]);
			} elseif(isset($Einddatum) && $Einddatum != '' && isset($Eindtijd) && $Eindtijd != '') {
				$eDatumArray = explode($_POST['datum'], $Einddatum);
				$eTijdArray = explode($_POST['tijd'], $Eindtijd);
				$eind = mktime($eTijdArray[0], $eTijdArray[1], 0, $eDatumArray[1], $eDatumArray[0], $eDatumArray[2]);
			}
			
			$item = new Agenda();
			$item->eigenaar		= $_SESSION['useID'];
			$item->start		= $start;
			$item->eind			= $eind;
			$item->titel		= $Onderwerp;
			$item->beschrijving	= $Beschrijving;

			if($item->save()) {
				$text[] = $Onderwerp .' van '. time2str('d LLLL yyyy', $start) .' is opgeslagen<br>';
				toLog('Agenda-item '. $Onderwerp .' opgeslagen', 'debug');
			}
		}		
	}
} elseif(isset($_POST['screen']) && $_POST['screen'] == '0') {
	$afspraken	= explode("\n", $_POST['afspraken']);
	foreach($afspraken as $a_id => $afspraak) {
		$velden = explode(";", $afspraak);
		$maxVelden[] = count($velden);
	}
	
	$max = max($maxVelden);

	$text[] = "Geef bovenaan elke kolom aan welke informatie van de afspraak er in die kolom staat.<br>";
	$text[] = "Er moet iig een kolom zijn met de startdatum en een kolom met het onderwerp.<br>";
	$text[] = "Voor kolommen die niet gebruikt worden, kies 'Dummy'.<br>";
	$text[] = "<p>";
	$text[] = "<form method='post'>";
	$text[] = "<input type='hidden' name='screen' value='1'>";
	$text[] = "<input type='hidden' name='datum' value='". $_POST['datum'] ."'>";
	$text[] = "<input type='hidden' name='tijd' value='". $_POST['tijd'] ."'>";
	$text[] = "<table border=1>";
	$text[] = "<tr>";		
	
	for($k=0 ; $k < $max ; $k++) {
		$text[] = "<td>";
		$text[] = "	<select name='kolom[$k]'>";
		
		foreach($onderdelen as $id => $onderdeel) {
			$text[] = "		<option value='$id'>$onderdeel</option>";
		}
		
		$text[] = "	</select>";
		$text[] = "	</td>";	
	}
	$text[] = "</tr>";
	
	foreach($afspraken as $a_id => $afspraak) {
		$text[] = "<tr>";
		
		$velden = explode(";", $afspraak);
		
		for($k=0 ; $k < $max ; $k++) {			
			if(isDatum($velden[$k])) {
				$veld = guessDate($velden[$k], $_POST['datum']);
			} else {
				$veld = $velden[$k];
			}
			$text[] = "	<td>". (trim($veld) == '' ? '&nbsp;' : trim($veld)) ."</td>";
			$text[] = "	<input type='hidden' name='veld[$a_id][$k]' value='". trim($veld) ."'>";
		}
		
		$text[] = "</tr>";
	}
	$text[] = "</table>";
	$text[] = "<p>";
	$text[] = "<input type='submit' value='Voeg toe'>";
	$text[] = "</form>";
} else {
	$text[] = "Geef je afspraken in.<br>";
	$text[] = "Per afspraak een rij, en de verschillende onderdelen van de afspraak (datum, tijd, onderwerp, etc.) gescheiden door <b>;</b>";
	$text[] = "Vergeet in het rechtervak ook niet aan te geven welk scheidingsteken is gebruikt voor de datum, en welke voor de tijd.</b>";
	$text[] = "<p>";
	$text[] = "<form method='post'>";
	$text[] = "<input type='hidden' name='screen' value='0'>";
	$text[] = "<table border=1>";
	$text[] = "<tr>";
	$text[] = "	<td rowspan='3'>";
	$text[] = "	<textarea name='afspraken' rows='15' cols='75'>Voor je afspraken in....</textarea><br>";
	$text[] = "	</td>";
	$text[] = "	<td>Scheidingsteken datum :</td>";
	$text[] = "	<td><select name='datum'>";
	$text[] = "	<option value='-'>-</option>";
	$text[] = "	<option value='/'>/</option>";
	$text[] = "	<option value='_'>_</option>";
	$text[] = "	<option value='.'>.</option>";
	$text[] = "	<option value=' '> </option>";
	$text[] = "</select></td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td>Scheidingsteken tijd :</td>";
	$text[] = "	<td><select name='tijd'>";
	$text[] = "	<option value=':'>:</option>";
	$text[] = "	<option value='.'>.</option>";
	$text[] = "</select></td>";
	$text[] = "<tr>";
	$text[] = "	<td colspan='2'>&nbsp;</td>";
	$text[] = "</tr>";	
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td colspan='3'><input type='submit' value='Controleer afspraken'></td>";
	$text[] = "</tr>";
	$text[] = "</table>";
	$text[] = "</form>";
}


echo showCSSHeader();
echo '<div class="content_vert_kolom_full">'.NL;
echo "<div class='content_block'>". implode(NL, $text) ."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

?>



?>