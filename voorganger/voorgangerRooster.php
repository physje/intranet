<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');

$db = connect_db();
$requiredUserGroups = array(1, 20);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

# Als er op een knop gedrukt is, het rooster wegschrijven
if(isset($_POST['save']) OR isset($_POST['maanden'])) {	
	foreach($_POST['voorganger'] as $dienst => $voorgangerID) {
		$oldData = getKerkdienstDetails($dienst);
		
		if(isset($_POST['ruiling'][$dienst])) {
			$ruiling = 1;
		} else {
			$ruiling = 0;
		}
		$sql = "UPDATE $TableDiensten SET $DienstVoorganger = $voorgangerID, $DienstRuiling = '$ruiling' WHERE $DienstID = ". $dienst;		
			
		if(!mysqli_query($db, $sql)) {
			$text[] = "Ging iets niet goed met geegevens opslaan";
			toLog('error', '', 'Gegevens voorganger ('. $_REQUEST['voorgangerID'] .") konden niet worden gekoppeld aan dienst $dienst");
		}
		
		# Mocht een voorganger wijzigen, zet dat dan ook even in de logfiles
		if($oldData['voorganger_id'] != '' AND $oldData['voorganger_id'] != $voorgangerID) {
			$dienstData = getKerkdienstDetails($dienst);
			toLog('info', '', "Voorganger van ". date("d-m-y", $dienstData['start']) ." gewijzigd van ". makeVoorgangerName($oldData['voorganger_id'], 1) ." naar ". makeVoorgangerName($voorgangerID, 1));
		}
	}
	toLog('info', '', 'Diensten bijgewerkt');
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
$diensten = getKerkdiensten(mktime(0,0,0,(date("n")-1)), mktime(date("H"),date("i"),date("s"),(date("n")+(3*$blokken))));

# Haal alle voorgangers op en maak een namen-array
$voorgangers = getVoorgangers();

foreach($voorgangers as $voorgangerID) {
	/*
	$voorgangerData = getVoorgangerData($voorgangerID);
	$voor = ($voorgangerData['voor'] == '' ? $voorgangerData['init'] : $voorgangerData['voor']);
	//$voorgangersNamen[$voorgangerID] = $voor.' '.($voorgangerData['tussen'] == '' ? '' : $voorgangerData['tussen']. ' ').$voorgangerData['achter'];
	$voorgangersNamen[$voorgangerID] = $voorgangerData['achter'].', '.$voor.($voorgangerData['tussen'] == '' ? '' : '; '.$voorgangerData['tussen']);
	*/
	$voorgangersNamen[$voorgangerID] = makeVoorgangerName($voorgangerID, 8);
}

# Zoek voorgangers die vaker dan 3 keer in de database staan
$frequent = array();
$sql = "SELECT $DienstVoorganger, count(*) as aantal FROM $TableDiensten GROUP BY $DienstVoorganger HAVING aantal > 2 AND $DienstVoorganger != 0 ORDER BY aantal DESC";
$result = mysqli_query($db, $sql);
if($row = mysqli_fetch_array($result)) {
	do {
		$voorgangerID = $row[$DienstVoorganger];
		$frequent[] = $voorgangerID;
	} while($row = mysqli_fetch_array($result));
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

foreach($diensten as $dienst) {
	$data = getKerkdienstDetails($dienst);
	
	$text[] = "<tr>";
	$text[] = "	<td align='right'>". time2str("%a %#d %b", $data['start']) ."</td>";
	//$text[] = "	<td align='right'>". date("d-m-Y", $data['start']) ."</td>";
	$text[] = "	<td>". date('H:i', $data['start']) ."</td>";
	$text[] = "	<td>";
	$text[] = "<select name='voorganger[$dienst]'>";
	$text[] = "	<option value='0'></option>";
	
	$text[] = "	<optgroup label=\"Frequent\">";
	foreach($frequent as $voorgangerID) {
		$naam = $voorgangersNamen[$voorgangerID];
		$text[] = "	<option value='$voorgangerID'". ($data['voorganger_id'] == $voorgangerID ? ' selected' : '') .">$naam</option>";
	}
	$text[] = "</optgroup>";
	
	# En de overige voorgangers
	$text[] = "	<optgroup label=\"Minder frequent\">";	
	foreach($voorgangersNamen as $voorgangerID => $naam) {
		if(!in_array($voorgangerID, $frequent))	$text[] = "	<option value='$voorgangerID'". ($data['voorganger_id'] == $voorgangerID ? ' selected' : '') .">$naam</option>";
	}
	$text[] = "</optgroup>";	
	$text[] = "</select>";	
	if($data['voorganger_id'] > 0) {
		#$text[] = "	<td>&nbsp;</td>";
		$voorgangersData = getVoorgangerData($data['voorganger_id']);
	} else {
		#$text[] = "	<td align='right'><a href='editVoorganger.php?new=ja' target='_blank'><img src='../images/invite.gif' title='Open een nieuw scherm om missende voorganger toe te voegen'></a></td>";
		$text[] = "&nbsp;<a href='editVoorganger.php?new=ja' target='_blank'><img src='../images/invite.gif' title='Open een nieuw scherm om missende voorganger toe te voegen'></a>";
	}
	$text[] = "	</td>";
	$text[] = "	<td><input type='checkbox' name='ruiling[$dienst]' value='1'". ($data['ruiling'] == 1 ? ' checked': '').($voorgangersData['plaats'] == 'Deventer' ? ' disabled' : '') ."></td>";
	$text[] = "	<td>". ($data['bijzonderheden'] != '' ? $data['bijzonderheden'] : '&nbsp;') ."</td>";
	$text[] = "</tr>";
}
$text[] = "</table>";
#$text[] = "<tr>";
#$text[] = "	<td colspan='6' align='middle'><input type='submit' name='save' value='Diensten opslaan'>&nbsp;<input type='submit' name='maanden' value='Volgende 3 maanden'></td>";
#$text[] = "</tr>";
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
