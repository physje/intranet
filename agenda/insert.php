<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$db = connect_db();

$onderdelen[0] = 'Startdatum';
$onderdelen[1] = 'Starttijd';
$onderdelen[2] = 'Einddatum';
$onderdelen[3] = 'Eindtijd';
$onderdelen[4] = 'Onderwerp';
//$onderdelen[5] = 'Lokatie';
$onderdelen[6] = 'Beschrijving';
$onderdelen[10] = 'Dummy';

if($_POST['screen'] == '1') {
	foreach($onderdelen as $id => $onderdeel) {
		$k_id = array_search($id, $_POST['kolom']);
		if(is_numeric($k_id)) {
			$kolommen[$id] = $k_id;
		}		
	}
		
	foreach($_POST['veld'] as $rij) {
		$Startdatum = $rij[$kolommen[0]];
		$Starttijd = $rij[$kolommen[1]];
		$Einddatum = $rij[$kolommen[2]];
		$Eindtijd = $rij[$kolommen[3]];
		$Onderwerp = $rij[$kolommen[4]];
		//$Lokatie = $rij[$kolommen[5]];
		$Beschrijving = $rij[$kolommen[6]];
		
		if($Startdatum == '') {
			echo "Kan niet; Startdatum onbekend";
			exit;
		} elseif($Onderwerp == '') {
			echo "Kan niet; Onderwerp onbekend";
			exit;
		} else {
			$sDatumArray = explode($_POST['datum'], $Startdatum);
					
			if($Starttijd != '') {
				$sTijdArray = explode($_POST['tijd'], $Starttijd);
				$start = mktime($sTijdArray[0], $sTijdArray[1], 0, $sDatumArray[1], $sDatumArray[0], $sDatumArray[2]);
			} else {
				$start = mktime(0, 0, 0, $sDatumArray[1], $sDatumArray[0], $sDatumArray[2]);
			}
			
			if($Einddatum == '' AND $Eindtijd == '') {
				$eind = mktime(0, 0, 0, $sDatumArray[1], $sDatumArray[0]+1, $sDatumArray[2]);
			} elseif($Einddatum != '' AND $Eindtijd == '') {
				$eDatumArray = explode($_POST['datum'], $Einddatum);
				$eind = mktime(0, 0, 0, $eDatumArray[1], $eDatumArray[0]+1, $eDatumArray[2]);
			} elseif($Einddatum == '' AND $Eindtijd != '') {
				$eTijdArray = explode($_POST['tijd'], $Eindtijd);
				$eind = mktime($eTijdArray[0], $eTijdArray[1], 0, $sDatumArray[1], $sDatumArray[0], $sDatumArray[2]);
			} elseif($Einddatum != '' AND $Eindtijd != '') {
				$eDatumArray = explode($_POST['datum'], $Einddatum);
				$eTijdArray = explode($_POST['tijd'], $Eindtijd);
				$eind = mktime($eTijdArray[0], $eTijdArray[1], 0, $eDatumArray[1], $eDatumArray[0], $eDatumArray[2]);
			}
			
			$query = "INSERT INTO $TableAgenda ($AgendaStart, $AgendaEind, $AgendaTitel, $AgendaDescr) VALUES ('$start', '$eind', '". urlencode($Onderwerp) ."', '". urlencode($Beschrijving) ."')";
			mysqli_query($db, $query);
		}		
	}
} elseif($_POST['screen'] == '0') {
	$afspraken	= explode("\n", $_POST['afspraken']);
	foreach($afspraken as $a_id => $afspraak) {
		$velden = explode(";", $afspraak);
		$maxVelden[] = count($velden);
	}
	
	$max = max($maxVelden);

	echo "<form method='post'>\n";
	echo "<input type='hidden' name='screen' value='1'>\n";
	echo "<input type='hidden' name='datum' value='". $_POST['datum'] ."'>\n";
	echo "<input type='hidden' name='tijd' value='". $_POST['tijd'] ."'>\n";
	echo "<table border=1>\n";
	echo "<tr>\n";		
	
	for($k=0 ; $k < $max ; $k++) {
		echo "<td>\n";
		echo "	<select name='kolom[$k]'>\n";
		
		foreach($onderdelen as $id => $onderdeel) {
			echo "		<option value='$id'>$onderdeel</option>\n";
		}
		
		echo "	</select>\n";
		echo "	</td>\n";	
	}
	echo "</tr>\n";
	
	foreach($afspraken as $a_id => $afspraak) {
		echo "<tr>\n";
		
		$velden = explode(";", $afspraak);
		
		for($k=0 ; $k < $max ; $k++) {			
			if(isDatum($velden[$k])) {
				$veld = guessDate($velden[$k], $_POST['datum']);
			} else {
				$veld = $velden[$k];
			}
			echo "	<td>". (trim($veld) == '' ? '&nbsp;' : trim($veld)) ."</td>";
			echo "	<input type='hidden' name='veld[$a_id][$k]' value='". trim($veld) ."'>\n";
		}
		
		echo "</tr>\n";
	}
	echo "</table>\n";
	echo "<p>\n";
	echo "<input type='submit' value='Voeg toe'>\n";
	echo "</form>\n";
} else {
	echo "Geef je afspraken in.<br>\n";
	echo "Elke afspraak op een rij, en de verschillende onderdelen van de afspraak gescheiden door <b>;</b>\n";
	echo "<p>\n";
	echo "<form method='post'>\n";
	echo "<input type='hidden' name='screen' value='0'>\n";
	echo "<table border=1>\n";
	echo "<tr>\n";
	echo "	<td rowspan='3'>\n";
	echo "	<textarea name='afspraken' rows='15' cols='75'>Voor je afspraken in....</textarea><br>\n";
	echo "	</td>\n";
	echo "	<td>Scheidingsteken datum :</td>";
	echo "	<td><select name='datum'>\n";
	echo "	<option value='-'>-</option>\n";
	echo "	<option value='/'>/</option>\n";
	echo "	<option value='_'>_</option>\n";
	echo "	<option value='.'>.</option>\n";
	echo "	<option value=' '> </option>\n";
	echo "</select></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td>Scheidingsteken tijd :</td>";
	echo "	<td><select name='tijd'>\n";
	echo "	<option value=':'>:</option>\n";
	echo "	<option value='.'>.</option>\n";
	echo "</select></td>\n";
	echo "<tr>\n";
	echo "	<td colspan='2'>&nbsp;</td>\n";
	echo "</tr>\n";	
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td colspan='3'><input type='submit' value='Controleer afspraken'></td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "</form>\n";
}

