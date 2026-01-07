<?php
include_once('../include/functions.php');
include_once('../include/EB_functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Member.php');
include_once('../Classes/Voorganger.php');
include_once('../Classes/Logging.php');

$cfgProgDir = '../auth/';
$requiredUserGroups = array(1, 20);
include($cfgProgDir. "secure.php");

$top_right = $top_keft = $left = $right = array();

$gebruiker = new Member($_SESSION['useID']);
$myGroups = $gebruiker->getTeams();

if(isset($_REQUEST['new'])) {
	$newVoorganger = new Voorganger();
	$newVoorganger->initialen = 'N.';
	$newVoorganger->voornaam = 'Nieuwe';
	$newVoorganger->achternaam = 'Voorganger';
	$newVoorganger->mail = 'mailadres';
	$newVoorganger->denominatie = 'denominatie';
	$id = $newVoorganger->save();

	if(is_numeric($id)) {
		$left[] = "Nieuwe voorganger is toegevoegd.<p>";
		toLog('Nieuwe voorganger toegevoegd');
		$_REQUEST['voorgangerID'] = $id;
	}
}

# Als er een voorgangerID bekend is (of gekozen door de gebruiker, of hierboven aangemaakt)
# Maak dan een Voorganger-object aan met alle gegevens
if(isset($_REQUEST['voorgangerID']))	$voorganger = new Voorganger($_REQUEST['voorgangerID']);

