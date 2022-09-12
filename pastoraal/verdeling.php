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
	$wijk			= $_REQUEST['wijk'];
	$wijkteam = getWijkteamLeden($wijk);
	
	# Zit je in het wijkteam, dan mag je verder
	if(array_key_exists($_SESSION['ID'], $wijkteam)) {
		# Verdeling opslaan	
		if(isset($_POST['save'])) {
			foreach($_POST['ouderling'] as $lid => $pastor) {
				$bezoeker = $_POST['bezoeker'][$lid];
				
				$sql_delete = "DELETE FROM $TablePastorVerdeling WHERE $PastorVerdelingLid = $lid";
				if(mysqli_query($db, $sql_delete) AND ($pastor > 0 OR $bezoeker > 0)) {
					$sql_insert = "INSERT INTO $TablePastorVerdeling ($PastorVerdelingLid, $PastorVerdelingPastor, $PastorVerdelingBezoeker) VALUES ($lid, $pastor, $bezoeker)";
					mysqli_query($db, $sql_insert);
				}
			}
		}

		# Leden opvragen
		$wijkLeden = getWijkledenByAdres($wijk, 0);
				
		$text[] = "<form method='post'>";		
		$text[] = "<input type='hidden' name='wijk' value='". $_REQUEST['wijk'] ."'>";
		$text[] = "<table border=0>";
		$text[] = '<tr>';		
		$text[] = "	<td>Lid</td>";
		$text[] = "	<td>Ouderling</td>";
		$text[] = "	<td>Pastoraal Bezoeker</td>";
		$text[] = '</tr>';
		
		foreach($wijkteam as $id => $rol) {
			if($rol == 1) $masterSelectPastor[$id] = '';
			if($rol == 4 OR $rol == 5) $masterSelectBezoeker[$id] = '';
		}
				
		foreach($wijkLeden as $adres => $leden) {
			$lid = $leden[0];
			
			$selectPastor = $masterSelectPastor;
			$selectBezoeker = $masterSelectBezoeker;
						
			$pastor = getPastor($lid);
			$bezoeker = getBezoeker($lid);
			
			if($pastor > 0 AND !array_key_exists($pastor, $wijkteam))	$selectPastor[$pastor] = 'gast';
			if($bezoeker > 0 AND !array_key_exists($bezoeker, $wijkteam))	$selectBezoeker[$bezoeker] = 'gast';
							
			$text[] = '<tr>';				
			$text[] = "	<td>". makeName($lid, 5) ."</td>";
			$text[] = "	<td>";
			$text[] = "	<select name='ouderling[$lid]'>";
			$text[] = "	<option value='0'". ($pastor == 0 ? ' selected' : '') ."></option>";
			foreach($selectPastor as $teamLid => $rol)	$text[] = "	<option value='$teamLid'". ($pastor == $teamLid ? ' selected' : '') .">". makeName($teamLid, 5) ."</option>";	
			$text[] = "	</select>";
			$text[] = "	</td>";
			
			$text[] = "	<td>";
			$text[] = "	<select name='bezoeker[$lid]'>";
			$text[] = "	<option value='0'". ($bezoeker == 0 ? ' selected' : '') ."></option>";
			foreach($selectBezoeker as $teamLid => $rol)	$text[] = "	<option value='$teamLid'". ($bezoeker == $teamLid ? ' selected' : '') .">". makeName($teamLid, 5) ."</option>";	
			$text[] = "	</select>";
			$text[] = "	</td>";
			
			$text[] = '</tr>';
		}
		
		$text[] = "<tr>";
		$text[] = "	<td colspan='3'>&nbsp;</td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td colspan='3'><input type='submit' name='save' value='Opslaan'></td>";
		$text[] = "</tr>";
		$text[] = "</table>";
		$text[] = "</form>";
	} else {
		$text[] = "Foei, mag jij hier wel komen";
	}
} else {
	$text[] = "Geen wijk bekend";
}


echo $HTMLHeader;
echo implode("\n", $text);
echo $HTMLFooter;

?>