function guessDate($string, $scheiding) {	
	$string = trim($string);
	$string = str_ireplace('zondag ', '', $string);
	$string = str_ireplace('maandag ', '', $string);
	$string = str_ireplace('dinsdag ', '', $string);
	$string = str_ireplace('woensdag ', '', $string);
	$string = str_ireplace('donderdag ', '', $string);
	$string = str_ireplace('vrijdag ', '', $string);
	$string = str_ireplace('zaterdag ', '', $string);		
	$string = str_ireplace('januari', $scheiding.'01'.$scheiding, $string);
	$string = str_ireplace('februari', $scheiding.'02'.$scheiding, $string);
	$string = str_ireplace('maart', $scheiding.'03'.$scheiding, $string);
	$string = str_ireplace('april', $scheiding.'04'.$scheiding, $string);
	$string = str_ireplace('mei', $scheiding.'05'.$scheiding, $string);
	$string = str_ireplace('juni', $scheiding.'06'.$scheiding, $string);
	$string = str_ireplace('juli', $scheiding.'07'.$scheiding, $string);
	$string = str_ireplace('augustus', $scheiding.'08'.$scheiding, $string);
	$string = str_ireplace('september', $scheiding.'09'.$scheiding, $string);
	$string = str_ireplace('oktober', $scheiding.'10'.$scheiding, $string);
	$string = str_ireplace('november', $scheiding.'11'.$scheiding, $string);
	$string = str_ireplace('december', $scheiding.'12'.$scheiding, $string);
	$string = str_ireplace('sept.', $scheiding.'09'.$scheiding, $string);
	$string = str_ireplace('jan.', $scheiding.'01'.$scheiding, $string);
	$string = str_ireplace('feb.', $scheiding.'02'.$scheiding, $string);
	$string = str_ireplace('mrt.', $scheiding.'03'.$scheiding, $string);
	$string = str_ireplace('apr.', $scheiding.'04'.$scheiding, $string);
	$string = str_ireplace('mei.', $scheiding.'05'.$scheiding, $string);
	$string = str_ireplace('jun.', $scheiding.'06'.$scheiding, $string);
	$string = str_ireplace('jul.', $scheiding.'07'.$scheiding, $string);
	$string = str_ireplace('aug.', $scheiding.'08'.$scheiding, $string);
	$string = str_ireplace('sep.', $scheiding.'09'.$scheiding, $string);
	$string = str_ireplace('okt.', $scheiding.'10'.$scheiding, $string);
	$string = str_ireplace('nov.', $scheiding.'11'.$scheiding, $string);
	$string = str_ireplace('dec.', $scheiding.'12'.$scheiding, $string);
	$string = str_ireplace('sept', $scheiding.'09'.$scheiding, $string);
	$string = str_ireplace('jan', $scheiding.'01'.$scheiding, $string);
	$string = str_ireplace('feb', $scheiding.'02'.$scheiding, $string);
	$string = str_ireplace('mrt', $scheiding.'03'.$scheiding, $string);
	$string = str_ireplace('apr', $scheiding.'04'.$scheiding, $string);
	$string = str_ireplace('mei', $scheiding.'05'.$scheiding, $string);
	$string = str_ireplace('jun', $scheiding.'06'.$scheiding, $string);
	$string = str_ireplace('jul', $scheiding.'07'.$scheiding, $string);
	$string = str_ireplace('aug', $scheiding.'08'.$scheiding, $string);
	$string = str_ireplace('sep', $scheiding.'09'.$scheiding, $string);
	$string = str_ireplace('okt', $scheiding.'10'.$scheiding, $string);
	$string = str_ireplace('nov', $scheiding.'11'.$scheiding, $string);
	$string = str_ireplace('dec', $scheiding.'12'.$scheiding, $string);
	
	$string = str_replace(' '.$scheiding, $scheiding, $string);
	$string = str_replace($scheiding.' ', $scheiding, $string);
	$string = str_replace(' ', '', $string);
	
	$delen = explode($scheiding, $string);
	if(count($delen) == 3) {
		if($delen[2] == '') {
			if(mktime(0,0,0,$delen[1],$delen[0],date('Y')) < time()){
				$delen[2] = date('Y')+1;
			} else {
				$delen[2] = date('Y');
			}
		}		
		$string = implode('-', $delen);
	}
	
	return $string;
}

