<?php
include_once('../Classes/Rooster.php');
include_once('../Classes/Member.php');
include_once('../Classes/Team.php');
include_once('../Classes/Kerkdienst.php');
include_once('../Classes/Vulling.php');
include_once('../Classes/Logging.php');
include_once('../Classes/Voorganger.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');

$showLogin = true;

if(!isset($_REQUEST['id'])) {
	echo "geen rooster gedefinieerd";
	exit;
}

if(isset($_REQUEST['hash'])) {
	$id = isValidHash($_REQUEST['hash']);
	
	if(!is_numeric($id)) {
		toLog('ongeldige hash (rooster wijzigen)', 'error');
		$showLogin = true;
	} else {
		$showLogin = false;
		session_start(['cookie_lifetime' => $cookie_lifetime]);
		$_SESSION['useID'] = $id;
		$_SESSION['realID'] = $id;
		toLog('rooster wijzigen mbv hash');
	}
}

$block_rooster = $block_stat = array();

# Eerste keer data ophalen voor in logfiles enzo
$rooster = new Rooster($_REQUEST['id']);
$beheerder = $rooster->beheerder;
$planner = $rooster->planner;

if($showLogin) {
	# Ken kijk- en schrijf-rechten toe aan admin, beheerder en planner
	$requiredUserGroups = array(1, $beheerder, $planner);
	$cfgProgDir = '../auth/';
	include($cfgProgDir. "secure.php");
}

$ik = new Member($_SESSION['useID']);
$myGroups = $ik->getTeams();

