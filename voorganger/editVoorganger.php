<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');

$db = connect_db();
$cfgProgDir = '../auth/';
$requiredUserGroups = array(1, 20);
include($cfgProgDir. "secure.php");

if(isset($_REQUEST['new'])) {
	$sql = "INSERT INTO $TableVoorganger ($VoorgangerVoor, $VoorgangerAchter) VALUES ('nieuwe', 'voorganger')";
	mysqli_query($db, $sql);
	$_REQUEST['voorgangerID'] = mysqli_insert_id($db);
	$nieuweVoorganger = true;
} else {
	$nieuweVoorganger = false;
}

if(isset($_REQUEST['voorgangerID'])) {
	if(isset($_REQUEST['save'])) {
		$sql = "UPDATE $TableVoorganger SET ";
		$sql .= "$VoorgangerTitel = '". $_REQUEST['titel'] ."', ";
		$sql .= "$VoorgangerVoor = '". $_REQUEST['voor'] ."', ";
		$sql .= "$VoorgangerInit = '". $_REQUEST['init'] ."', "; 
		$sql .= "$VoorgangerTussen = '". $_REQUEST['tussen'] ."', ";
		$sql .= "$VoorgangerAchter = '". $_REQUEST['achter'] ."', ";
		$sql .= "$VoorgangerTel = '". $_REQUEST['tel'] ."', ";
		$sql .= "$VoorgangerTel2 = '". $_REQUEST['tel2'] ."', "; 
		$sql .= "$VoorgangerPVNaam = '". $_REQUEST['pvnaam'] ."', "; 
		$sql .= "$VoorgangerPVTel = '". $_REQUEST['pvtel'] ."', ";
		$sql .= "$VoorgangerMail = '". $_REQUEST['mail'] ."', "; 
		$sql .= "$VoorgangerPlaats = '". $_REQUEST['plaats'] ."', "; 
		$sql .= "$VoorgangerDenom = '". $_REQUEST['denom'] ."', "; 
		$sql .= "$VoorgangerOpmerking = '". $_REQUEST['opm'] ."', ";
		$sql .= "$VoorgangerStijl = '". $_REQUEST['stijl'] ."', ";
		$sql .= "$VoorgangerAandacht = '". ($_REQUEST['aandachtspunten'] == 'ja' ? '1' : '0') ."', ";
		$sql .= "$VoorgangerDeclaratie = '". ($_REQUEST['declaratie'] == 'ja' ? '1' : '0') ."' ";
		$sql .= "WHERE $VoorgangerID = '". $_REQUEST['voorgangerID'] ."'";
		
		if(mysqli_query($db, $sql)) {
			$dienstBlocken[] = "Gegevens opgeslagen";
			toLog('info', $_SESSION['ID'], '', 'Gegevens voorganger ('. $_REQUEST['voorgangerID'] .') bijgewerkt');
		} else {
			$dienstBlocken[] = "Ging iets niet goed met gegevens opslaan";
			toLog('error', $_SESSION['ID'], '', 'Gegevens voorganger ('. $_REQUEST['voorgangerID'] .') konden niet worden opgeslagen');
		}
	} else {	
		$voorgangerData = getVoorgangerData($_REQUEST['voorgangerID']);
		
		$text[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
		$text[] = "<input type='hidden' name='voorgangerID' value='". $_REQUEST['voorgangerID'] ."'>";
		$text[] = "<table>";
		if($nieuweVoorganger) {
			$text[] = "<tr>";
			$text[] = "	<td colspan='2'><b>Deze voorganger verschijnt niet direct<br>in het selectie-lijstje op het rooster.<br>Daarvoor moet het rooster eerst ververst worden.</b></td>";
			$text[] = "</tr>";
		}
		$text[] = "<tr>";
		$text[] = "	<td>Titel</td>";
		$text[] = "	<td><input type='text' name='titel' value='". $voorgangerData['titel'] ."'></td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td>Initialen</td>";
		$text[] = "	<td><input type='text' name='init' value='". $voorgangerData['init'] ."'></td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td>Voornaam</td>";
		$text[] = "	<td><input type='text' name='voor' value='". $voorgangerData['voor'] ."'></td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td>Tussenvoegsel</td>";
		$text[] = "	<td><input type='text' name='tussen' value='". $voorgangerData['tussen'] ."'></td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td>Achternaam</td>";
		$text[] = "	<td><input type='text' name='achter' value='". $voorgangerData['achter'] ."'></td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td>Telefoonnummer</td>";
		$text[] = "	<td><input type='text' name='tel' value='". $voorgangerData['tel'] ."'></td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td>Mailadres</td>";
		$text[] = "	<td><input type='text' name='mail' value='". $voorgangerData['mail'] ."'></td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td>Plaats</td>";
		$text[] = "	<td><input type='text' name='plaats' value='". $voorgangerData['plaats'] ."'></td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td>Denominatie</td>";
		$text[] = "	<td><input type='text' name='denom' value='". $voorgangerData['denom'] ."'></td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td>Telefoonnummer 2</td>";
		$text[] = "	<td><input type='text' name='tel2' value='". $voorgangerData['tel2'] ."'></td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td>Naam preekvoorziener</td>";
		$text[] = "	<td><input type='text' name='pvnaam' value='". $voorgangerData['pv_naam'] ."'></td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td>Telefoon preekvoorziener</td>";
		$text[] = "	<td><input type='text' name='pvtel' value='". $voorgangerData['pv_tel'] ."'></td>";
		$text[] = "</tr>";		
		$text[] = "<tr>";
		$text[] = "	<td valign='top'>Opmerking</td>";
		$text[] = "	<td><textarea name='opm'>". $voorgangerData['opm'] ."</textarea></td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td>Aanspreekstijl</td>";
		$text[] = "	<td><select name='stijl'>";
		$text[] = "	<option value='0'". ($voorgangerData['stijl'] == 0 ? ' selected' : '') .">Vousvoyeren</option>";
		$text[] = "	<option value='1'". ($voorgangerData['stijl'] == 1 ? ' selected' : '') .">Tutoyeren</option>";		
		$text[] = "	</select></td>";
		$text[] = "</tr>";	
		$text[] = "<tr>";
		$text[] = "	<td>&nbsp;</td>";
		$text[] = "	<td>Als bijlage meesturen :<br>";
		$text[] = "	<input type='checkbox' name='aandachtspunten' value='ja'". ($voorgangerData['aandacht'] == 1 ? ' checked' : '') ."> Aandachtspunten voor de dienst<br>";
		$text[] = "	<input type='checkbox' name='declaratie' value='ja'". ($voorgangerData['declaratie'] == 1 ? ' checked' : '') ."> Declaratie-formulier</td>";
		$text[] = "</tr>";		
		$text[] = "<tr>";
		$text[] = "	<td>&nbsp;</td>";
		$text[] = "	<td><input type='submit' name='save' value='Opslaan'></td>";
		$text[] = "</tr>";
		$text[] = "</table>";
		$text[] = "</form>";
		
		$dienstBlocken[] = implode("\n", $text);
	}	
} else {
	$deel[] = "Selecteer de voorganger waar u de gegevens van wilt wijzigen :";
	$voorgangers = getVoorgangers();
	foreach($voorgangers as $voorgangerID) {
		$voorgangerData = getVoorgangerData($voorgangerID);
		$voor = ($voorgangerData['voor'] == '' ? $voorgangerData['init'] : $voorgangerData['voor']);
		$naam = $voor.' '.($voorgangerData['tussen'] == '' ? '' : $voorgangerData['tussen']. ' ').$voorgangerData['achter'];
		$deel[] = "<a href='?voorgangerID=$voorgangerID'>$naam</a>";
	}
	
	//$deel[] = "";
	$dienstBlocken[] = "<a href='?new=true'>Voeg nieuwe voorganger toe</a>";
	
	$dienstBlocken[] = implode("<br>", $deel);
}

echo $HTMLHeader;
echo "<table width=100% border=0>";
foreach($dienstBlocken as $block) {
	echo "<tr>";
	echo "	<td valign='top'>". showBlock($block, 100)."</td>";
	echo "</tr>";
	echo "<tr>";
	echo "	<td>&nbsp;</td>";
	echo "</tr>";
}

echo "</table>";
echo $HTMLFooter;
