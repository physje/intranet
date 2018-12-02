<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');

$db = connect_db();
$showLogin = true;

if(!isset($_REQUEST['rooster'])) {
	echo "geen rooster gedefinieerd";
	exit;
}

# Zoek op wie de beheerder is
$beheerder = getBeheerder4Rooster($_REQUEST['rooster']);

if(isset($_REQUEST['hash'])) {
	$id = isValidHash($_REQUEST['hash']);
	
	if(!is_numeric($id)) {
		toLog('error', '', '', 'ongeldige hash (rooster)');
		$showLogin = true;
	} else {
		$showLogin = false;
		$_SESSION['ID'] = $id;
		toLog('info', $id, '', 'rooster mbv hash');
	}
}

if($showLogin) {
	# Ken kijk-rechten
	$requiredUserGroups = array(1, $beheerder);
	$cfgProgDir = 'auth/';
	include($cfgProgDir. "secure.php");
}

# Eerste keer data ophalen voor in logfiles enzo
$RoosterData = getRoosterDetails($_REQUEST['rooster']);

# Als op de knop van de mail geklikt is die data wegschrijven
if(isset($_POST['save_mail'])) {
	$sql = "UPDATE $TableRoosters SET $RoostersMail = '". urlencode($_POST['text_mail']) ."', $RoostersSubject = '". urlencode($_POST['onderwerp_mail']) ."', $RoostersFrom = '". urlencode($_POST['naam_afzender']) ."',	$RoostersFromAddr = '". urlencode($_POST['mail_afzender']) ."' WHERE $RoostersID = ". $_POST['rooster'];
	mysqli_query($db, $sql);
	toLog('info', $_SESSION['ID'], '', 'Mail voor '. $RoosterData['naam'] .' aangepast');
}

