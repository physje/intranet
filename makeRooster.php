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

if(isset($_REQUEST['hash'])) {
	$id = isValidHash($_REQUEST['hash']);
	
	if(!is_numeric($id)) {
		toLog('error', '', 'ongeldige hash (rooster)');
		$showLogin = true;
	} else {
		$showLogin = false;
		session_start(['cookie_lifetime' => $cookie_lifetime]);
		$_SESSION['useID'] = $id;
		toLog('info', '', 'rooster mbv hash');
	}
}

$block_rooster = $block_stat = array();

# Eerste keer data ophalen voor in logfiles enzo
$RoosterData = getRoosterDetails($_REQUEST['rooster']);
$beheerder = $RoosterData['beheerder'];
$planner = $RoosterData['planner'];

if($showLogin) {
	# Ken kijk- en schrijf-rechten toe aan admin, beheerder en planner
	$requiredUserGroups = array(1, $beheerder, $planner);
	$cfgProgDir = 'auth/';
	include($cfgProgDir. "secure.php");
}

$myGroups = getMyGroups($_SESSION['useID']);

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
	
	if(is_array($_POST['opmerking'])) {
		foreach($_POST['opmerking'] as $dienst => $opmerking) {
			if($opmerking != '') {
				updateRoosterOpmerking($_POST['rooster'], $dienst, $opmerking);
			}
		}
	}
	
	toLog('info', '', 'Rooster '. $RoosterData['naam'] .' aangepast');
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

$header[] = '<style>';
$header[] = '@media only screen and (max-width: 760px), (min-device-width: 768px) and (max-device-width: 1024px)  {';
$header[] = '	td:nth-of-type(1):before { content: "Dienst"; }';
$header[] = '	td:nth-of-type(2):before { content: "'.($RoosterData['text_only'] == 0 ? 'Persoon' : 'Roostertekst') .'"; }';

if($RoosterData['opmerking'] == 1) {
	$header[] = '	td:nth-of-type(3):before { content: "Interne opmerking"; }';
	$header[] = '	td:nth-of-type(4):before { content: "Bijzonderheid"; }';
} else {
	$header[] = '	td:nth-of-type(3):before { content: "Bijzonderheid"; }';
}
$header[] = "}";
$header[] = "</style>";

$block_rooster[] = "<h2>Rooster</h2>";
$block_rooster[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
$block_rooster[] = "<input type='hidden' name='rooster' value='". $_REQUEST['rooster'] ."'>";
if(isset($_REQUEST['hash'])) {
	$block_rooster[] = "<input type='hidden' name='hash' value='". $_REQUEST['hash'] ."'>";
}
$block_rooster[] = "<input type='hidden' name='blokken' value='$blokken'>";
$block_rooster[] = "<table>";
if(in_array($beheerder, $myGroups) OR in_array(1, $myGroups)) {
	$block_rooster[] = "<caption><a href='rooster_details.php?rooster=". $_REQUEST['rooster'] ."'>Mis je diensten? Wil je de tekst van de mail aanpassen? Klik hier om instellingen van het rooster aan te passen.</a></caption>";
}
$block_rooster[] = "<thead>";
$block_rooster[] = "<tr>";
$block_rooster[] = "	<th>Dienst</th>";
if($RoosterData['text_only'] == 0) {
	$block_rooster[] = "	<th>Persoon</th>";
} else {	
	$block_rooster[] = "	<th>Roostertekst</th>";
}
if($RoosterData['opmerking'] == 1)	$block_rooster[] = "	<th class='th_rot'>Interne opmerking</th>";
$block_rooster[] = "	<th>Bijzonderheid</th>";
$block_rooster[] = "</tr>";
$block_rooster[] = "</thead>";
$block_rooster[] = "<tbody>";

$statistiek = array();

foreach($diensten as $dienst) {
	if(toonDienst($dienst, $RoosterData['gelijk'])) {	
		$details = getKerkdienstDetails($dienst);
		$vulling = getRoosterVulling($_REQUEST['rooster'], $dienst);
		$opmerking = getRoosterOpmerking($_REQUEST['rooster'], $dienst);
		
		if(in_array($RoosterData['gelijk'], array(1, 3, 5, 6))) {
			$korteDatum = true;
		} else {
			$korteDatum = false;
		}
				
		$block_rooster[] = "<tr>";
		$block_rooster[] = "	<td>". ($korteDatum ? time2str("%A %d %b", $details['start']) : time2str("%A %d %b %H:%M", $details['start'])) ."</td>";
				
		if($RoosterData['text_only'] == 0) {
			$selected = current($vulling);
			
			$block_rooster[] = "	<td>";
			
			for($n=0 ; $n < $nrFields ; $n++) {
				if($selected != 0) {
					if(!isset($statistiek[$selected]))	$statistiek[$selected] = 0;
					$statistiek[$selected]++;
				}
				$block_rooster[] = "	<select name='persoon[$dienst][]'>";
				$block_rooster[] = "	<option value=''>&nbsp;</option>";
								
				foreach($namen as $key => $naam) {
					$block_rooster[] = "	<option value='$key'". ($selected == $key ? " selected" : '') .">$naam</option>";
				}		
				
				$block_rooster[] = "</select>";
				$selected = next($vulling);
			}
			
			$block_rooster[] = "	</td>";
		} else {
			$block_rooster[] = "	<td><input type='text' name='invulling[$dienst]' value='$vulling'></td>";
		}
		
		if($RoosterData['opmerking'] == 1) {
			$block_rooster[] = "	<td><input type='text' name='opmerking[$dienst]' value='$opmerking'></td>";			
		}
		$block_rooster[] = "	<td>". ($details['bijzonderheden'] != '' ? $details['bijzonderheden'] : '&nbsp;' )."</td>";
		$block_rooster[] = "</tr>";
	}
}

$block_rooster[] = "</table>";
$block_rooster[] = "<p class='after_table'><input type='submit' name='save' value='Rooster opslaan'>&nbsp;<input type='submit' name='maanden' value='Volgende 3 maanden'></p>";
$block_rooster[] = "</form>";



if($RoosterData['text_only'] == 0) {
	$block_stat[] = "<h2>Statistiek</h2>";
	$block_stat[] = "Op basis van de roosterdata zoals die hierboven is opgeslagen, wordt statistiek berekend.<br>";
	$block_stat[] = "Mogelijk moet het rooster dus nog worden opgeslagen om de meest recente statistiek te krijgen.<br>";
	$block_stat[] = "<br>";
		
	asort($statistiek, SORT_STRING);
	foreach($statistiek as $lid => $aantal) {
		$block_stat[] = makeName($lid, 5) .' : '. $aantal .'<br>';		
	}
}


if(false) {
	$block_link[] = "";
}


echo showCSSHeader(array('default', 'table_rot'), $header);
echo '<div class="content_vert_kolom_full">'.NL;
echo '<h1>'. $RoosterData['naam'] .'</h1>'.NL;
echo "<div class='content_block'>".NL. implode(NL, $block_rooster).NL."</div>".NL;
if($RoosterData['text_only'] == 0) {
	echo "<div class='content_block'>".NL. implode(NL, $block_stat).NL."</div>".NL;
}
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();
?>