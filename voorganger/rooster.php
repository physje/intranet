<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Member.php');
include_once('../Classes/Kerkdienst.php');
include_once('../Classes/Voorganger.php');
include_once('../Classes/Logging.php');

$requiredUserGroups = array(1, 20);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

# Als er op een knop gedrukt is, het rooster wegschrijven
if(isset($_POST['save']) OR isset($_POST['maanden'])) {	
	foreach($_POST['voorganger'] as $dienstID => $voorgangerID) {
		$dienst = new Kerkdienst($dienstID);
		$oudeVoorganger = $dienst->voorganger;
		$dienst->voorganger = $voorgangerID;
		
		if(isset($_POST['ruiling'][$dienstID])) {
			$dienst->ruiling = true;
		} else {
			$dienst->ruiling = false;
		}

		if(!$dienst->save()) {
			$text[] = "Ging iets niet goed met geegevens opslaan";
			toLog('Gegevens voorganger ('. $_REQUEST['voorgangerID'] .") konden niet worden gekoppeld aan dienst $dienstID", 'error');
		}		
		
		# Mocht een voorganger wijzigen, zet dat dan ook even in de logfiles
		if($oudeVoorganger != '' && $oudeVoorganger > 0 && $oudeVoorganger != $voorgangerID) {
			$old = new Voorganger($oudeVoorganger);
			$new = new Voorganger($voorgangerID);
			toLog("Voorganger van ". date("d-m-y", $dienst->start) ." gewijzigd van ". $old->getName() ." naar ". $new->getName());
		}
	}
	toLog('Diensten bijgewerkt');
}

# Als er op de knop van 3 maanden extra geklikt is, 3 maanden bij de eindtijd toevoegen
# Eerst initeren, event. later ophogen
if(isset($_POST['blokken'])) {
	$blokken = $_POST['blokken'];
} else {
	$blokken = 1;
}

if(isset($_POST['maanden'])) {
	$blokken++;
}

# Haal alle kerkdiensten binnen een tijdsvak op
$diensten = Kerkdienst::getDiensten(mktime(0,0,0,(date("n")-1)), mktime(date("H"),date("i"),date("s"),(date("n")+(3*$blokken))));

# Haal alle voorgangers (regulier en frequente -meer dan 3x-) op en maak een namen-array
$voorgangers = Voorganger::getVoorgangers();
$freqVoorgangers = Voorganger::getFrequenteVoorgangers();

foreach($voorgangers as $voorgangerID) {
	$voorganger = new Voorganger($voorgangerID);
	$voorganger->nameType = 4;
	$voorgangersNamen[$voorgangerID] = $voorganger->getName();
}

# Bouw formulier op
$text[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
$text[] = "<input type='hidden' name='blokken' value='$blokken'>";
$text[] = "<table border=0>";
$text[] = "<thead>";
$text[] = "<tr>";
$text[] = "	<th>Datum</th>";
$text[] = "	<th>Start</th>";
$text[] = "	<th>Voorganger</th>";
$text[] = "	<th>Ruiling</th>";
$text[] = "	<th>Bijzonderheid</th>";
$text[] = "</tr>";
$text[] = "</thead>";

foreach($diensten as $dienstID) {
	$dienst = new Kerkdienst($dienstID);
	
	$text[] = "<tr>";
	$text[] = "	<td align='right'>". time2str("D d M", $dienst->start) ."</td>";
	$text[] = "	<td>". date('H:i', $dienst->start) ."</td>";
	$text[] = "	<td>";
	$text[] = "<select name='voorganger[$dienstID]'>";
	$text[] = "	<option value='0'></option>";
	
	$text[] = "	<optgroup label=\"Frequent\">";
	foreach($freqVoorgangers as $voorgangerID) {
		$naam = $voorgangersNamen[$voorgangerID];
		$text[] = "	<option value='$voorgangerID'". ($dienst->voorganger == $voorgangerID ? ' selected' : '') .">$naam</option>";
	}
	$text[] = "</optgroup>";
	
	# En de overige voorgangers
	$text[] = "	<optgroup label=\"Minder frequent\">";	
	foreach($voorgangersNamen as $voorgangerID => $naam) {
		if(!in_array($voorgangerID, $freqVoorgangers))	$text[] = "	<option value='$voorgangerID'". ($dienst->voorganger == $voorgangerID ? ' selected' : '') .">$naam</option>";
	}
	$text[] = "</optgroup>";	
	$text[] = "</select>";	
	if($dienst->voorganger > 0) {		
		$voorganger = new Voorganger($dienst->voorganger);
	} else {
		$text[] = "&nbsp;<a href='edit.php?new=ja' target='_blank'><img src='../images/invite.gif' title='Open een nieuw scherm om missende voorganger toe te voegen'></a>";
	}
	$text[] = "	</td>";
	$text[] = "	<td><input type='checkbox' name='ruiling[$dienstID]' value='1'". ($dienst->ruiling ? ' checked': '').($voorganger->plaats == 'Deventer' ? ' disabled' : '') ."></td>";
	$text[] = "	<td>". ($dienst->opmerking != '' ? $dienst->opmerking : '&nbsp;') ."</td>";
	$text[] = "</tr>";
}
$text[] = "</table>";
$text[] = "<p class='after_table'><input type='submit' name='save' value='Diensten opslaan'>&nbsp;<input type='submit' name='maanden' value='Volgende 3 maanden'></p>";
$text[] = "</form>";


$header[] = '<style>';
$header[] = '@media only screen and (max-width: 760px), (min-device-width: 768px) and (max-device-width: 1024px)  {';
$header[] = '	td:nth-of-type(1):before { content: "Datum"; }';
$header[] = '	td:nth-of-type(2):before { content: "Start"; }';
$header[] = '	td:nth-of-type(3):before { content: "Voorganger"; }';
$header[] = '	td:nth-of-type(4):before { content: "Ruiling"; }';
$header[] = '	td:nth-of-type(5):before { content: "Bijzonderheid"; }';
$header[] = "}";
$header[] = "</style>";

echo showCSSHeader(array('default', 'table_rot'), $header);
echo '<div class="content_vert_kolom_full">'.NL;
echo '<h1>Preekrooster</h1>'.NL;
echo "<div class='content_block'>".NL. implode(NL, $text).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

?>