# Als er op een knop gedrukt is, het rooster wegschrijven
if(isset($_POST['save']) OR isset($_POST['maanden'])) {
	if(!$rooster->tekst) {		
		foreach($_POST['persoon'] as $dienst => $personen) {
			$vulling = new Vulling();
			$vulling->rooster = $_POST['id'];
			$vulling->dienst = $dienst;
			$vulling->leden = $personen;
			$vulling->tekst_only = false;

			if(isset($_POST['opmerking'][$dienst]) AND $_POST['opmerking'][$dienst] != '') {
				$vulling->opmerking = $_POST['opmerking'][$dienst];				
			}

			$vulling->save();
		}
	} else {
		foreach($_POST['invulling'] as $dienst => $invulling) {
			$vulling = new Vulling();
			$vulling->rooster = $_POST['id'];
			$vulling->dienst = $dienst;			
			$vulling->tekst_only = true;	
						
			if($invulling != '') {				
				$vulling->tekst = $invulling;
			}

			$vulling->save();
		}
	}	
	
	toLog('Rooster '. $rooster->naam .' aangepast');
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
$rooster = new Rooster($_REQUEST['id']);

if($rooster->tekst) {
	$nrFields = 1;
} else {
	$nrFields 	= $rooster->velden;
	$groep		= new Team($rooster->groep);
	$IDs		= $groep->leden;
	
	# Als er geen groep is, gewoon de hele gemeente nemen
	if(count($IDs) == 0) {
		$IDs = Member::getMembers('adressen');
		$type = 3;
	} else {
		$type = 2;
	}
	
	# Doorloop de hele groep en haal hun namen op
	foreach($IDs as $member) {
		$person = new Member($member);
		$person->nameType = $type;
		$namen[$member] = $person->getName();
	}
}

# Haal alle kerkdiensten binnen een tijdsvak op
$diensten	= Kerkdienst::getDiensten(mktime(0,0,0), mktime(date("H"),date("i"),date("s"),(date("n")+(3*$blokken))));

$header[] = '<style>';
$header[] = '@media only screen and (max-width: 760px), (min-device-width: 768px) and (max-device-width: 1024px)  {';
$header[] = '	td:nth-of-type(1):before { content: "Dienst"; }';
$header[] = '	td:nth-of-type(2):before { content: "'.($rooster->tekst ? 'Roostertekst' : 'Persoon') .'"; }';
$i = 2;

if($rooster->opmerking) { $i++;		$header[] = '	td:nth-of-type('. $i . '):before { content: "Interne opmerking"; }'; }
if($rooster->voorganger) { $i++;	$header[] = '	td:nth-of-type('. $i . '):before { content: "Voorganger"; }'; }

$i++;
$header[] = '	td:nth-of-type('. $i . '):before { content: "Bijzonderheid"; }';
$header[] = "}";
$header[] = "</style>";

$block_rooster[] = "<h2>Rooster</h2>";
$block_rooster[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
$block_rooster[] = "<input type='hidden' name='id' value='". $rooster->id ."'>";
if(isset($_REQUEST['hash'])) { $block_rooster[] = "<input type='hidden' name='hash' value='". $_REQUEST['hash'] ."'>";}
$block_rooster[] = "<input type='hidden' name='blokken' value='$blokken'>";
$block_rooster[] = "<table>";
if(in_array($beheerder, $myGroups) OR in_array(1, $myGroups)) {
	$block_rooster[] = "<caption><a href='rooster_details.php?id=". $rooster->id ."'>Mis je diensten? Wil je de tekst van de mail aanpassen? Klik hier om instellingen van het rooster aan te passen.</a></caption>";
}
$block_rooster[] = "<thead>";
$block_rooster[] = "<tr>";
$block_rooster[] = "	<th>Dienst</th>";
if($rooster->tekst) {
	$block_rooster[] = "	<th>Roostertekst</th>";
} else {	
	$block_rooster[] = "	<th>Persoon</th>";
}
if($rooster->opmerking)		$block_rooster[] = "	<th class='th_rot'>Interne opmerking</th>";
if($rooster->voorganger)	$block_rooster[] = "	<th class='th_rot'>Voorganger</th>";
$block_rooster[] = "	<th>Bijzonderheid</th>";
$block_rooster[] = "</tr>";
$block_rooster[] = "</thead>";
$block_rooster[] = "<tbody>";

$statistiek = array();

foreach($diensten as $dienst) {
	if(toonDienst($dienst, $rooster->gelijk)) {	
		$kerkdienst = new Kerkdienst($dienst);		
		$vulling	= new Vulling($dienst, $rooster->id);
		   		
		if(in_array($rooster->gelijk, array(1, 3, 5, 6))) {
			$korteDatum = true;
		} else {
			$korteDatum = false;
		}
				
		$block_rooster[] = "<tr>";
		$block_rooster[] = "	<td>". ($korteDatum ? time2str("EEEE dd LLL", $kerkdienst->start) : time2str("EEEE dd LLL HH:mm", $kerkdienst->start)) ."</td>";
				
		if($rooster->tekst) {
			$block_rooster[] = "	<td><input type='text' name='invulling[$dienst]' value='". $vulling->tekst ."'></td>";			
		} else {
			$block_rooster[] = "	<td>";
			
			for($n=0 ; $n < $nrFields ; $n++) {
				if(isset($vulling->leden[$n])) {
					$selected = $vulling->leden[$n];
				} else {
					$selected = 0;
				}

				if($selected != 0) {
					if(!isset($statistiek[$selected]))	$statistiek[$selected] = 0;
					$statistiek[$selected]++;
				}
				$block_rooster[] = "	<select name='persoon[$dienst][]'>";
				$block_rooster[] = "		<option value=''>&nbsp;</option>";
								
				foreach($namen as $key => $naam) {
					$block_rooster[] = "		<option value='$key'". ($selected == $key ? " selected" : '') .">$naam</option>";
				}		
				
				$block_rooster[] = "	</select>";
				$selected = next($vulling);
			}
			
			$block_rooster[] = "	</td>";
		}
		
		if($rooster->opmerking) {
			$block_rooster[] = "	<td><input type='text' name='opmerking[$dienst]' value='". $vulling->opmerking ."'></td>";
		}
		
		if($rooster->voorganger) {
			$predikant	= new Voorganger($kerkdienst->voorganger);
			$predikant->nameType = 4;
			$block_rooster[] = "	<td>". $predikant->getName() ."</td>";			
		}
		
		
		$block_rooster[] = "	<td>". ($kerkdienst->opmerking != '' ? $kerkdienst->opmerking : '&nbsp;' )."</td>";
		$block_rooster[] = "</tr>";
	}
}

$block_rooster[] = "</table>";
$block_rooster[] = "<p class='after_table'><input type='submit' name='save' value='Rooster opslaan'>&nbsp;<input type='submit' name='maanden' value='Volgende 3 maanden'></p>";
$block_rooster[] = "</form>";

if(!$rooster->tekst) {
	$block_stat[] = "<h2>Statistiek</h2>";
	$block_stat[] = "Op basis van de roosterdata zoals die hierboven is opgeslagen, wordt statistiek berekend.<br>";
	$block_stat[] = "Mogelijk moet het rooster dus nog worden opgeslagen om de meest recente statistiek te krijgen.<br>";
	$block_stat[] = "<br>";
		
	asort($statistiek, SORT_STRING);
	foreach($statistiek as $lid => $aantal) {		
		$person = new Member($lid);
		$block_stat[] = $person->getName() .' : '. $aantal .'<br>';		
	}
}

echo showCSSHeader(array('default', 'table_rot'), $header);
echo '<div class="content_vert_kolom_full">'.NL;
echo '<h1>'. $rooster->naam .'</h1>'.NL;
echo "<div class='content_block'>".NL. implode(NL, $block_rooster).NL."</div>".NL;
if(!$rooster->tekst) {
	echo "<div class='content_block'>".NL. implode(NL, $block_stat).NL."</div>".NL;
}
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();
?>