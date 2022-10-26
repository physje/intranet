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
	
	if(array_key_exists($_SESSION['ID'], $wijkteam)) {
		$rol = $wijkteam[$_SESSION['ID']];
		$inWijkteam = true;		
	}
	
	if(in_array(49, getMyGroups($_SESSION['ID']))) {	
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
			
			$text[] = "<h1>". makeName($_REQUEST['addID'], 5) ."</h1>";	
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
			#$text[] = "<tr>";
			#$text[] = "	<td valign='top'>Zichtbaar voor</td>";
			#$text[] = "	<td>"; #<input type='checkbox' name='prive'> Alleen mijzelf<br>";
			#$text[] = "<input type='checkbox' name='predikant' value='1' checked> Predikant<br>";
			#$text[] = "<input type='checkbox' name='ouderling' value='1' checked> Ouderling<br>";
			#$text[] = "<input type='checkbox' name='bezoeker' value='1' checked> Pastoraal bezoekers</td>";
			#$text[] = "</tr>";	
			$text[] = "<tr>";
			$text[] = "	<td colspan='2'>&nbsp;</td>";
			$text[] = "</tr>";
			$text[] = "<tr>";
			$text[] = "	<td colspan='2'><input type='submit' name='save' value='Opslaan'></td>";
			$text[] = "</tr>";
			$text[] = "</table>";
			$text[] = "</form>";
		} else {
			if(isset($_POST['save'])) {
				$sql = "INSERT INTO $TablePastoraat ($PastoraatIndiener, $PastoraatTijdstip, $PastoraatLid, $PastoraatType, $PastoraatLocatie, $PastoraatZichtOud, $PastoraatZichtPred, $PastoraatZichtPas, $PastoraatNote) VALUES (". $_SESSION['ID'] .", ". mktime(12,12,0,$_POST['maand'],$_POST['dag'],$_POST['jaar']) .", ". $_POST['lid'] .", ". $_POST['type'] .", ". $_POST['locatie'] .", '". (isset($_POST['ouderling']) ? 1 : 0) ."', '". (isset($_POST['predikant']) ? 1 : 0) ."', '". (isset($_POST['bezoeker']) ? 1 : 0) ."', '". urlencode(str_rot13($_POST['aantekening'])) ."')";
				if(mysqli_query($db, $sql)) {
					$text[] = "Opgeslagen<br>";
				} else {
					$text[] = "Probelemen met opslaan<br>";
				}
			}
			
			$filter = false;
			if(isset($_REQUEST['filter']) AND $_REQUEST['filter'] == 'true')	$filter = true;
																		
			# Alle wijkleden opvragen, zonder zonen en dochters (= false)				
			$wijkLeden = getWijkledenByAdres($wijk, 0);
			
			# Een tabel met 2 kolommen
			# In de linker kolom de lijst met alle leden
			# In de rechter kolom de lijst met wijkteam-leden
			$text[] = "<table border=0 width='100%'>";
			$text[] = "<tr>";
			$text[] = "	<td width='60%'>";
			
			# Linker kolom
			$text[] = '<table border=0>';
			$text[] = '<tr>';
			$text[] = "	<td colspan='2'><b>Lid</b></td>";
			$text[] = "	<td>&nbsp;</td>";
			$text[] = "	<td><b>Ouderling</b></td>";
			$text[] = "	<td>&nbsp;</td>";
			$text[] = "	<td><b>Pastoraal bezoeker</b></td>";
			$text[] = "	<td>&nbsp;</td>";
			$text[] = "	<td><b>Bezoeken</b></td>";
			$text[] = "	<td colspan='2'>&nbsp;</td>";			
			$text[] = '</tr>';
			
			foreach($wijkLeden as $adres => $leden) {					
				$lid = $leden[0];
								
				$pastor		= getPastor($lid);
				$bezoeker	= getBezoeker($lid);				
								
				if(($filter AND ($_SESSION['ID'] == $pastor OR $_SESSION['ID'] == $bezoeker)) OR !$filter) {
					$data = array();
					$datum = '';
				
					$adres		= getWoonAdres($lid);
					$bezoeken	= getPastoraleBezoeken($lid);
					
					$text[] = '<tr>';
					$text[] = "	<td colspan='2'><b><a href='../profiel.php?id=$lid' target='profiel'>". makeName($lid, 8) ."</a></b></td>";
					$text[] = "	<td rowspan='2'>&nbsp;</td>";
					$text[] = "	<td rowspan='2' valign='top'>". ($pastor > 0 ? makeName($pastor, 5) : '&nbsp;') ."</td>";
					$text[] = "	<td rowspan='2'>&nbsp;</td>";
					$text[] = "	<td rowspan='2' valign='top'>". ($bezoeker > 0 ? makeName($bezoeker, 5) : '&nbsp;') ."</td>";
					$text[] = "	<td rowspan='2'>&nbsp;</td>";
					foreach($bezoeken as $bezoekID) {					
						$details = getPastoraalbezoekDetails($bezoekID);
						
						if(count($data) == 0)	$datum = date('d-m-Y', $details['datum']);
						$data[] = date('d-m-Y', $details['datum']) .' '. $typePastoraat[$details['type']];					  
					}
					
					if(count($data) > 0) {
						$text[] = "	<td rowspan='2' valign='top'><a href='details.php?ID=$lid' title='". implode("\n", $data) ."' target='bezoek'>". $datum ."</a></td>";
					} else {
					  $text[] = "	<td rowspan='2'>&nbsp;</td>";
					}
						
					$text[] = "	<td rowspan='2'>&nbsp;</td>";
					$text[] = "	<td rowspan='2' valign='top'><a href='". $_SERVER['PHP_SELF'] ."?wijk=$wijk&addID=$lid'><img src='../images/add-icon.png' height='16' title='Voeg bezoek aan ". makeName($lid, 1) ." toe'></a></td>";
					$text[] = "</tr>";
					$text[] = "<tr>";
					$text[] = "	<td width='10'>&nbsp;</td>";
					$text[] = "	<td>$adres</td>";
					$text[] = "</tr>";
				}
			}
			
			$text[] = "<tr>";
			$text[] = "	<td colspan='7'>&nbsp;</td>";			
			$text[] = "</tr>";
			$text[] = '</table>';
			
			# Middelste & rechter kolom
			$text[] = "</td>";
			$text[] = "<td width=25>&nbsp;</td>";
			$text[] = "<td width='38' valign='top'>";
			
			$text[] = "<table border=0>";
			$text[] = "<tr>";
			$text[] = "	<td colspan='2'><b>Wijkteam wijk $wijk</b></td>";
			$text[] = "</tr>";
			
			foreach($wijkteam as $lid => $wijkteamRol) {
				$text[] = "<tr>";
				$text[] = "	<td>". makeName($lid, 5) ."</td>";
				$text[] = "	<td>". $teamRollen[$wijkteamRol] ."</td>";
				$text[] = "</tr>";
			}
			$text[] = "</table>";
			$text[] = "<br>";
			$text[] = "<br>";
			$text[] = "<a href='". $_SERVER['PHP_SELF'] ."?wijk=$wijk&filter=".($filter ? 'false' : 'true')."'>".($filter ? 'Toon alle leden van wijk '. $wijk : 'Toon alleen leden waar ik aan ben toegewezen')."</a>";
			
			if($rol == 1) {
				$text[] = "<br>";
				$text[] = "<br>";
				$text[] = "<a href='verdeling.php?wijk=$wijk' target='verdeling'>Wijs ouderling/bezoeker aan wijkleden toe</a>";
			}
			
			$text[] = "</td>";
			$text[] = "</tr>";
			$text[] = "</table>";
			
			
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


echo $HTMLHeader;
echo implode("\n", $text);
echo $HTMLFooter;

?>