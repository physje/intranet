<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');
$showLogin = true;

if(isset($_REQUEST['hash'])) {
	$id = isValidHash($_REQUEST['hash']);
	
	if(!is_numeric($id)) {
		toLog('error', '', '', 'ongeldige hash (account)');
		$showLogin = true;
	} else {
		$showLogin = false;
		$_SESSION['ID'] = $id;
		toLog('info', $id, '', 'account mbv hash');
	}
}

if($showLogin) {
	$cfgProgDir = 'auth/';
	include_once($cfgProgDir. "secure.php");
	$db = connect_db();
}

$RoosterData = getRoosterDetails($_REQUEST['rooster']);
$diensten = getAllKerkdiensten(true);
$IDs = getGroupMembers($RoosterData['groep']);

toLog('debug', $_SESSION['ID'], '', 'Rooster '. $RoosterData['naam'] .' bekeken');


$text[] = "<h1>". $RoosterData['naam'] ."</h1>".NL;
$block_1[] = '<table>';

foreach($diensten as $dienst) {
	$details = getKerkdienstDetails($dienst);
	$vulling = getRoosterVulling($_REQUEST['rooster'], $dienst);
	
	# Zijn er namen of is er een tekststring
	if(count($vulling) > 0 OR $vulling != '') {
		if($RoosterData['text_only'] == 0) {		
			$namen = array();
				
			foreach($vulling as $lid) {
				//$data = getMemberDetails($lid);
				$string = "<a href='profiel.php?id=$lid'>". makeName($lid, 5) ."</a>";
				
				if((in_array($_SESSION['ID'], $IDs) OR in_array($_SESSION['ID'], $vulling)) AND !in_array($_REQUEST['rooster'], $importRoosters)) {
					if($lid == $_SESSION['ID']) {
						$string .= " <a href='ruilen.php?rooster=". $_REQUEST['rooster'] ."&dienst_d=$dienst&dader=$lid' title='klik om ruiling door te geven'><img src='images/wisselen.png'></a>";
					} else {
						$string .= " <a href='ruilen.php?rooster=". $_REQUEST['rooster'] ."&dienst_s=$dienst&slachtoffer=$lid' title='klik ruiling door te geven'><img src='images/wisselen.png'></a>";
					}
				}
				
				$namen[] = $string;
			}			
			$RoosterString = implode('<br>', $namen);
		} else {
			$RoosterString = $vulling;
		}
		
		if(trim($RoosterString) != '') {
			$block_1[] = "<tr>";
			$block_1[] = "	<td valign='top'>".time2str("%a %d %b %H:%M", $details['start'])."</td>";
			$block_1[] = "	<td valign='top'>". $RoosterString ."</td>";
			$block_1[] = "</tr>".NL;
		}
	}
}

$block_1[] = '</table>';

$block_2[] = '<table>';
$block_2[] = "<tr>";
$block_2[] = "	<td><a href='showCombineRooster.php?rs=". $_REQUEST['rooster'] ."&pdf'>PDF-versie</a></td>";
$block_2[] = "</tr>".NL;
$block_2[] = '</table>';

echo $HTMLHeader;
echo implode(NL, $text);
echo "<table width=100% border=0>";
echo "<tr>";
echo "	<td width='50%' valign='top'>". showBlock(implode(NL, $block_1), 100)."</td>";
echo "	<td width='50%' valign='top'>". showBlock(implode(NL, $block_2), 100)."</td>";
echo "</tr>";
echo "</table>";
echo $HTMLFooter;
?>
