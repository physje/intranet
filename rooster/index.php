<?php
include_once('../Classes/Member.php');
include_once('../Classes/Team.php');
include_once('../Classes/Rooster.php');
include_once('../Classes/Kerkdienst.php');
include_once('../Classes/Mysql.php');
include_once('../Classes/Logging.php');
include_once('../Classes/Vulling.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$showLogin = true;

if(isset($_REQUEST['hash'])) {
	$userID = isValidHash($_REQUEST['hash']);
	
	if(!is_numeric($userID)) {
		toLog('ongeldige hash (rooster '. $_REQUEST['id'] .')', 'error');
		$showLogin = true;
	} else {
		$showLogin = false;
		session_start(['cookie_lifetime' => $cookie_lifetime]);
		$_SESSION['useID'] = $userID;
		$_SESSION['realID'] = $userIDid;
		toLog('rooster '. $_REQUEST['id'] .' mbv hash');
	}
}

if($showLogin) {
	$cfgProgDir = '../auth/';
	include($cfgProgDir. "secure.php");
}

$gebruiker		= new Member($_SESSION['useID']);
$rooster		= new Rooster($_REQUEST['id']);
$groep			= new Team($rooster->groep);
$IDs			= $groep->leden;
$familie		= $gebruiker->getFamilieLeden();
$diensten		= Kerkdienst::getDiensten(time(), time()+90*24*60*60);
$leeg			= true;
$showRuilen		= false;

# Moet het symbool voor ruilen getoond worden
#		1a) Je zit in de groep waar dit rooster voor geldt
#		1b)	Een van je familie-leden zit in de groep die op het rooster staat
#		2)	Het is een rooster wat niet geimporteerd wordt
if((in_array($_SESSION['useID'], $IDs) OR count(array_intersect($familie, $IDs)) > 0) AND !in_array($_REQUEST['id'], $importRoosters)) {
	$showRuilen = true;
}

toLog('Rooster '. $rooster->naam .' bekeken', 'debug');

$block_1[] = '<table>';
$block_1[] = '<thead>';
$block_1[] = '<tr>';
$block_1[] = '	<th>Datum</th>';
$block_1[] = '	<th>Persoon</th>';
$block_1[] = '</tr>';
$block_1[] = '</thead>';
$block_1[] = '<tbody>';

foreach($diensten as $dienst) {
	$kerkdienst = new Kerkdienst($dienst);
	$vulling = new Vulling($dienst, $_REQUEST['id']);
	
	# Zijn er namen of is er een tekststring
	if(isset($vulling) AND (is_array($vulling) AND (count($vulling) > 0) OR $vulling != '')) {
		if(!$rooster->tekst) {		
			$namen = array();
							
			foreach($vulling->leden as $lid) {
				$person = new Member($lid);
				$string = "<a href='../profiel.php?id=$lid'>". $person->getName() ."</a>";
				
				#if((in_array($_SESSION['ID'], $IDs) OR in_array($_SESSION['ID'], $vulling)) AND !in_array($_REQUEST['rooster'], $importRoosters)) {
				if($showRuilen AND in_array($lid, $familie)) {
					$string .= " <a href='ruilen.php?id=". $_REQUEST['id'] ."&d_d=$dienst&d=$lid' title='klik om ruiling door te geven'><img src='../images/wisselen.png'></a>";
				} elseif($showRuilen) {
					$string .= " <a href='ruilen.php?id=". $_REQUEST['id'] ."&d_s=$dienst&s=$lid' title='klik ruiling door te geven'><img src='../images/wisselen.png'></a>";
				}
								
				$namen[] = $string;
			}			
			$RoosterString = implode('<br>', $namen);
		} else {
			$RoosterString = $vulling->tekst;
		}
		
		if(trim($RoosterString) != '') {
			$block_1[] = "<tr>";
			$block_1[] = "	<td><a href='komendeWeek.php?id=$dienst'>".time2str("d LLL HH:mm", $kerkdienst->start)."</a></td>";
			$block_1[] = "	<td>". $RoosterString ."</td>";
			$block_1[] = "</tr>";
			$leeg = false;
		}
	}
}

if($leeg) {
	unset($block_1);	
	$block_1[] = "Dit rooster is leeg";
} else {
	$block_1[] = '</table>';
}

$block_2[] = "<a href='combinatieRooster.php?rs=". $_REQUEST['id'] ."&pdf'>PDF-versie</a>";

echo showCSSHeader(array('default', 'table_default'), '', $rooster->naam);
echo '<div class="content_vert_kolom">'.NL;
echo "<h1>". $rooster->naam ."</h1>".NL;
echo "<div class='content_block'>".NL. implode(NL, $block_1).NL."</div>".NL;
echo "<div class='content_block'>".NL. implode(NL, $block_2).NL."</div>".NL;
echo '</div>'.NL;
echo showCSSFooter();
?>