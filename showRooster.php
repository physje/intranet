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
	include($cfgProgDir. "secure.php");
	$db = connect_db();
}

$RoosterData = getRoosterDetails($_REQUEST['rooster']);
$diensten = getAllKerkdiensten(true);
$IDs = getGroupMembers($RoosterData['groep']);
$leeg = true;

toLog('debug', $_SESSION['ID'], '', 'Rooster '. $RoosterData['naam'] .' bekeken');

$text[] = "<h1>". $RoosterData['naam'] ."</h1>".NL;
$block_1[] = '<table border=0>'.NL;

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
			$block_1[] = "<tr>".NL;
			$block_1[] = "	<td valign='top'><a href='roosterKomendeWeek.php?id=$dienst'>".time2str("%a %d %b %H:%M", $details['start'])."</a></td>".NL;
			$block_1[] = "	<td valign='top'>". $RoosterString ."</td>".NL;
			$block_1[] = "</tr>".NL;
			$leeg = false;
		}
	}
}

if($leeg) {
	$block_1[] = "<tr>".NL;
	$block_1[] = "	<td colspan='2'>Dit rooster is leeg</td>".NL;
	$block_1[] = "</tr>".NL;
}

$block_1[] = '</table>'.NL;

$block_2[] = '<table>'.NL;
$block_2[] = "<tr>".NL;
$block_2[] = "	<td><a href='showCombineRooster.php?rs=". $_REQUEST['rooster'] ."&pdf'>PDF-versie</a></td>".NL;
$block_2[] = "</tr>".NL;
$block_2[] = '</table>'.NL;

echo $HTMLHeader;
echo '<table border=0 width=100%>'.NL;
echo '<tr>'.NL;

# Als site bekeken wordt op een mobieltje
if(isMobile()) {
	echo "	<td>".NL;
	echo implode(NL, $block_1);
	echo "<p>".NL;
	echo implode(NL, $block_2);
	echo "</td>".NL;
} else {
	echo "	<td width='50%' valign='top'>". showBlock(implode(NL, $block_1), 100)."</td>".NL;
	echo "	<td width='50%' valign='top'>". showBlock(implode(NL, $block_2), 100)."</td>".NL;
}

echo "</tr>".NL;
echo "</table>".NL;
echo $HTMLFooter;
?>
