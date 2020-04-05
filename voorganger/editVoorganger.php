<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');

$db = connect_db();
$cfgProgDir = '../auth/';
$requiredUserGroups = array(1, 20);
include_once($cfgProgDir. "secure.php");

if(isset($_REQUEST['new'])) {
	$sql = "INSERT INTO $TableVoorganger ($VoorgangerVoor, $VoorgangerAchter) VALUES ('nieuwe', 'voorganger')";
	mysqli_query($db, $sql);
	$_REQUEST['voorgangerID'] = mysqli_insert_id($db);
	$nieuweVoorganger = true;
} else {
	$nieuweVoorganger = false;
}

if(isset($_REQUEST['voorgangerID'])) {
	if(isset($_POST['save'])) {
		if($_POST['achter'] == '') {
			$dienstBlocken[] = "Gegevens <b>niet</b> opgeslagen, achternaam is niet ingevuld";
		} elseif($_POST['init'] == '' AND $_POST['voor'] == '') {
			$dienstBlocken[] = "Gegevens <b>niet</b> opgeslagen, voornaam of initialen is niet ingevuld";
		} elseif($_POST['mail'] == '') {
			$dienstBlocken[] = "Gegevens <b>niet</b> opgeslagen, mailadres is niet ingevuld";
		} elseif($_POST['denom'] == '') {
			$dienstBlocken[] = "Gegevens <b>niet</b> opgeslagen, denominatie is niet ingevuld";
		} else {				
			$sql = "UPDATE $TableVoorganger SET ";
			$sql .= "$VoorgangerTitel = '". $_POST['titel'] ."', ";
			$sql .= "$VoorgangerVoor = '". $_POST['voor'] ."', ";
			$sql .= "$VoorgangerInit = '". $_POST['init'] ."', "; 
			$sql .= "$VoorgangerTussen = '". $_POST['tussen'] ."', ";
			$sql .= "$VoorgangerAchter = '". $_POST['achter'] ."', ";
			$sql .= "$VoorgangerTel = '". $_POST['tel'] ."', ";
			$sql .= "$VoorgangerTel2 = '". $_POST['tel2'] ."', "; 
			$sql .= "$VoorgangerPVNaam = '". $_POST['pvnaam'] ."', "; 
			$sql .= "$VoorgangerPVTel = '". $_POST['pvtel'] ."', ";
			$sql .= "$VoorgangerMail = '". $_POST['mail'] ."', "; 
			$sql .= "$VoorgangerPlaats = '". $_POST['plaats'] ."', "; 
			$sql .= "$VoorgangerDenom = '". $_POST['denom'] ."', "; 
			$sql .= "$VoorgangerOpmerking = '". $_POST['opm'] ."', ";
			$sql .= "$VoorgangerStijl = '". $_POST['stijl'] ."', ";		
			//$sql .= "$VoorgangerHonorarium = '". $_POST['honorarium'] ."', ";
			$sql .= "$VoorgangerHonorariumOld = '". $_POST['honorarium_old'] ."', ";
			$sql .= "$VoorgangerHonorariumNew = '". $_POST['honorarium_new'] ."', ";
			$sql .= "$VoorgangerHonorariumSpecial = '". $_POST['honorarium_spec'] ."', ";
			$sql .= "$VoorgangerKM = '". $_POST['km_vergoeding'] ."', ";
			$sql .= "$VoorgangerEBRelatie = '". $_POST['EB_relatie'] ."', ";		
			$sql .= "$VoorgangerAandacht = '". ($_POST['aandachtspunten'] == 'ja' ? '1' : '0') ."', ";
			$sql .= "$VoorgangerDeclaratie = '". ($_POST['declaratie'] == 'ja' ? '1' : '0') ."' ";
			$sql .= "WHERE $VoorgangerID = '". $_POST['voorgangerID'] ."'";
			
			if(mysqli_query($db, $sql)) {
				$dienstBlocken[] = "Gegevens opgeslagen";
				toLog('info', $_SESSION['ID'], '', 'Gegevens voorganger ('. $_REQUEST['voorgangerID'] .') bijgewerkt');
			} else {
				$dienstBlocken[] = "Ging iets niet goed met gegevens opslaan";
				toLog('error', $_SESSION['ID'], '', 'Gegevens voorganger ('. $_REQUEST['voorgangerID'] .') konden niet worden opgeslagen');
			}
		}
	}
	
	if(isset($_POST['voorgangerID'])) {
		$voorgangerData['titel'] = $_POST['titel'];
		$voorgangerData['voor'] = $_POST['voor'];
		$voorgangerData['init'] = $_POST['init'];
		$voorgangerData['tussen'] = $_POST['tussen'];
		$voorgangerData['achter'] = $_POST['achter'];
		$voorgangerData['tel'] = $_POST['tel'];
		$voorgangerData['tel2'] = $_POST['tel2'];
		$voorgangerData['pv_naam'] = $_POST['pvnaam'];
		$voorgangerData['pv_tel'] = $_POST['pvtel'];
		$voorgangerData['mail'] = $_POST['mail'];
		$voorgangerData['plaats'] = $_POST['plaats'];
		$voorgangerData['denom'] = $_POST['denom'];
		$voorgangerData['opm'] = $_POST['opm'];
		$voorgangerData['stijl'] = $_POST['stijl'];
		$voorgangerData['honorarium_oud'] = $_POST['honorarium_old'];
		$voorgangerData['honorarium_nieuw'] = $_POST['honorarium_new'];
		$voorgangerData['honorarium_spec'] = $_POST['honorarium_spec'];		
		$voorgangerData['km_vergoeding'] = $_POST['km_vergoeding'];
		$voorgangerData['EB-relatie'] = $_POST['EB_relatie'];
		$voorgangerData['aandacht'] = ($_POST['aandachtspunten'] == 'ja' ? '1' : '0');
		$voorgangerData['declaratie'] = ($_POST['declaratie'] == 'ja' ? '1' : '0');
	} else {		
		$firstData = getVoorgangerData($_REQUEST['voorgangerID']);
		$secondData = getDeclaratieData($_REQUEST['voorgangerID'], time());		
		$voorgangerData = array_merge($firstData, $secondData);
	}
		
	$text[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
	$text[] = "<input type='hidden' name='voorgangerID' value='". $_REQUEST['voorgangerID'] ."'>";
	$text[] = "<table border=0>";
	if($nieuweVoorganger) {
		$text[] = "<tr>";
		$text[] = "	<td colspan='2'><b>Deze voorganger verschijnt niet direct<br>in het selectie-lijstje op het rooster.<br>Daarvoor moet het rooster eerst ververst worden.</b></td>";
		$text[] = "</tr>";
	}
	$text[] = "<tr>";
	$text[] = "	<td width='50%'><h1>Preekvoorziener</h1></td>";
	if(in_array(1, getMyGroups($_SESSION['ID']))) {	
		$text[] = "	<td width='50%'><h1>Declaratie</h1></td>";
	} else {
		$text[] = "	<td width='50%'>&nbsp;</td>";
	}
	$text[] = "</tr>";		
	$text[] = "<tr>";
	$text[] = "	<td valign='top'>";
	
	# Start Preekvoorziener
	$text[] = "<table border=0>";
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
	$text[] = "	<td>Mobiel</td>";
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
	$text[] = "</table>";
	
	# Einde Preekvoorziener
	$text[] = "</td>";
	$text[] = "	<td valign='top'>";
	
	# Start Declaratie
	if(in_array(1, getMyGroups($_SESSION['ID']))) {	
		$text[] = "<table border=0>";
		$text[] = "<tr>";
		$text[] = "	<td>Honorarium 2019</td>";
		$text[] = "	<td><input type='text' name='honorarium_old' value='". $voorgangerData['honorarium_oud'] ."'> cent</td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td>Honorarium 2020</td>";
		$text[] = "	<td><input type='text' name='honorarium_new' value='". $voorgangerData['honorarium_nieuw'] ."'> cent</td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td>Honorarium<br><small>speciale gelegenheden</small></td>";
		$text[] = "	<td><input type='text' name='honorarium_spec' value='". $voorgangerData['honorarium_spec'] ."'> cent</td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td>KM-vergoeding</td>";
		$text[] = "	<td><input type='text' name='km_vergoeding' value='". $voorgangerData['km_vergoeding'] ."'> cent</td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td>eBoekhouden</td>";
		//$text[] = "	<td><input type='text' name='EB_relatie' value='". $voorgangerData['EB-relatie'] ."'></td>";
		$text[] = "	<td><select name='EB_relatie'>";
		$text[] = "	<option value=''>Selecteer relatie</option>";
		
		$sql = "SELECT * FROM $TableEBoekhouden ORDER BY $EBoekhoudenNaam";
		$result = mysqli_query($db, $sql);
		$row = mysqli_fetch_array($result);
		
		do {
			$text[] = "	<option value='". $row[$EBoekhoudenCode] ."'". ($voorgangerData['EB-relatie'] == $row[$EBoekhoudenCode] ? ' selected' : '') .">". substr($row[$EBoekhoudenNaam], 0, 35) ."</option>";
		} while($row = mysqli_fetch_array($result));
				
		$text[] = "	</select></td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td>&nbsp;</td>";
		$text[] = "	<td>&nbsp;</td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td>&nbsp;</td>";
		$text[] = "	<td>";
		$text[] = "<table border=1>";
		$text[] = "<tr>";
		$text[] = "	<td>&nbsp;</td>";
		$text[] = "	<td width='50' align='center'><b>2019</b></td>";
		$text[] = "	<td width='50' align='center'><b>2020</b></td>";
		$text[] = "	<td width='50' align='center'><b>Speciaal</b></td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td><b>GKV</b></td>";
		$text[] = "	<td align='center'>&euro; 90</td>";
		$text[] = "	<td align='center'>&euro; 90</td>";
		$text[] = "	<td align='center'>&euro; 90</td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td><b>CGK</b></td>";
		$text[] = "	<td align='center'>&euro; 90</td>";
		$text[] = "	<td align='center'>&euro; 90</td>";
		$text[] = "	<td align='center'>&euro; 180</td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td><b>NGK</b></td>";
		$text[] = "	<td align='center'>&euro; 90</td>";
		$text[] = "	<td align='center'>&euro; 110</td>";
		$text[] = "	<td align='center'>&euro; 220</td>";
		$text[] = "</tr>";
		$text[] = "</table>";
		
		$text[] = "	</td>";
		$text[] = "</tr>";
		$text[] = "</table>";
	} else {		
		$text[] = "<table border=0>";
		$text[] = "<tr>";
		$text[] = "	<td>Laatste keer voorgegaan</td>";
		//$text[] = "	<td>". strftime('%d %h, %y', $voorgangerData['last_voorgaan']) ."</td>";
		if($voorgangerData['last_voorgaan'] > 0) {
			$text[] = "	<td>". date('d-m-Y', $voorgangerData['last_voorgaan']) ."</td>";
		} else {
			$text[] = "	<td>niet</td>";
		}
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td>Laatste keer aandachtspunten ontvangen</td>";
		//$text[] = "	<td>". strftime('%d %h, %y', $voorgangerData['last_aandacht']) ."</td>";
		if($voorgangerData['last_aandacht'] > 0) {
			$text[] = "	<td>". date('d-m-Y', $voorgangerData['last_aandacht']) ."</td>";
		} else {
			$text[] = "	<td>niet</td>";
		}
		
		$text[] = "</tr>";
		$text[] = "</table>";
	}		
	
	# Einde Declaratie
	$text[] = "</td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td colspan='2'>&nbsp;</td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td>&nbsp;</td>";
	$text[] = "	<td><input type='submit' name='save' value='Opslaan'></td>";
	$text[] = "</tr>";
	$text[] = "</table>";
	$text[] = "</form>";
	
	$dienstBlocken[] = implode("\n", $text);
	
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