# Als er op 'Voorganger verwijderen' geklikt is
if(isset($_REQUEST['delete_data'])) {
	$voorganger->active = false;
	if($voorganger->save()) {
		$left[] = "De gegevens van ". $voorganger->getName() ." zijn niet meer zichtbaar.<p>";
		toLog($voorganger->getName() .' ('. $_REQUEST['voorgangerID'] .') is inactief gezet');
	}
	$left[] = "Ga terug naar het <a href=''>overzicht</a>.";	
} elseif(isset($_REQUEST['voorgangerID'])) {
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
			$voorganger->aanhef			= $_POST['titel'];
			$voorganger->voornaam		= $_POST['voor'];
			$voorganger->initialen		= $_POST['init'];
            $voorganger->tussenvoegsel	= $_POST['tussen'];
            $voorganger->achternaam		= $_POST['achter'];
            $voorganger->telefoon		= $_POST['tel'];
            $voorganger->mobiel			= $_POST['tel2'];
            $voorganger->preekvoorziener= $_POST['pvnaam'];
            $voorganger->preekvoorziener_telefoon = $_POST['pvtel'];
            $voorganger->mail			= $_POST['mail'];
            $voorganger->plaats			= $_POST['plaats'];
            $voorganger->denominatie	= $_POST['denom'];
            $voorganger->vousvoyeren			= ($_POST['stijl'] == 1 ? true : false);
            $voorganger->opmerkingen	= $_POST['opm'];
            $voorganger->aandachtspunt	= ((isset($_POST['aandachtspunten']) && $_POST['aandachtspunten'] == 'ja') ? true : false);
            $voorganger->declaratie		= ((isset($_POST['declaratie']) && $_POST['declaratie'] == 'ja') ? true : false);
			$voorganger->reiskosten		= ((isset($_POST['reiskosten']) && $_POST['reiskosten'] == 'ja') ? true : false);

			if($voorganger->save()) {
				$top_left[] = "Gegevens opgeslagen";
				toLog('Gegevens '. $voorganger->getName() .' ('. $_REQUEST['voorgangerID'] .') bijgewerkt');
			} else {
				$top_left[] = "Ging iets niet goed met gegevens opslaan";
				toLog('Gegevens '. $voorganger->getName() .' ('. $_REQUEST['voorgangerID'] .') konden niet worden opgeslagen', 'error');
			}
		}
	}
	
	# Sla declaratie-data op
	if(isset($_POST['save_decl'])) {
		$voorganger->honorarium_oud 	= $_POST['honorarium_oud'];
		$voorganger->honorarium			= $_POST['honorarium'];
		$voorganger->honorarium_special = $_POST['honorarium_spec'];
		$voorganger->km_vergoeding		= $_POST['km_vergoeding'];
		$voorganger->boekhoud_id		= $_POST['EB_relatie'];

		if($voorganger->save()) {
			$top_right[] = "Gegevens opgeslagen";
			toLog('Financiele gegevens '. $voorganger->getName() .' ('. $_REQUEST['voorgangerID'] .') bijgewerkt');
		} else {
			$top_right[] = "Ging iets niet goed met gegevens opslaan";
			toLog('Financiele gegevens '. $voorganger->getName() .' ('. $_REQUEST['voorgangerID'] .') konden niet worden opgeslagen', 'error');
		}
	}

	# Toon formulier voor wijzigingen				
	$left[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
	$left[] = "<input type='hidden' name='voorgangerID' value='". $voorganger->id ."'>";
	$left[] = "<table>";
	$left[] = "<tr>";
	$left[] = "	<td>Titel</td>";
	$left[] = "	<td><input type='text' name='titel' value='". getParam('titel', $voorganger->aanhef) ."'></td>";
	$left[] = "</tr>";
	$left[] = "<tr>";
	$left[] = "	<td>Initialen</td>";
	$left[] = "	<td><input type='text' name='init' value='". getParam('init', $voorganger->initialen) ."'></td>";
	$left[] = "</tr>";
	$left[] = "<tr>";
	$left[] = "	<td>Voornaam</td>";
	$left[] = "	<td><input type='text' name='voor' value='". getParam('voor', $voorganger->voornaam) ."'></td>";
	$left[] = "</tr>";
	$left[] = "<tr>";
	$left[] = "	<td>Tussenvoegsel</td>";
	$left[] = "	<td><input type='text' name='tussen' value='". getParam('tussen', $voorganger->tussenvoegsel) ."'></td>";
	$left[] = "</tr>";
	$left[] = "<tr>";
	$left[] = "	<td>Achternaam</td>";
	$left[] = "	<td><input type='text' name='achter' value='". getParam('achter', $voorganger->achternaam) ."'></td>";
	$left[] = "</tr>";
	$left[] = "<tr>";
	$left[] = "	<td>Telefoonnummer</td>";
	$left[] = "	<td><input type='text' name='tel' value='". getParam('tel', $voorganger->telefoon) ."'></td>";
	$left[] = "</tr>";
	$left[] = "<tr>";
	$left[] = "	<td>Mailadres</td>";
	$left[] = "	<td><input type='text' name='mail' value='". getParam('mail', $voorganger->mail) ."'></td>";
	$left[] = "</tr>";
	$left[] = "<tr>";
	$left[] = "	<td>Plaats</td>";
	$left[] = "	<td><input type='text' name='plaats' value='". getParam('plaats', $voorganger->plaats) ."'></td>";
	$left[] = "</tr>";
	$left[] = "<tr>";
	$left[] = "	<td>Denominatie</td>";
	$left[] = "	<td><input type='text' name='denom' value='". getParam('denom', $voorganger->denominatie) ."'></td>";
	$left[] = "</tr>";
	$left[] = "<tr>";
	$left[] = "	<td>Mobiel</td>";
	$left[] = "	<td><input type='text' name='tel2' value='". getParam('tel2', $voorganger->mobiel) ."'></td>";
	$left[] = "</tr>";
	$left[] = "<tr>";
	$left[] = "	<td>Naam preekvoorziener</td>";
	$left[] = "	<td><input type='text' name='pvnaam' value='". getParam('pvnaam', $voorganger->preekvoorziener) ."'></td>";
	$left[] = "</tr>";
	$left[] = "<tr>";
	$left[] = "	<td>Telefoon preekvoorziener</td>";
	$left[] = "	<td><input type='text' name='pvtel' value='". getParam('pvtel', $voorganger->preekvoorziener_telefoon) ."'></td>";
	$left[] = "</tr>";		
	$left[] = "<tr>";
	$left[] = "	<td valign='top'>Opmerking</td>";
	$left[] = "	<td><textarea name='opm'>". getParam('opm', $voorganger->opmerkingen) ."</textarea></td>";
	$left[] = "</tr>";
	$left[] = "<tr>";
	$left[] = "	<td>Aanspreekstijl</td>";
	$left[] = "	<td><select name='stijl'>";
	$left[] = "	<option value='0'". (getParam('stijl', ($voorganger->vousvoyeren ? '1' : '0')) ? ' selected' : '') .">Vousvoyeren</option>";
	$left[] = "	<option value='1'". (getParam('stijl', ($voorganger->vousvoyeren ? '0' : '1')) ? ' selected' : '') .">Tutoyeren</option>";		
	$left[] = "	</select></td>";
	$left[] = "</tr>";	
	$left[] = "<tr>";
	$left[] = "	<td>&nbsp;</td>";
	$left[] = "	<td>Als bijlage meesturen :<br>";
	$left[] = "	<input type='checkbox' name='aandachtspunten' value='ja'". (getParam('aandachtspunten', $voorganger->aandachtspunt )? ' checked' : '') ."> Aandachtspunten voor de dienst<br>";
	$left[] = "	<input type='checkbox' name='declaratie' value='ja'". (getParam('declaratie', $voorganger->declaratie) ? ' checked' : '') ."> Declaratie-formulier<br>";
	$left[] = "	<input type='checkbox' name='reiskosten' value='ja'". (getParam('reiskosten', $voorganger->reiskosten) ? ' checked' : '') ."> Reiskosten-vergoeding</td>";
	$left[] = "</tr>";		
	$left[] = "</table>";
	$left[] = "<p class='after_table'><input type='submit' name='save_data' value='Gegevens opslaan'>&nbsp;<input type='submit' name='delete_data' value='Voorganger verwijderen'></p>";
	$left[] = "</form>";
	
	
	if(in_array(1, $myGroups)) {	
		$right[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
		$right[] = "<input type='hidden' name='voorgangerID' value='". $voorganger->id ."'>";
		$right[] = "<table>";
		$right[] = "<tr>";
		$right[] = "	<td>Honorarium vroeger</td>";
		$right[] = "	<td><input type='text' name='honorarium_oud' value='". $voorganger->honorarium_oud."'> cent</td>";
		$right[] = "</tr>";
		$right[] = "<tr>";
		$right[] = "	<td>Honorarium nu</td>";
		$right[] = "	<td><input type='text' name='honorarium' value='". $voorganger->honorarium ."'> cent</td>";
		$right[] = "</tr>";
		$right[] = "<tr>";
		$right[] = "	<td>Honorarium<br><small>speciale gelegenheden</small></td>";
		$right[] = "	<td><input type='text' name='honorarium_spec' value='". $voorganger->honorarium_special ."'> cent</td>";
		$right[] = "</tr>";
		$right[] = "<tr>";
		$right[] = "	<td>KM-vergoeding</td>";
		$right[] = "	<td><input type='text' name='km_vergoeding' value='". $voorganger->km_vergoeding ."'> cent</td>";
		$right[] = "</tr>";
		$right[] = "<tr>";
		$right[] = "	<td>eBoekhouden</td>";		
		$right[] = "	<td><select name='EB_relatie'>";
		$right[] = "	<option value=''>Selecteer relatie</option>";
		
		$relaties = eb_getRelaties();	
		foreach($relaties as $relatieData) {
			$right[] = "	<option value='". $relatieData['code'] ."'". ($voorganger->boekhoud_id == $relatieData['code'] ? ' selected' : '') .">". substr($relatieData['naam'], 0, 40) ."</option>";
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
		if($voorganger->last_voorgaan > 0) {
			$right[] = "	<td>". date('d-m-Y', $voorganger->last_voorgaan) ."</td>";
		} else {
			$right[] = "	<td>niet</td>";
		}
		$right[] = "</tr>";
		$right[] = "<tr>";
		$right[] = "	<td>Laatste keer aandachtspunten ontvangen</td>";
		if($voorganger->last_aandacht > 0) {
			$right[] = "	<td>". date('d-m-Y', $voorganger->last_aandacht) ."</td>";
		} else {
			$right[] = "	<td>niet</td>";
		}		
		$right[] = "</tr>";
		$right[] = "<tr>";
		$right[] = "	<td>Laatste keer gegevens gecontroleerd</td>";
		if($voorganger->last_data > 0) {
			$right[] = "	<td>". date('d-m-Y', $voorganger->last_data) ."</td>";
		} else {
			$right[] = "	<td>niet</td>";
		}		
		$right[] = "</tr>";		
		$right[] = "</table>";
	}
} else {
	$left[] = "Selecteer de voorganger waar u de gegevens van wilt wijzigen :<br>";
	$voorgangers = Voorganger::getVoorgangers();

	foreach($voorgangers as $voorgangerID) {
		$voorganger = new Voorganger($voorgangerID);
		$voorganger->nameType = 4;
		$left[] = "<a href='?voorgangerID=$voorgangerID'>". $voorganger->getName()."</a><br>";
	}
		
	$right[] = "<a href='?new=true'>Voeg nieuwe voorganger toe</a>";
}


echo showCSSHeader();
echo '<div class="content_vert_kolom">'.NL;
if(isset($_REQUEST['voorgangerID'])) {
	$voorganger->nameType=4;
	echo "<h1>". $voorganger->getName() ."</h1>";
}

if(isset($top_left) AND count($top_left) > 0)		echo "<div class='content_block'>".NL. implode(NL, $top_left).NL."</div>".NL;
if(isset($left) AND count($left) > 0)						echo "<div class='content_block'>".NL. implode(NL, $left).NL."</div>".NL;
if(isset($top_right) AND count($top_right) > 0)	echo "<div class='content_block'>".NL. implode(NL, $top_right).NL."</div>".NL;
if(isset($right) AND count($right) > 0)					echo "<div class='content_block'>".NL. implode(NL, $right).NL."</div>".NL;

echo '</div> <!-- end \'content_vert_kolom\' -->'.NL;
echo showCSSFooter();
?>
