<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');

if(isset($_REQUEST['id'])) {
	$cfgProgDir = 'auth/';
	include($cfgProgDir. "secure.php");
		
	$details = getKerkdienstDetails($_REQUEST['id']);
	$start = mktime(0,0,0,date("n", $details['start']), date("j", $details['start']), date("Y", $details['start']));
	$eind = mktime(23,59,59,date("n", $details['eind']), date("j", $details['eind']), date("Y", $details['eind']));
	
	toLog('info', '', 'Rooster van dienst '. $_REQUEST['id'] .' bekeken');	
} else {
	$start = mktime(0,0,0);
	//$eind = mktime(23,59,59,date("n"), (date("j")+7+(7-date("N"))));
	$eind = mktime(23,59,59,date("n"), (date("j")+9));
	
	toLog('info', '', 'Rooster komende week bekeken');
}
$diensten = getKerkdiensten($start, $eind);
$roosters = getRoosters();

$dienstBlocken = array();

foreach($diensten as $dienst) {	
	$details	= getKerkdienstDetails($dienst);
	$dagdeel	= formatDagdeel($details['start']);
	
	$block_1 = array();
	$block_1[] = "<table>";
	$block_1[] = "<tr>";
	$block_1[] = "	<td colspan='2'><h2>". ucfirst($dagdeel) .' '. time2str("%d %b", $details['start']).($details['bijzonderheden'] != "" ? ' ('.$details['bijzonderheden'].')' : '').'; '.$details['voorganger']."</h2></td>";
	$block_1[] = "</tr>".NL;
	$block_1[] = "<tr>";
	$block_1[] = "	<td>". ($details['collecte_2'] != '' ? '1ste collecte' : 'Collecte') ."</td>";
	$block_1[] = "	<td>". $details['collecte_1'] ."</td>";
	$block_1[] = "</tr>".NL;
	
	if($details['collecte_2'] != '') {	
		$block_1[] = "<tr>";
		$block_1[] = "	<td>2de collecte</td>";
		$block_1[] = "	<td>". $details['collecte_2'] ."</td>";
		$block_1[] = "</tr>".NL;
	}
	
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
			if(isset($overigeDiensten[1]) AND $dienst == $overigeDiensten[1]) {
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
			$block_1[] = "	<td><a href='showRooster.php?rooster=$rooster'>". $roosterDetails['naam'] ."</a></td>";
			$block_1[] = "	<td>". $string ."</td>";
			$block_1[] = "</tr>".NL;
		}
	}
	$block_1[] = '</table>';
	
	$dienstBlocken[] = implode(NL, $block_1);
}


echo showCSSHeader(array('default', 'table_default'));
echo '<div class="content_vert_kolom">'.NL;
echo "<h1>". (isset($_REQUEST['id']) ? "Diensten van ". time2str("%d %B", $start) : "Diensten tussen ". time2str("%d %B", $start) ." en ". time2str("%d %B", $eind)) ."</h1>";

foreach($dienstBlocken as $block) {
	echo "<div class='content_block'>".NL. $block .NL."</div>".NL;
}

echo '</div> <!-- end \'content_vert_kolom\' -->'.NL;
echo showCSSFooter();

?>
