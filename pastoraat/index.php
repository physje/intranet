<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');

$db = connect_db();
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

# Als bekend is welke wijk
# Dan checken wie er in het wijkteam zitten van die wijk
if(isset($_REQUEST['wijk'])) {	
	$wijk			= strtoupper($_REQUEST['wijk']);
	$wijkteam = getWijkteamLeden($wijk);
	
	$inWijkteam = false;	
	
	if(array_key_exists($_SESSION['useID'], $wijkteam)) {
		$rol = $wijkteam[$_SESSION['useID']];
		$inWijkteam = true;		
	}
	
	if(in_array(49, getMyGroups($_SESSION['useID']))) {	
		$inWijkteam = true;
		$rol = 1;
	}
	
	# Zit je in het wijkteam & heb je de juiste rol, dan mag je verder
	if($inWijkteam AND $rol <> 3 AND $rol <> 6) {		
		# Moet er een bezoek worden toegevoegd
		# En is er nog niet op SAVE geklikt
		# Toon dan het formulier
		if(isset($_REQUEST['addID']) AND !isset($_POST['save'])) {	
			$dag		= getParam('dag', date("d"));
			$maand	= getParam('maand', date("m"));
			$jaar		= getParam('jaar', date("Y"));
			
			$text[] = "<form method='post'>";
			$text[] = "<input type='hidden' name='lid' value='". $_REQUEST['addID'] ."'>";
			$text[] = "<input type='hidden' name='wijk' value='". $_REQUEST['wijk'] ."'>";
			$text[] = "<table>";
			$text[] = "<tr>";	
			$text[] = "	<td>Datum bezoek</td>";
			$text[] = "	<td><select name='dag'>";
			for($d=1 ; $d<=31 ; $d++)	{	$text[] = "<option value='$d'". ($d == $dag ? ' selected' : '') .">$d</option>";	}
			$text[] = "	</select> <select name='maand'>";
			for($m=1 ; $m<=12 ; $m++)	{	$text[] = "<option value='$m'". ($m == $maand ? ' selected' : '') .">". $maandArray[$m] ."</option>";	}
			$text[] = "	</select> <select name='jaar'>";
			for($j=(date('Y') - 1) ; $j<=(date('Y') + 1) ; $j++)	{	$text[] = "<option value='$j'". ($j == $jaar ? ' selected' : '') .">$j</option>";	}
			$text[] = "	</select></td>";	
			$text[] = "<tr>";	
			$text[] = "	<td>Type bezoek</td>";
			$text[] = "	<td>";
			$text[] = "	<select name='type'>";
			$text[] = "	<option value='0'></option>";
			foreach($typePastoraat as $value => $name)	$text[] = "	<option value='$value'>$name</option>";	
			$text[] = "	</select>";
			$text[] = "	</td>";	
			$text[] = "</tr>";
			
			$text[] = "	<td>Locatie</td>";
			$text[] = "	<td>";
			$text[] = "	<select name='locatie'>";
			$text[] = "	<option value='0'></option>";
			foreach($locatiePastoraat as $value => $name)	$text[] = "	<option value='$value'>$name</option>";	
			$text[] = "	</select>";
			$text[] = "	</td>";	
			$text[] = "</tr>";
			$text[] = "<tr>";
			$text[] = "	<td valign='top'>Aantekening</td>";
			$text[] = "	<td><textarea name='aantekening'></textarea><br><small>Geen privacygevoelige informatie invullen</small></td>";
			$text[] = "</tr>";
			$text[] = "</table>";
			$text[] = "<p class='after_table'><input type='submit' name='save' value='Opslaan'></p>";
			$text[] = "</form>";
		} else {
			if(isset($_POST['save'])) {
				$sql = "INSERT INTO $TablePastoraat ($PastoraatIndiener, $PastoraatTijdstip, $PastoraatLid, $PastoraatType, $PastoraatLocatie, $PastoraatZichtOud, $PastoraatZichtPred, $PastoraatZichtPas, $PastoraatNote) VALUES (". $_SESSION['useID'] .", ". mktime(12,12,0,$_POST['maand'],$_POST['dag'],$_POST['jaar']) .", ". $_POST['lid'] .", ". $_POST['type'] .", ". $_POST['locatie'] .", '". (isset($_POST['ouderling']) ? 1 : 0) ."', '". (isset($_POST['predikant']) ? 1 : 0) ."', '". (isset($_POST['bezoeker']) ? 1 : 0) ."', '". urlencode(str_rot13($_POST['aantekening'])) ."')";
				if(mysqli_query($db, $sql)) {
					$text[] = "Opgeslagen<br>";
					toLog('info', $_SESSION['realID'], $_POST['lid'], 'Pastoraal bezoek op '. $_POST['dag'] .'-'. $_POST['maand'] .'-'. $_POST['jaar'] .' toegevoegd');
				} else {
					$text[] = "Probelemen met opslaan<br>";
					toLog('error', $_SESSION['realID'], $_POST['lid'], 'Problemen met opslaan pastoraal bezoek op '. $_POST['dag'] .'-'. $_POST['maand'] .'-'. $_POST['jaar']);
				}
			}
			
			$filter = false;
			if(isset($_REQUEST['filter']) AND $_REQUEST['filter'] == 'true')	$filter = true;
																		
			# Alle wijkleden opvragen, zonder zonen en dochters (= false)				
			$wijkLeden = getWijkledenByAdres($wijk, 0);
			
			$text[] = "<table>";
			$text[] = "<thead>";
			$text[] = "<tr>";
			$text[] = "	<th>Lid</th>";
			$text[] = "	<th>Adres</th>";
			$text[] = "	<th>Ouderling</th>";
			$text[] = "	<th>Pastoraal bezoeker</th>";
			$text[] = "	<th>Bezoeken</th>";
			$text[] = "	<th>&nbsp;</th>";
			$text[] = '</tr>';
			$text[] = "</thead>";
			
			foreach($wijkLeden as $adres => $leden) {					
				$lid = $leden[0];
								
				$pastor		= getPastor($lid);
				$bezoeker	= getBezoeker($lid);				
								
				if(($filter AND ($_SESSION['useID'] == $pastor OR $_SESSION['useID'] == $bezoeker)) OR !$filter) {
					$data = array();
					$datum = '';
				
					$adres		= getWoonAdres($lid);
					$bezoeken	= getPastoraleBezoeken($lid);
					
					$text[] = '<tr>';
					$text[] = "	<td><a href='../profiel.php?id=$lid' target='profiel'>". makeName($lid, 8) ."</a></b></td>";
					$text[] = "	<td>$adres</td>";
					$text[] = "	<td>". ($pastor > 0 ? makeName($pastor, 5) : '&nbsp;') ."</td>";
					$text[] = "	<td>". ($bezoeker > 0 ? makeName($bezoeker, 5) : '&nbsp;') ."</td>";
					foreach($bezoeken as $bezoekID) {					
						$details = getPastoraalbezoekDetails($bezoekID);
						
						if(count($data) == 0)	$datum = date('d-m-Y', $details['datum']);
						$data[] = date('d-m-Y', $details['datum']) .' '. $typePastoraat[$details['type']];					  
					}
					
					if(count($data) > 0) {
						$text[] = "	<td valign='top'><a href='details.php?ID=$lid' title='". implode("\n", $data) ."' target='bezoek'>". $datum ."</a></td>";
					} else {
					  $text[] = "	<td>&nbsp;</td>";
					}
						
					$text[] = "	<td valign='top'><a href='". $_SERVER['PHP_SELF'] ."?wijk=$wijk&addID=$lid'><img src='../images/add-icon.png' height='16' title='Voeg bezoek aan ". makeName($lid, 1) ." toe'></a></td>";
					$text[] = "</tr>";
				}
			}
			
			$text[] = '</table>';
			
			
			$blok[] = "<table>";
			$blok[] = "<tr>";
			$blok[] = "	<td colspan='2'><b>Wijkteam wijk $wijk</b></td>";
			$blok[] = "</tr>";
			
			foreach($wijkteam as $lid => $wijkteamRol) {
				$blok[] = "<tr>";
				$blok[] = "	<td>". makeName($lid, 5) ."</td>";
				$blok[] = "	<td>". $teamRollen[$wijkteamRol] ."</td>";
				$blok[] = "</tr>";
			}
			$blok[] = "</table>";
			$blok[] = "<br>";
			$blok[] = "<br>";
			$blok[] = "<a href='". $_SERVER['PHP_SELF'] ."?wijk=$wijk&filter=".($filter ? 'false' : 'true')."'>".($filter ? 'Toon alle leden van wijk '. $wijk : 'Toon alleen leden waar ik aan ben toegewezen')."</a>";
			
			if($rol == 1) {
				$blok[] = "<br>";
				$blok[] = "<br>";
				$blok[] = "<a href='verdeling.php?wijk=$wijk' target='verdeling'>Wijs ouderling/bezoeker aan wijkleden toe</a>";
			}
			
			$blok[] = "</td>";
			$blok[] = "</tr>";
			$blok[] = "</table>";
		}
	} elseif($inWijkteam) {
		$text[] = "Helaas, als ". strtolower($teamRollen[$rol]) ." van wijk $wijk heb je geen toegang";
	} else {		
		$text[] = "Je bent niet bekend als lid van het wijkteam van wijk $wijk";
	}
} else {
	$text[] = "Welke wijk wil je bekijken";
	$text[] = "<ol>";
	foreach($wijkArray as $wijk) {
		$text[] = "<li><a href='". $_SERVER['PHP_SELF'] ."?wijk=$wijk'>Wijk $wijk</a></li>";
	}
	$text[] = "</ol>";
}



