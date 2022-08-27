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
	
	if(isset($_POST['save'])) {
		foreach($_POST['bezoeker'] as $lid => $pastor) {			
			$sql_delete = "DELETE FROM $TablePastorVerdeling WHERE $PastorVerdelingLid = $lid";
			if(mysqli_query($db, $sql_delete) AND $pastor > 0) {
				$sql_insert = "INSERT INTO $TablePastorVerdeling ($PastorVerdelingLid, $PastorVerdelingPastor) VALUES ($lid, $pastor)";
				mysqli_query($db, $sql_insert);
			}
		}
	}

	# Zit je in het wijkteam, dan mag je verder
	if(array_key_exists($_SESSION['ID'], $wijkteam)) {
		$wijkLeden = getWijkledenByAdres($wijk, false);
		$vorig_adres = 0;
		
		$text[] = "<form method='post'>";		
		$text[] = "<input type='hidden' name='wijk' value='". $_REQUEST['wijk'] ."'>";
		$text[] = "<table>";
		
		foreach($wijkLeden as $adres => $leden) {
			foreach($leden as $lid) {
				$text[] = '<tr>';
				
				if($adres != $vorig_adres) {
					$text[] = "	<td colspan='2'><b>". makeName($lid, 5) ."</b></td>";
					$vorig_adres = $adres;
				} else {
					$text[] = "	<td colspan='2'>". makeName($lid, 5) ."</td>";
				}
				
				#$selectWijkteam = array();
				$selectWijkteam = $wijkteam;
				$pastor = getPastor($lid);				
				if($pastor > 0 AND !array_key_exists($pastor, $wijkteam))	$selectWijkteam[$pastor] = 'gast';
				
				$text[] = "	<td>";
				$text[] = "	<select name='bezoeker[$lid]'>";
				$text[] = "	<option value='0'". ($pastor == 0 ? ' selected' : '') ."></option>";
				foreach($selectWijkteam as $wijklid => $rol)	$text[] = "	<option value='$wijklid'". ($pastor == $wijklid ? ' selected' : '') .">". makeName($wijklid, 5) ."</option>";	
				$text[] = "	</select>";
				$text[] = "	</td>";
				$text[] = '</tr>';
			}
		}
		
		$text[] = "<tr>";
		$text[] = "	<td colspan='2'>&nbsp;</td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td colspan='2'><input type='submit' name='save' value='Opslaan'></td>";
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