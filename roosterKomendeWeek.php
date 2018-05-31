<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');

$start = mktime(0,0,0);
$eind = mktime(23,59,59,date("n"), (date("j")+7+(7-date("N"))));
$diensten = getKerkdiensten($start, $eind);
$roosters = getRoosters();

$block_1 = array();
$block_1[] = "<table width='100%' border=0>";
$block_1[] = "<tr>";
$block_1[] = "	<td valign='top' colspan='2'><h1>Diensten tussen ". strftime("%d %B", $start) ." en ". strftime("%d %B", $eind) ."</h1></td>";
$block_1[] = "</tr>".NL;
$block_1[] = "</table>";
$dienstBlocken[] = implode(NL, $block_1);

foreach($diensten as $dienst) {	
	$details = getKerkdienstDetails($dienst);
		
	if(date("H", $details['start']) < 12) {
		$dagdeel = "Ochtenddienst";
	} elseif(date("H", $details['start']) < 18) {
		$dagdeel = "Middagdienst";
	} else {
		$dagdeel = "Avonddienst";
	}
	
	$block_1 = array();
	$block_1[] = "<table width='100%' border=0>";
	$block_1[] = "<tr>";
	$block_1[] = "	<td valign='top' colspan='2'><h2>". $dagdeel .' '. strftime("%d %b", $details['start']).($details['bijzonderheden'] != "" ? ' ('.$details['bijzonderheden'].')' : '').'; '.$details['voorganger']."</h2></td>";
	$block_1[] = "</tr>".NL;
	$block_1[] = "<tr>";
	$block_1[] = "	<td valign='top' width='250'>1ste collecte</td>";
	$block_1[] = "	<td valign='top'>". $details['collecte_1'] ."</td>";
	$block_1[] = "</tr>".NL;
	$block_1[] = "<tr>";
	$block_1[] = "	<td valign='top' width='250'>2de collecte</td>";
	$block_1[] = "	<td valign='top'>". $details['collecte_2'] ."</td>";
	$block_1[] = "</tr>".NL;
	
	foreach($roosters as $rooster) {
		$roosterDetails = getRoosterDetails($rooster);
		
		# Voor sommige roosters is de ochtend- en middag-dienst gelijk
		# Daar houden wij hier rekening mee
		# Standaard gaan we uit van het feit dat voor de huidige dienst het rooster opgezocht moet worden ($roosterDienst = $dienst)
		# Daarna kijken wij of beide diensten aan elkaar gelijk gesteld zijn ($roosterDetails['gelijk']) en zoeken wij alle diensten van die dag op
		# Indien nodig passen wij de dienst aan waarvoor het rooster gezocht moet worden
		$roosterDienst = $dienst;
		if($roosterDetails['gelijk'] == 1) {
			$overigeDiensten = getKerkdiensten(mktime(0,0,0,date("n", $details['start']),date("j", $details['start']),date("Y", $details['start'])), mktime(23,59,59,date("n", $details['start']),date("j", $details['start']),date("Y", $details['start'])));
			if($dienst == $overigeDiensten[1]) {
				$roosterDienst = $overigeDiensten[0];
			} 		
		}
				
		$vulling = getRoosterVulling($rooster, $roosterDienst);
		$string = '';
		
		if($roosterDetails['text_only'] == 1) {
			$string = $vulling;
		} else {
			if(count($vulling) > 0) {			
				$namen = array();
			
				foreach($vulling as $lid) {
					$string = "<a href='profiel.php?id=$lid'>". makeName($lid, 5) ."</a>";
					$namen[] = $string;
				}
				$string = implode('<br>', $namen);
			}
		}
		
		if($string != "") {
			$block_1[] = "<tr>";
			$block_1[] = "	<td valign='top' width='250'><a href='showRooster.php?rooster=$rooster'>". $roosterDetails['naam'] ."</a></td>";
			$block_1[] = "	<td valign='top'>". $string ."</td>";
			$block_1[] = "</tr>".NL;
		}
	}
	$block_1[] = '</table>';
	
	$dienstBlocken[] = implode(NL, $block_1);
}



echo $HTMLHeader;
echo "<table width=100% border=0>";
foreach($dienstBlocken as $block) {
	echo "<tr>";
	echo "	<td valign='top'>". showBlock($block, 100)."</td>";
	echo "</tr>";
	echo "<tr>";
	echo "	<td>&nbsp;</td>";
	echo "</tr>";
}

echo "</table>";
echo $HTMLFooter;
toLog('info', '', '', 'Rooster komende week bekeken');
?>