if(isset($_REQUEST['addID'])) {
	$header = array();
	$tables = array('default', 'table_default');
} else {
	$header[] = '<style>';
	$header[] = '@media only screen and (max-width: 760px), (min-device-width: 768px) and (max-device-width: 1024px)  {';
	$header[] = '	td:nth-of-type(1):before { content: "Lid"; }';
	$header[] = '	td:nth-of-type(2):before { content: "Adres"; }';
	$header[] = '	td:nth-of-type(3):before { content: "Ouderling"; }';
	$header[] = '	td:nth-of-type(4):before { content: "Pastoraal bezoeker"; }';
	$header[] = '	td:nth-of-type(5):before { content: "Bezoeken"; }';
	$header[] = "}";
	$header[] = "</style>";	
	
	$tables = array('default', 'table_rot');
}

echo showCSSHeader($tables, $header);

if(isset($blok)) {
	echo '<div class="content_vert_kolom_full">'.NL;
	echo "<div class='content_block'>".NL. implode(NL, $text).NL."</div>".NL;
	echo "<div class='content_block'>".NL. implode(NL, $blok).NL."</div>".NL;
	echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
} else {
	echo '<div class="content_vert_kolom">'.NL;
	if(isset($_REQUEST['addID'])) echo "<h1>". makeName($_REQUEST['addID'], 5) ."</h1>";	
	echo "<div class='content_block'>".NL. implode(NL, $text).NL."</div>".NL;
	echo '</div> <!-- end \'content_vert_kolom\' -->'.NL;
}

echo showCSSFooter();

?>