<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');
$showLogin = true;

if(isset($_REQUEST['hash'])) {
	$id = isValidHash($_REQUEST['hash']);
	
	if(!is_numeric($id)) {
		toLog('error', '', 'ongeldige hash (account)');
		$showLogin = true;
	} else {
		$showLogin = false;
		$_SESSION['useID'] = $id;
		toLog('info', $id, '', 'account mbv hash');
	}
}

if($showLogin) {
	$cfgProgDir = 'auth/';
	include($cfgProgDir. "secure.php");
	$db = connect_db();
}

$RoosterData	= getRoosterDetails($_REQUEST['rooster']);
$diensten			= getAllKerkdiensten(true);
$IDs					= getGroupMembers($RoosterData['groep']);
$familie			= getFamilieleden($_SESSION['useID']);
$leeg					= true;
$showRuilen		= false;

# Moet het symbool voor ruilen getoond worden
#		1a) Je zit in de groep waar dit rooster voor geldt
#		1b)	Een van je familie-leden zit in de groep die op het rooster staat
#		2)	Het is een rooster wat niet geimporteerd wordt 
if((in_array($_SESSION['useID'], $IDs) OR count(array_intersect($familie, $IDs)) > 0) AND !in_array($_REQUEST['rooster'], $importRoosters)) {
	$showRuilen = true;
}

toLog('debug', '', 'Rooster '. $RoosterData['naam'] .' bekeken');

$block_1[] = '<table>';
$block_1[] = '<thead>';
$block_1[] = '<tr>';
$block_1[] = '	<th>Datum</th>';
$block_1[] = '	<th>Persoon</th>';
$block_1[] = '</tr>';
$block_1[] = '</thead>';
$block_1[] = '<tbody>';

foreach($diensten as $dienst) {
	$details = getKerkdienstDetails($dienst);
	$vulling = getRoosterVulling($_REQUEST['rooster'], $dienst);
	
	# Zijn er namen of is er een tekststring
	if(isset($vulling) AND (is_array($vulling) AND (count($vulling) > 0) OR $vulling != '')) {
		if($RoosterData['text_only'] == 0) {		
			$namen = array();
							
			foreach($vulling as $lid) {
				//$data = getMemberDetails($lid);
				$string = "<a href='profiel.php?id=$lid'>". makeName($lid, 5) ."</a>";
				
				#if((in_array($_SESSION['ID'], $IDs) OR in_array($_SESSION['ID'], $vulling)) AND !in_array($_REQUEST['rooster'], $importRoosters)) {
				if($showRuilen AND in_array($lid, $familie)) {
					$string .= " <a href='ruilen.php?rooster=". $_REQUEST['rooster'] ."&dienst_d=$dienst&dader=$lid' title='klik om ruiling door te geven'><img src='images/wisselen.png'></a>";
				} elseif($showRuilen) {
					$string .= " <a href='ruilen.php?rooster=". $_REQUEST['rooster'] ."&dienst_s=$dienst&slachtoffer=$lid' title='klik ruiling door te geven'><img src='images/wisselen.png'></a>";
				}
								
				$namen[] = $string;
			}			
			$RoosterString = implode('<br>', $namen);
		} else {
			$RoosterString = $vulling;
		}
		
		if(trim($RoosterString) != '') {
			$block_1[] = "<tr>";
			$block_1[] = "	<td><a href='roosterKomendeWeek.php?id=$dienst'>".time2str("%a %d %b %H:%M", $details['start'])."</a></td>";
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

$block_2[] = "<a href='showCombineRooster.php?rs=". $_REQUEST['rooster'] ."&pdf'>PDF-versie</a>";

echo showCSSHeader(array('default', 'table_default'));
echo '<div class="content_vert_kolom">'.NL;
echo "<h1>". $RoosterData['naam'] ."</h1>".NL;
echo "<div class='content_block'>".NL. implode(NL, $block_1).NL."</div>".NL;
echo "<div class='content_block'>".NL. implode(NL, $block_2).NL."</div>".NL;
echo '</div>'.NL;
echo showCSSFooter();

?>