function columnArray($array, $column) {
	foreach($array as $key => $sub_array) {
		$output[$key] = $sub_array[$column];
	}

	return $output;
}

function isDatum($string) {
	/*
	if(strpos($string, 'zondag')) return true;
	if(strpos($string, 'maandag')) return true;
	if(strpos($string, 'dinsdag')) return true;
	if(strpos($string, 'woensdag')) return true;
	if(strpos($string, 'donderdag')) return true;
	if(strpos($string, 'vrijdag')) return true;
	if(strpos($string, 'zaterdag')) return true;
	*/
	if(strpos($string, 'januari')) return true;
	if(strpos($string, 'februari')) return true;
	if(strpos($string, 'maart')) return true;
	if(strpos($string, 'april')) return true;
	if(strpos($string, 'mei')) return true;
	if(strpos($string, 'juni')) return true;
	if(strpos($string, 'juli')) return true;
	if(strpos($string, 'augustus')) return true;
	if(strpos($string, 'september')) return true;
	if(strpos($string, 'oktober')) return true;
	if(strpos($string, 'november')) return true;
	if(strpos($string, 'december')) return true;
	if(strpos($string, 'sept')) return true;
	if(strpos($string, 'jan')) return true;
	if(strpos($string, 'feb')) return true;
	if(strpos($string, 'mrt')) return true;
	if(strpos($string, 'apr')) return true;
	if(strpos($string, 'mei')) return true;
	if(strpos($string, 'jun')) return true;
	if(strpos($string, 'jul')) return true;
	if(strpos($string, 'aug')) return true;
	if(strpos($string, 'sep')) return true;
	if(strpos($string, 'okt')) return true;
	if(strpos($string, 'nov')) return true;
	if(strpos($string, 'dec')) return true;
	
	return false;
}



?>