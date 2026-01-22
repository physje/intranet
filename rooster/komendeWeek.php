<?php

include_once('../Classes/Kerkdienst.php');
include_once('../Classes/Logging.php');
include_once('../Classes/Rooster.php');
include_once('../Classes/Vulling.php');
include_once('../Classes/Member.php');
include_once('../Classes/Voorganger.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');

if(isset($_REQUEST['id'])) {
	$cfgProgDir = '../auth/';
	include($cfgProgDir. "secure.php");
		
	$kerkdienst = new Kerkdienst($_REQUEST['id']);
	$start = mktime(0,0,0,date("n", $kerkdienst->start), date("j", $kerkdienst->start), date("Y", $kerkdienst->start));
	$eind = mktime(23,59,59,date("n", $kerkdienst->eind), date("j", $kerkdienst->eind), date("Y", $kerkdienst->eind));
	
	toLog('Rooster van dienst '. $_REQUEST['id'] .' bekeken');	
} else {
	$start = mktime(0,0,0);
	//$eind = mktime(23,59,59,date("n"), (date("j")+7+(7-date("N"))));
	$eind = mktime(23,59,59,date("n"), (date("j")+9));
	
	toLog('Rooster komende week bekeken');
}
$diensten = Kerkdienst::getDiensten($start, $eind);
$roosters = Rooster::getAllRoosters();

$dienstBlocken = array();

foreach($diensten as $dienst) {	
	$kerkdienst = new Kerkdienst($dienst);
	$voorganger = new Voorganger($kerkdienst->voorganger);
	$dagdeel	= formatDagdeel($kerkdienst->start);
	
	$block_1 = array();
	$block_1[] = "<table>";
	$block_1[] = "<tr>";
	$block_1[] = "	<td colspan='2'><h2>". ucfirst($dagdeel) .' '. time2str("d LLL", $kerkdienst->start).($kerkdienst->opmerking != "" ? ' ('.$kerkdienst->opmerking.')' : '').'; '.$voorganger->getName()."</h2></td>";
	$block_1[] = "</tr>".NL;
	$block_1[] = "<tr>";
	$block_1[] = "	<td>". ($kerkdienst->collecte_2 != '' ? '1ste collecte' : 'Collecte') ."</td>";
	$block_1[] = "	<td>". $kerkdienst->collecte_1 ."</td>";
	$block_1[] = "</tr>".NL;
	
	if($kerkdienst->collecte_2 != '') {	
		$block_1[] = "<tr>";
		$block_1[] = "	<td>2de collecte</td>";
		$block_1[] = "	<td>". $kerkdienst->collecte_2 ."</td>";
		$block_1[] = "</tr>".NL;
	}
	
	foreach($roosters as $rooster) {
		$roosterDetails = new Rooster($rooster);
		#echo $roosterDetails->naam.'<br>';
		
		# Voor sommige roosters is de ochtend- en middag-dienst gelijk
		# Daar houden wij hier rekening mee
		# Standaard gaan we uit van het feit dat voor de huidige dienst het rooster opgezocht moet worden ($roosterDienst = $dienst)
		# Daarna kijken wij of beide diensten aan elkaar gelijk gesteld zijn ($roosterDetails['gelijk']) en zoeken wij alle diensten van die dag op
		# Indien nodig passen wij de dienst aan waarvoor het rooster gezocht moet worden
		$roosterDienst = $dienst;
		if($roosterDetails->gelijk) {			
			$overigeDiensten = Kerkdienst::getDiensten(mktime(0,0,0,date("n", $kerkdienst->start),date("j", $kerkdienst->start),date("Y", $kerkdienst->start)), mktime(23,59,59,date("n", $kerkdienst->start),date("j", $kerkdienst->start),date("Y", $kerkdienst->start)));
			if(isset($overigeDiensten[1]) AND $dienst == $overigeDiensten[1]) {
				$roosterDienst = $overigeDiensten[0];
			} 		
		}
				
		
		$vulling = new Vulling($roosterDienst, $rooster);
		$string = '';
		
		if($roosterDetails->tekst) {
			$string = $vulling->tekst;
		} else {
			if(count($vulling->leden) > 0) {			
				$namen = array();
			
				foreach($vulling->leden as $lid) {
					$person = new Member($lid);
					$string = "<a href='../profiel.php?id=". $person->id ."' target='profiel'>". $person->getName() ."</a>";
					$namen[] = $string;
				}
				$string = implode('<br>', $namen);
			}
		}
		
		if($string != "") {
			$block_1[] = "<tr>";
			$block_1[] = "	<td><a href='index.php?id=$rooster'>". $roosterDetails->naam ."</a></td>";
			$block_1[] = "	<td>". $string ."</td>";
			$block_1[] = "</tr>".NL;
		}
	}
	$block_1[] = '</table>';
	
	$dienstBlocken[] = implode(NL, $block_1);
}

echo showCSSHeader(array('default', 'table_default'));
echo '<div class="content_vert_kolom">'.NL;
echo "<h1>". (isset($_REQUEST['id']) ? "Diensten van ". time2str("d LLL", $start) : "Diensten tussen ". time2str("d LLL", $start) ." en ". time2str("d LLL", $eind)) ."</h1>";

foreach($dienstBlocken as $block) {
	echo "<div class='content_block'>".NL. $block .NL."</div>".NL;
}

echo '</div> <!-- end \'content_vert_kolom\' -->'.NL;
echo showCSSFooter();

?>