# Als er op een knop gedrukt is, het rooster wegschrijven
if(isset($_POST['save']) OR isset($_POST['maanden'])) {
	if($RoosterData['text_only'] == 0) {	
		foreach($_POST['persoon'] as $dienst => $personen) {
			# Alle gegevens voor de dienst verwijderen
			removeFromRooster($_POST['rooster'], $dienst);
			
			# En de nieuwe wegschrijven
			foreach($personen as $pos => $persoon) {
				if($persoon != '' AND $persoon != 0) {
					add2Rooster($_POST['rooster'], $dienst, $persoon, $pos);
				}
			}		
		}
	} else {
		foreach($_POST['invulling'] as $dienst => $invulling) {
			# Alle gegevens voor de dienst verwijderen
			removeFromRooster($_POST['rooster'], $dienst);
			
			if($invulling != '') {
				updateRoosterText($_POST['rooster'], $dienst, $invulling);
			}			
		}
	}
	
	foreach($_POST['opmerking'] as $dienst => $opmerking) {
		if($opmerking != '') {
			updateRoosterOpmerking($_POST['rooster'], $dienst, $opmerking);
		}
	}
	
	toLog('info', $_SESSION['ID'], '', 'Rooster '. $RoosterData['naam'] .' aangepast');
	
	$sql = "UPDATE $TableRoosters SET $RoostersGelijk = '". $_POST['gelijkeDiensten'] ."', $RoostersOpmerking = '". $_POST['interneOpmerking'] ."', $RoostersLastChange = '". date("Y-m-d H:i:s") ."' WHERE $RoostersID like ". $_POST['rooster'];
	mysql_query($sql);
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

# Roosterdata voor de 2de keer opvragen (hierboven kan de data gewijzigd zijn)
# Nu gelijk ook maar groepsdata opvragen
$RoosterData = getRoosterDetails($_REQUEST['rooster']);

if($RoosterData['text_only'] == 0) {
	$nrFields = $RoosterData['aantal'];
	$IDs = getGroupMembers($RoosterData['groep']);
	
	# Als er geen groep is, gewoon de hele gemeente nemen
	if(count($IDs) == 0) {
		$IDs = getMembers('adressen');
		$type = 13;
	} else {
		$type = 5;
	}
	
	# Doorloop de hele groep en haal hun namen op
	foreach($IDs as $member) {
		$namen[$member] = makeName($member, $type);
	}
} else {
	$nrFields = 1;
}

# Haal alle kerkdiensten binnen een tijdsvak op
$diensten = getKerkdiensten(mktime(0,0,0), mktime(date("H"),date("i"),date("s"),(date("n")+(3*$blokken))));

$block_1[] = "<h2>Rooster</h2>";
$block_1[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
$block_1[] = "<input type='hidden' name='rooster' value='". $_REQUEST['rooster'] ."'>";
if(isset($_REQUEST['hash'])) {
	$block_1[] = "<input type='hidden' name='hash' value='". $_REQUEST['hash'] ."'>";
}
$block_1[] = "<input type='hidden' name='blokken' value='$blokken'>";
$block_1[] = "<table border=0>";
$block_1[] = "<tr>";
$block_1[] = "	<td align='right' valign='top'><input type='checkbox' name='gelijkeDiensten' value='1'". ($RoosterData['gelijk'] == 1 ? ' checked' : '') ."></td>";
$block_1[] = "	<td colspan='". ($nrFields+1+$RoosterData['opmerking']) ."' align='left'>Bij meer diensten per dag is het rooster voor alle diensten gelijk<br><small>(pas effect na opslaan)</small></td>";
$block_1[] = "</tr>";
$block_1[] = "<tr>";
$block_1[] = "	<td align='right' valign='top'><input type='checkbox' name='interneOpmerking' value='1'". ($RoosterData['opmerking'] == 1 ? ' checked' : '') ."></td>";
$block_1[] = "	<td colspan='". ($nrFields+1+$RoosterData['opmerking']) ."' align='left'>Mogelijkheid om interne opmerkingen bij het rooster te plaatsen<br><small>(huidige opmerkingen worden verwijderd bij uitvinken)</small></td>";
$block_1[] = "</tr>";
$block_1[] = "<tr>";
$block_1[] = "	<td><b>Dienst</b></td>";
if($RoosterData['text_only'] == 0) {
	$block_1[] = "	<td colspan='$nrFields' width='1'><b>Persoon</b></td>";
} else {	
	$block_1[] = "	<td colspan='$nrFields' width='1'><b>Roostertekst</b></td>";
}
if($RoosterData['opmerking'] == 1)	$block_1[] = "	<td align='left'><b>Interne opmerking</b></td>";
$block_1[] = "	<td align='left'><b>Bijzonderheid</b></td>";
$block_1[] = "</tr>";

foreach($diensten as $dienst) {
	if(!gelijkeDienst($dienst, $RoosterData['gelijk'])) {	
		$details = getKerkdienstDetails($dienst);
		$vulling = getRoosterVulling($_REQUEST['rooster'], $dienst);
		$opmerking = getRoosterOpmerking($_REQUEST['rooster'], $dienst);
		
		$block_1[] = "<tr>";
		$block_1[] = "	<td align='right'>". ($RoosterData['gelijk'] == 1 ? strftime("%A %d %b", $details['start']) : strftime("%A %d %b %H:%M", $details['start'])) ."</td>";
				
		if($RoosterData['text_only'] == 0) {
			$selected = current($vulling);
			
			for($n=0 ; $n < $nrFields ; $n++) {
				if($selected != 0) $statistiek[$selected]++;
				$block_1[] = "	<td><select name='persoon[$dienst][]'>";
				$block_1[] = "	<option value=''>&nbsp;</option>";
								
				foreach($namen as $key => $naam) {
					$block_1[] = "	<option value='$key'". ($selected == $key ? " selected" : '') .">$naam</option>";
				}		
				
				$block_1[] = "</select></td>";
				$selected = next($vulling);
			}
		} else {
			$block_1[] = "	<td><input type='text' name='invulling[$dienst]' value='$vulling' size='50'></td>";
		}
		
		if($RoosterData['opmerking'] == 1) {
			$block_1[] = "	<td><input type='text' name='opmerking[$dienst]' value='$opmerking' size='50'></td>";
		}
		$block_1[] = "	<td>". $details['bijzonderheden']."</td>";
		$block_1[] = "</tr>";
	}
}

$block_1[] = "<tr>";
$block_1[] = "<td colspan='". ($nrFields+2+$RoosterData['opmerking']) ."' align='middle'><input type='submit' name='save' value='Rooster opslaan'>&nbsp;<input type='submit' name='maanden' value='Volgende 3 maanden'></td>";
$block_1[] = "</tr>";
$block_1[] = "</table>";
$block_1[] = "</form>";

if($RoosterData['text_only'] == 0) {
	$block_3[] = "<h2>Statistiek</h2>";
	$block_3[] = "Op basis van de roosterdata zoals die hierboven is opgeslagen, wordt statistiek berekend.<br>";
	$block_3[] = "Mogelijk moet het rooster dus nog worden opgeslagen om de meest recente statistiek te krijgen.<br>";
	$block_3[] = "<br>";
	$block_3[] = "<table>";
	
	asort($statistiek, SORT_STRING);
	foreach($statistiek as $lid => $aantal) {
		$block_3[] = '<tr>';
		$block_3[] = '	<td>'. makeName($lid, 5) .'</td>';
		$block_3[] = '	<td>&nbsp;</td>';
		$block_3[] = '	<td>'. $aantal .'</td>';
		$block_3[] = '<tr>';
	}
	$block_3[] = '</table>';
	
	$block_2[] = "<h2>Remindermail</h2>";
	$block_2[] = "3 dagen voordat iemand op het rooster staat krijgt hij/zij een mail als reminder.<br>";
	$block_2[] = "Hieronder kan die mail worden vormgegeven.<br>";
	$block_2[] = "<br>";
	$block_2[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
	$block_2[] = "<input type='hidden' name='rooster' value='". $_REQUEST['rooster'] ."'>";
	$block_2[] = "<table border=0>";
	$block_2[] = "<tr>";
	$block_2[] = "	<td valign='top'>Afzendernaam</td>";
	$block_2[] = "	<td valign='top' colspan='2'><input type='text' name='naam_afzender' size=80 value='".$RoosterData['naam_afzender'] ."'></td>";
	$block_2[] = "</tr>";
	$block_2[] = "<tr>";
	$block_2[] = "	<td valign='top'>Mailadres</td>";
	$block_2[] = "	<td valign='top' colspan='2'><input type='text' name='mail_afzender' size=80 value='".$RoosterData['mail_afzender'] ."'></td>";
	$block_2[] = "</tr>";
	$block_2[] = "<tr>";
	$block_2[] = "	<td valign='top'>Onderwerp</td>";
	$block_2[] = "	<td valign='top' colspan='2'><input type='text' name='onderwerp_mail' size=80 value='".$RoosterData['onderwerp_mail'] ."'></td>";
	$block_2[] = "</tr>";
	$block_2[] = "<tr>";
	$block_2[] = "	<td valign='top'>Mailtekst</td>";
	$block_2[] = "	<td valign='top'><textarea name='text_mail' rows=20 cols=60>". $RoosterData['text_mail'] ."</textarea></td>";
	$block_2[] = "	<td valign='top'>";
	$block_2[] = "		<table border=0>";
	$block_2[] = "		<tr><td valign='top'>[[voornaam]]</td><td valign='top'>voornaam van de ontvanger.</td></tr>";
	$block_2[] = "		<tr><td valign='top'>[[achternaam]]</td><td valign='top'>achternaam van de ontvanger.</td></tr>";
	$block_2[] = "		<tr><td valign='top'>[[team]]</td><td valign='top'>namen van iedereen die voor die dag op het rooster staat (uitgezonderd de ontvanger).</td></tr>";
	$block_2[] = "		<tr><td valign='top'>[[voorganger]]</td><td valign='top'>naam van de voorganger die dienst.</td></tr>";
	$block_2[] = "		<tr><td valign='top'>[[dag]]</td><td valign='top'>naam van de dag. Meestal zondag, bij feestdagen meestal andere dag.</td></tr>";
	$block_2[] = "		<tr><td valign='top'>[[dagdeel]]</td><td valign='top'>naam van het dagdeel (ochtend, middag, avond).</td></tr>";
	$block_2[] = "		</table>";
	$block_2[] = "	</td>";
	$block_2[] = "</tr>";
	$block_2[] = "<tr>";
	$block_2[] = "	<td valign='top'>&nbsp;</td><td valign='top' colspan='2'><input type='submit' name='save_mail' value='Mail-gegevens opslaan'></td>";
	$block_2[] = "</tr>";
	$block_2[] = "</table>";
	$block_2[] = "</form>";
}

echo $HTMLHeader;
echo "<h1>". $RoosterData['naam'] ."</h1>".NL;
echo showBlock(implode(NL, $block_1), 100);
if($RoosterData['text_only'] == 0) {
	echo "<p>";
	echo showBlock(implode(NL, $block_3), 100);
	echo "<p>";
	echo showBlock(implode(NL, $block_2), 100);
}
echo $HTMLFooter;
?>