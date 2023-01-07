<?php
include_once('../include/functions.php');
include_once('../include/EB_functions.php');
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
	# Sla predikant gegevens op
	if(isset($_POST['save_data'])) {
		if($_POST['achter'] == '') {
			$left[] = "Gegevens <b>niet</b> opgeslagen, achternaam is niet ingevuld";
		} elseif($_POST['init'] == '' AND $_POST['voor'] == '') {
			$left[] = "Gegevens <b>niet</b> opgeslagen, voornaam of initialen is niet ingevuld";
		} elseif($_POST['mail'] == '') {
			$left[] = "Gegevens <b>niet</b> opgeslagen, mailadres is niet ingevuld";
		} elseif($_POST['denom'] == '') {
			$left[] = "Gegevens <b>niet</b> opgeslagen, denominatie is niet ingevuld";
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
			$sql .= "$VoorgangerAandacht = '". ((isset($_POST['aandachtspunten']) AND $_POST['aandachtspunten'] == 'ja') ? '1' : '0') ."', ";
			$sql .= "$VoorgangerDeclaratie = '". ((isset($_POST['declaratie']) AND $_POST['declaratie'] == 'ja') ? '1' : '0') ."', ";
			$sql .= "$VoorgangerReiskosten = '". ((isset($_POST['reiskosten']) AND $_POST['reiskosten'] == 'ja') ? '1' : '0') ."' ";			
			$sql .= "WHERE $VoorgangerID = '". $_POST['voorgangerID'] ."'";
						
			if(mysqli_query($db, $sql)) {
				$top_left[] = "Gegevens opgeslagen";
				toLog('info', $_SESSION['ID'], '', 'Gegevens voorganger ('. $_REQUEST['voorgangerID'] .') bijgewerkt');
			} else {
				$top_left[] = "Ging iets niet goed met gegevens opslaan";
				toLog('error', $_SESSION['ID'], '', 'Gegevens voorganger ('. $_REQUEST['voorgangerID'] .') konden niet worden opgeslagen');
			}
		}
	}
	
	# Sla declaratie-data op
	if(isset($_POST['save_decl'])) {
		$sql = "UPDATE $TableVoorganger SET ";
		#$sql .= "$VoorgangerHonorariumOld = '". $_POST['honorarium_old'] ."', ";
		$sql .= "$VoorgangerHonorariumNew = '". $_POST['honorarium_new'] ."', ";
		$sql .= "$VoorgangerHonorarium2023 = '". $_POST['honorarium_2023'] ."', ";
		$sql .= "$VoorgangerHonorariumSpecial = '". $_POST['honorarium_spec'] ."', ";
		$sql .= "$VoorgangerKM = '". $_POST['km_vergoeding'] ."', ";
		$sql .= "$VoorgangerEBRelatie = '". $_POST['EB_relatie'] ."' ";		
		$sql .= "WHERE $VoorgangerID = '". $_POST['voorgangerID'] ."'";
		
		if(mysqli_query($db, $sql)) {
			$top_right[] = "Gegevens opgeslagen";
			toLog('info', $_SESSION['ID'], '', 'Gegevens voorganger ('. $_REQUEST['voorgangerID'] .') bijgewerkt');
		} else {
			$top_right[] = "Ging iets niet goed met gegevens opslaan";
			toLog('error', $_SESSION['ID'], '', 'Gegevens voorganger ('. $_REQUEST['voorgangerID'] .') konden niet worden opgeslagen');
		}		
	}
	
	$firstData = getVoorgangerData($_REQUEST['voorgangerID']);
	$secondData = getDeclaratieData($_REQUEST['voorgangerID'], time());		

	$voorgangerData['titel']		= getParam('titel', $firstData['titel']);
	$voorgangerData['voor']			= getParam('voor', $firstData['voor']);
	$voorgangerData['init']			= getParam('init', $firstData['init']);
	$voorgangerData['tussen']		= getParam('tussen', $firstData['tussen']);
	$voorgangerData['achter']		= getParam('achter', $firstData['achter']);
	$voorgangerData['tel']			= getParam('tel', $firstData['tel']);
	$voorgangerData['tel2']			= getParam('tel2', $firstData['tel2']);
	$voorgangerData['pv_naam']	= getParam('pvnaam', $firstData['pv_naam']);
	$voorgangerData['pv_tel']		= getParam('pvtel', $firstData['pv_tel']);
	$voorgangerData['mail']			= getParam('mail', $firstData['mail']);
	$voorgangerData['plaats']		= getParam('plaats', $firstData['plaats']);
	$voorgangerData['denom']		= getParam('denom', $firstData['denom']);
	$voorgangerData['opm']			= getParam('opm', $firstData['opm']);
	$voorgangerData['stijl']		= getParam('stijl', $firstData['stijl']);
	$voorgangerData['aandacht'] = ((isset($_POST['aandachtspunten']) AND $_POST['aandachtspunten'] == 'ja') ? '1' : $firstData['aandacht']);
	$voorgangerData['declaratie'] = ((isset($_POST['declaratie']) AND $_POST['declaratie'] == 'ja') ? '1' : $firstData['declaratie']);
	$voorgangerData['reiskosten'] = ((isset($_POST['reiskosten']) AND $_POST['reiskosten'] == 'ja') ? '1' : $firstData['reiskosten']);
	
	
	#$voorgangerData['honorarium_oud'] 	= getParam('honorarium_old', $secondData['honorarium_oud']);
	$voorgangerData['honorarium_nieuw']	= getParam('honorarium_new', $secondData['honorarium_nieuw']);
	$voorgangerData['honorarium_2023']	= getParam('honorarium_2023', $secondData['honorarium_2023']);
	$voorgangerData['honorarium_spec']	= getParam('honorarium_spec', $secondData['honorarium_spec']);
	$voorgangerData['km_vergoeding']		= getParam('km_vergoeding', $secondData['km_vergoeding']);
	$voorgangerData['EB-relatie']				= getParam('EB_relatie', $secondData['EB-relatie']);
				
	$left[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
	$left[] = "<input type='hidden' name='voorgangerID' value='". $_REQUEST['voorgangerID'] ."'>";
	
	if($nieuweVoorganger) {
		$left[] = "<b>Deze voorganger verschijnt niet direct<br>in het selectie-lijstje op het rooster.<br>Daarvoor moet het rooster eerst ververst worden.</b>";
	}

	$left[] = "<table>";
	$left[] = "<tr>";
	$left[] = "	<td>Titel</td>";
	$left[] = "	<td><input type='text' name='titel' value='". $voorgangerData['titel'] ."'></td>";
	$left[] = "</tr>";
	$left[] = "<tr>";
	$left[] = "	<td>Initialen</td>";
	$left[] = "	<td><input type='text' name='init' value='". $voorgangerData['init'] ."'></td>";
	$left[] = "</tr>";
	$left[] = "<tr>";
	$left[] = "	<td>Voornaam</td>";
	$left[] = "	<td><input type='text' name='voor' value='". $voorgangerData['voor'] ."'></td>";
	$left[] = "</tr>";
	$left[] = "<tr>";
	$left[] = "	<td>Tussenvoegsel</td>";
	$left[] = "	<td><input type='text' name='tussen' value='". $voorgangerData['tussen'] ."'></td>";
	$left[] = "</tr>";
	$left[] = "<tr>";
	$left[] = "	<td>Achternaam</td>";
	$left[] = "	<td><input type='text' name='achter' value='". $voorgangerData['achter'] ."'></td>";
	$left[] = "</tr>";
	$left[] = "<tr>";
	$left[] = "	<td>Telefoonnummer</td>";
	$left[] = "	<td><input type='text' name='tel' value='". $voorgangerData['tel'] ."'></td>";
	$left[] = "</tr>";
	$left[] = "<tr>";
	$left[] = "	<td>Mailadres</td>";
	$left[] = "	<td><input type='text' name='mail' value='". $voorgangerData['mail'] ."'></td>";
	$left[] = "</tr>";
	$left[] = "<tr>";
	$left[] = "	<td>Plaats</td>";
	$left[] = "	<td><input type='text' name='plaats' value='". $voorgangerData['plaats'] ."'></td>";
	$left[] = "</tr>";
	$left[] = "<tr>";
	$left[] = "	<td>Denominatie</td>";
	$left[] = "	<td><input type='text' name='denom' value='". $voorgangerData['denom'] ."'></td>";
	$left[] = "</tr>";
	$left[] = "<tr>";
	$left[] = "	<td>Mobiel</td>";
	$left[] = "	<td><input type='text' name='tel2' value='". $voorgangerData['tel2'] ."'></td>";
	$left[] = "</tr>";
	$left[] = "<tr>";
	$left[] = "	<td>Naam preekvoorziener</td>";
	$left[] = "	<td><input type='text' name='pvnaam' value='". $voorgangerData['pv_naam'] ."'></td>";
	$left[] = "</tr>";
	$left[] = "<tr>";
	$left[] = "	<td>Telefoon preekvoorziener</td>";
	$left[] = "	<td><input type='text' name='pvtel' value='". $voorgangerData['pv_tel'] ."'></td>";
	$left[] = "</tr>";		
	$left[] = "<tr>";
	$left[] = "	<td valign='top'>Opmerking</td>";
	$left[] = "	<td><textarea name='opm'>". $voorgangerData['opm'] ."</textarea></td>";
	$left[] = "</tr>";
	$left[] = "<tr>";
	$left[] = "	<td>Aanspreekstijl</td>";
	$left[] = "	<td><select name='stijl'>";
	$left[] = "	<option value='0'". ($voorgangerData['stijl'] == 0 ? ' selected' : '') .">Vousvoyeren</option>";
	$left[] = "	<option value='1'". ($voorgangerData['stijl'] == 1 ? ' selected' : '') .">Tutoyeren</option>";		
	$left[] = "	</select></td>";
	$left[] = "</tr>";	
	$left[] = "<tr>";
	$left[] = "	<td>&nbsp;</td>";
	$left[] = "	<td>Als bijlage meesturen :<br>";
	$left[] = "	<input type='checkbox' name='aandachtspunten' value='ja'". ($voorgangerData['aandacht'] == 1 ? ' checked' : '') ."> Aandachtspunten voor de dienst<br>";
	$left[] = "	<input type='checkbox' name='declaratie' value='ja'". ($voorgangerData['declaratie'] == 1 ? ' checked' : '') ."> Declaratie-formulier<br>";
	$left[] = "	<input type='checkbox' name='reiskosten' value='ja'". ($voorgangerData['reiskosten'] == 1 ? ' checked' : '') ."> Reiskosten-vergoeding</td>";
	$left[] = "</tr>";		
	$left[] = "</table>";
	$left[] = "<p class='after_table'><input type='submit' name='save_data' value='Gegevens opslaan'></p>";
	$left[] = "</form>";
	
	
	if(in_array(1, getMyGroups($_SESSION['ID']))) {	
		$right[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
		$right[] = "<input type='hidden' name='voorgangerID' value='". $_REQUEST['voorgangerID'] ."'>";
		$right[] = "<table>";
		$right[] = "<tr>";
		$right[] = "	<td>Honorarium t/m 2022</td>";
		$right[] = "	<td><input type='text' name='honorarium_new' value='". $voorgangerData['honorarium_nieuw'] ."'> cent</td>";
		$right[] = "</tr>";
		$right[] = "<tr>";
		$right[] = "	<td>Honorarium v.a. 2023</td>";
		$right[] = "	<td><input type='text' name='honorarium_2023' value='". $voorgangerData['honorarium_2023'] ."'> cent</td>";
		$right[] = "</tr>";		
		$right[] = "<tr>";
		$right[] = "	<td>Honorarium<br><small>speciale gelegenheden</small></td>";
		$right[] = "	<td><input type='text' name='honorarium_spec' value='". $voorgangerData['honorarium_spec'] ."'> cent</td>";
		$right[] = "</tr>";
		$right[] = "<tr>";
		$right[] = "	<td>KM-vergoeding</td>";
		$right[] = "	<td><input type='text' name='km_vergoeding' value='". $voorgangerData['km_vergoeding'] ."'> cent</td>";
		$right[] = "</tr>";
		$right[] = "<tr>";
		$right[] = "	<td>eBoekhouden</td>";		
		$right[] = "	<td><select name='EB_relatie'>";
		$right[] = "	<option value=''>Selecteer relatie</option>";
		
		$relaties = eb_getRelaties();	
		foreach($relaties as $relatieData) {
			$right[] = "	<option value='". $relatieData['code'] ."'". ($voorgangerData['EB-relatie'] == $relatieData['code'] ? ' selected' : '') .">". substr($relatieData['naam'], 0, 35) ."</option>";
		}		
				
		$right[] = "	</select></td>";
		$right[] = "</tr>";
		$right[] = "<tr>";
		$right[] = "	<td>&nbsp;</td>";
		$right[] = "	<td>&nbsp;</td>";
		$right[] = "</tr>";
		$right[] = "<tr>";
		$right[] = "	<td>&nbsp;</td>";
		$right[] = "	<td>";
		$right[] = "<table border=1>";
		$right[] = "<tr>";
		$right[] = "	<td>&nbsp;</td>";
		#$right[] = "	<td width='50' align='center'><b>2019</b></td>";
		$right[] = "	<td width='50' align='center'><b>2022</b></td>";
		$right[] = "	<td width='50' align='center'><b>2023</b></td>";
		$right[] = "	<td width='50' align='center'><b>Speciaal</b></td>";
		$right[] = "</tr>";
		$right[] = "<tr>";
		$right[] = "	<td><b>GKV</b></td>";
		#$right[] = "	<td align='center'>&euro; 90</td>";
		$right[] = "	<td align='center'>&euro; 90</td>";
		$right[] = "	<td align='center'>&euro; 110</td>";
		$right[] = "	<td align='center'>&euro; 220</td>";
		$right[] = "</tr>";
		$right[] = "<tr>";
		$right[] = "	<td><b>CGK</b></td>";
		#$right[] = "	<td align='center'>&euro; 90</td>";
		$right[] = "	<td align='center'>&euro; 90</td>";
		$right[] = "	<td align='center'>&euro; 110</td>";
		$right[] = "	<td align='center'>&euro; 220</td>";
		$right[] = "</tr>";
		$right[] = "<tr>";
		$right[] = "	<td><b>NGK</b></td>";
		#$right[] = "	<td align='center'>&euro; 90</td>";
		$right[] = "	<td align='center'>&euro; 110</td>";
		$right[] = "	<td align='center'>&euro; 110</td>";
		$right[] = "	<td align='center'>&euro; 220</td>";
		$right[] = "</tr>";
		$right[] = "</table>";
		$right[] = "	</td>";
		$right[] = "</tr>";
		$right[] = "</table>";
		$right[] = "<p class='after_table'><input type='submit' name='save_decl' value='Declaratie-data opslaan'></p>";
		$right[] = "</form>";
	} else {		
		$right[] = "<table>";
		$right[] = "<tr>";
		$right[] = "	<td>Laatste keer voorgegaan</td>";
		//$text[] = "	<td>". strftime('%d %h, %y', $voorgangerData['last_voorgaan']) ."</td>";
		if($voorgangerData['last_voorgaan'] > 0) {
			$right[] = "	<td>". date('d-m-Y', $voorgangerData['last_voorgaan']) ."</td>";
		} else {
			$right[] = "	<td>niet</td>";
		}
		$right[] = "</tr>";
		$right[] = "<tr>";
		$right[] = "	<td>Laatste keer aandachtspunten ontvangen</td>";
		//$text[] = "	<td>". strftime('%d %h, %y', $voorgangerData['last_aandacht']) ."</td>";
		if($voorgangerData['last_aandacht'] > 0) {
			$right[] = "	<td>". date('d-m-Y', $voorgangerData['last_aandacht']) ."</td>";
		} else {
			$right[] = "	<td>niet</td>";
		}
		
		$right[] = "</tr>";
		$right[] = "</table>";
	}
} else {
	$left[] = "Selecteer de voorganger waar u de gegevens van wilt wijzigen :<br>";
	$voorgangers = getVoorgangers();
	foreach($voorgangers as $voorgangerID) {
		$voorgangerData = getVoorgangerData($voorgangerID);
		$voor = ($voorgangerData['voor'] == '' ? $voorgangerData['init'] : $voorgangerData['voor']);
		$naam = $voor.' '.($voorgangerData['tussen'] == '' ? '' : $voorgangerData['tussen']. ' ').$voorgangerData['achter'];
		$left[] = "<a href='?voorgangerID=$voorgangerID'>$naam</a><br>";
	}
		
	$right[] = "<a href='?new=true'>Voeg nieuwe voorganger toe</a>";
}


echo showCSSHeader();
echo '<div class="content_vert_kolom">'.NL;
if(isset($_REQUEST['voorgangerID'])) {
	$voorgangerData = getVoorgangerData($_REQUEST['voorgangerID']);
	$voor = ($voorgangerData['voor'] == '' ? $voorgangerData['init'] : $voorgangerData['voor']);	

	echo "<h1>". $voor.' '.($voorgangerData['tussen'] == '' ? '' : $voorgangerData['tussen']. ' ').$voorgangerData['achter'] ."</h1>";
}

if(isset($top_left))	echo "<div class='content_block'>".NL. implode(NL, $top_left).NL."</div>".NL;
echo "<div class='content_block'>".NL. implode(NL, $left).NL."</div>".NL;

if(isset($top_right))	echo "<div class='content_block'>".NL. implode(NL, $top_right).NL."</div>".NL;
echo "<div class='content_block'>".NL. implode(NL, $right).NL."</div>".NL;

echo '</div> <!-- end \'content_vert_kolom\' -->'.NL;
echo showCSSFooter();
?>
