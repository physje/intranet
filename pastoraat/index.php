<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Member.php');
include_once('../Classes/Bezoek.php');
include_once('../Classes/Wijk.php');
include_once('../Classes/Logging.php');

$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

$user = new Member($_SESSION['useID']);
$myGroups = $user->getTeams();

# Als bekend is welke wijk
# Dan checken wie er in het wijkteam zitten van die wijk
if(isset($_REQUEST['wijk'])) {		
	$w			= new Wijk();
	$w->wijk	= strtoupper($_REQUEST['wijk']);
	$wijkteam	= $w->getWijkteam();
	
	$inWijkteam = false;	
	
	if(array_key_exists($_SESSION['useID'], $wijkteam)) {
		$rol = $wijkteam[$_SESSION['useID']];
		$inWijkteam = true;		
	}
	
	if(in_array(49, $myGroups)) {	
		$inWijkteam = true;
		$rol = 1;
	}
	
	# Zit je in het wijkteam & heb je de juiste rol, dan mag je verder
	if($inWijkteam AND $rol <> 3 AND $rol <> 6) {		
		# Moet er een bezoek worden toegevoegd
		# En is er nog niet op SAVE geklikt
		# Toon dan het formulier
		if(isset($_REQUEST['addID']) AND !isset($_POST['save'])) {	
			$dag	= getParam('dag', date("d"));
			$maand	= getParam('maand', date("m"));
			$jaar	= getParam('jaar', date("Y"));
			
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
				$bezoek = new Bezoek();
				$bezoek->werker		= $user->id;
				$bezoek->tijdstip	= mktime(12,12,0,$_POST['maand'],$_POST['dag'],$_POST['jaar']);
				$bezoek->lid		= $_POST['lid'];
				$bezoek->type		= $_POST['type'];
				$bezoek->locatie	= $_POST['locatie'];
				$bezoek->zichtbaarOuderling	= (isset($_POST['ouderling']) ? true : false);
				$bezoek->zichtbaarPredikant	= (isset($_POST['predikant']) ? true : false);
				$bezoek->zichtbaarPastoraal	= (isset($_POST['bezoeker']) ? true : false);
				$bezoek->aantekening = $_POST['aantekening'];

				if($bezoek->save()) {
					$text[] = "Opgeslagen<br>";
					toLog('Pastoraal bezoek op '. $_POST['dag'] .'-'. $_POST['maand'] .'-'. $_POST['jaar'] .' toegevoegd', '', $_POST['lid']);
				} else {
					$text[] = "Probelemen met opslaan<br>";
					toLog('Problemen met opslaan pastoraal bezoek op '. $_POST['dag'] .'-'. $_POST['maand'] .'-'. $_POST['jaar'], 'error', $_POST['lid']);
				}
			}
			
			$filter = false;
			if(isset($_REQUEST['filter']) AND $_REQUEST['filter'] == 'true')	$filter = true;
																		
			# Alle wijkleden opvragen, zonder zonen en dochters (= false)				
			$wijkLeden = $w->getWijkleden();
			
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
				unset($bezoeker);
				unset($pastor);

				if(is_array($leden)) {
					$lid = new Member($leden[0]);
				} else {
					$lid = new Member($leden);
				}

				$p = $lid->getPastor();
				$b = $lid->getBezoeker();

				if($p > 0)		$pastor		= new Member($p);
				if($b > 0)		$bezoeker	= new Member($b);
								
				if(($filter && ((isset($pastor) && $_SESSION['useID'] == $pastor->id) || (isset($bezoeker) && $_SESSION['useID'] == $bezoeker->id))) || !$filter) {
					$data = array();
					$datum = '';
				
					$adres		= $lid->getWoonadres();
					$bezoeken	= $lid->getPastoraleBezoeken();
					
					$text[] = '<tr>';
					$text[] = "	<td><a href='../profiel.php?id=". $lid->id ."' target='profiel'>". $lid->getName(8) ."</a></b></td>";
					$text[] = "	<td>$adres</td>";
					$text[] = "	<td>". (isset($pastor) ? $pastor->getName(5) : '&nbsp;') ."</td>";
					$text[] = "	<td>". (isset($bezoeker) ? $bezoeker->getName(5) : '&nbsp;') ."</td>";
					foreach($bezoeken as $bezoekID) {					
						$bezoek = new Bezoek($bezoekID);
						
						# Als $data nog geen elementen heeft (lees net geinitialiseerd), neem dan de datum van het pastoraal bezoek als datum om te tonen in de tabel
						if(count($data) == 0)	$datum = date('d-m-Y', $bezoek->tijdstip);
						$data[] = date('d-m-Y', $bezoek->tijdstip) .($bezoek->type > 0 ? ' '. $typePastoraat[$bezoek->type] : '');
					}
					
					if(count($data) > 0) {
						$text[] = "	<td valign='top'><a href='details.php?ID=". $lid->id ."' title='". implode("\n", $data) ."' target='bezoek'>". $datum ."</a></td>";
					} else {
					  $text[] = "	<td>&nbsp;</td>";
					}
						
					$text[] = "	<td valign='top'><a href='". $_SERVER['PHP_SELF'] ."?wijk=". $w->wijk ."&addID=". $lid->id ."'><img src='../images/add-icon.png' height='16' title='Voeg bezoek aan ". $lid->getName(1) ." toe'></a></td>";
					$text[] = "</tr>";
				}
			}
			
			$text[] = '</table>';
			
			
			$blok[] = "<table>";
			$blok[] = "<tr>";
			$blok[] = "	<td colspan='2'><b>Wijkteam wijk ". $w->wijk ."</b></td>";
			$blok[] = "</tr>";
			
			foreach($wijkteam as $user => $wijkteamRol) {
				$lid = new Member($user);
				$blok[] = "<tr>";
				$blok[] = "	<td>". $lid->getName(5) ."</td>";
				$blok[] = "	<td>". $teamRollen[$wijkteamRol] ."</td>";
				$blok[] = "</tr>";
			}
			$blok[] = "</table>";
			$blok[] = "<br>";
			$blok[] = "<br>";
			$blok[] = "<a href='". $_SERVER['PHP_SELF'] ."?wijk=". $w->wijk ."&filter=".($filter ? 'false' : 'true')."'>".($filter ? 'Toon alle leden van wijk '. $w->wijk : 'Toon alleen leden waar ik aan ben toegewezen')."</a>";
			
			if($rol == 1) {
				$blok[] = "<br>";
				$blok[] = "<br>";
				$blok[] = "<a href='verdeling.php?wijk=". $w->wijk ."' target='verdeling'>Wijs ouderling/bezoeker aan wijkleden toe</a>";
			}
			
			$blok[] = "</td>";
			$blok[] = "</tr>";
			$blok[] = "</table>";
		}
	} elseif($inWijkteam) {
		$text[] = "Helaas, als ". strtolower($teamRollen[$rol]) ." van wijk ". $w->wijk ." heb je geen toegang";
	} else {		
		$text[] = "Helaas, je bent niet bekend als lid van het wijkteam van wijk ". $w->wijk;
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
	echo '<div class="content_vert_kolom_full">'.NL;
	#if(isset($_REQUEST['addID'])) echo "<h1>". makeName($_REQUEST['addID'], 5) ."</h1>";	
	echo "<div class='content_block'>".NL. implode(NL, $text).NL."</div>".NL;
	echo '</div> <!-- end \'content_vert_kolom\' -->'.NL;
}

echo showCSSFooter();

?>