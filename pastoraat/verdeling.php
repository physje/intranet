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
	
	$inWijkteam = false;
	
	if(array_key_exists($_SESSION['useID'], $wijkteam)) {
		$rol = $wijkteam[$_SESSION['useID']];
		$inWijkteam = true;		
	}
	
	# Alleen ouderlingen mogen hun wijk aanpassen
	if($inWijkteam AND $rol == 1) {
		# Verdeling opslaan	
		if(isset($_POST['save'])) {
			foreach($_POST['ouderling'] as $lid => $pastor) {
				
				$bezoeker = 0;
				if(isset($_POST['bezoeker'][$lid])) $bezoeker = $_POST['bezoeker'][$lid];
				
				$sql_delete = "DELETE FROM $TablePastorVerdeling WHERE $PastorVerdelingLid = $lid";
				if(mysqli_query($db, $sql_delete) AND ($pastor > 0 OR $bezoeker > 0)) {
					$sql_insert = "INSERT INTO $TablePastorVerdeling ($PastorVerdelingLid, $PastorVerdelingPastor, $PastorVerdelingBezoeker) VALUES ($lid, $pastor, $bezoeker)";
					mysqli_query($db, $sql_insert);
				}
			}
			
			toLog('info', $_SESSION['realID'], '', 'Verdeling ouderlingen/pastoraal bezoekers wijk '. $wijk .' aangepast');
			
			$text[] = "<b>Wijzigingen opgeslagen</b><br><br>Je kan dit venster sluiten om terug te gaan naar het wijk-overzicht.<p>&nbsp;</p>";
		}

		# Leden opvragen
		$wijkLeden = getWijkledenByAdres($wijk, 0);

		$masterSelectPastor = $masterSelectBezoeker = array();
		
		foreach($wijkteam as $id => $rol) {
			if($rol == 1) $masterSelectPastor[$id] = '';
			if($rol == 4 OR $rol == 5) $masterSelectBezoeker[$id] = '';
		}
								
		$text[] = "<form method='post'>";		
		$text[] = "<input type='hidden' name='wijk' value='". $_REQUEST['wijk'] ."'>";
		$text[] = "<table>";
		$text[] = "<thead>";
		$text[] = '<tr>';		
		$text[] = "	<th>Lid</th>";
		$text[] = "	<th>Ouderling</th>";
		$text[] = "	<th>Pastoraal Bezoeker</th>";
		$text[] = '</tr>';
		$text[] = "</thead>";
				
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
			
			if(count($selectBezoeker) > 0) {
				$text[] = "	<select name='bezoeker[$lid]'>";
				$text[] = "	<option value='0'". ($bezoeker == 0 ? ' selected' : '') ."></option>";
				foreach($selectBezoeker as $teamLid => $rol)	$text[] = "	<option value='$teamLid'". ($bezoeker == $teamLid ? ' selected' : '') .">". makeName($teamLid, 5) ."</option>";	
				$text[] = "	</select>";
			} else {
				$text[] = "&nbsp;";
			}
			$text[] = "	</td>";			
			
			$text[] = '</tr>';
		}
		$text[] = "</table>";
		$text[] = "<p class='after_table'><input type='submit' name='save' value='Opslaan'></p>";	
		$text[] = "</form>";
	} elseif($inWijkteam) {
		$text[] = "Helaas, als ". strtolower($teamRollen[$rol]) ." van wijk $wijk heb je geen toegang";
	} else {		
		$text[] = "Je bent niet bekend als lid van het wijkteam van wijk $wijk";
	}
} else {
	$text[] = "Geen wijk bekend";
}

$header[] = '<style>';
$header[] = '@media only screen and (max-width: 760px), (min-device-width: 768px) and (max-device-width: 1024px)  {';
$header[] = '	td:nth-of-type(1):before { content: "Lid"; }';
$header[] = '	td:nth-of-type(2):before { content: "Ouderling"; }';
$header[] = '	td:nth-of-type(3):before { content: "Pastoraal bezoeker"; }';
$header[] = "}";
$header[] = "</style>";


echo showCSSHeader(array('default', 'table_rot'), $header);
echo '<div class="content_vert_kolom_full">'.NL;
echo "<div class='content_block'>".NL. implode(NL, $text).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

?>