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

# Als bekend is welke wijk
# Dan checken wie er in het wijkteam zitten van die wijk
if(isset($_REQUEST['ID'])) {
	$user		= new Member($_REQUEST['ID']);	
	$pastor		= $user->getPastor();
	$myGroups	= $user->getTeams();

	$w			= new Wijk;
	$w->wijk	= $user->wijk;
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
	
	# Zit je in het wijkteam, dan mag je verder
	if(($inWijkteam && $rol <> 3 && $rol <> 6) || $_SESSION['useID'] == $pastor) {		
		$bezoeken	= $user->getPastoraleBezoeken();
		
		if(count($bezoeken) > 0) {
			$text[] = "<table>";
			$text[] = "<tr>";
			$text[] = "	<td>&nbsp;</td>";
			$text[] = "	<td><b>Datum</b></td>";
			$text[] = "	<td>&nbsp;</td>";
			$text[] = "	<td><b>Door</b></td>";
			$text[] = "	<td>&nbsp;</td>";
			$text[] = "	<td><b>Type</b></td>";
			$text[] = "	<td>&nbsp;</td>";
			$text[] = "	<td><b>Locatie</b></td>";
			$text[] = "	<td>&nbsp;</td>";
			$text[] = "	<td><b>Aantekening</b></td>";
			$text[] = "</tr>";
			
			foreach($bezoeken as $bezoekID) {
				$bezoek = new Bezoek($bezoekID);
				$indiener = new Member($bezoek->werker);
				
				$text[] = "<tr>";
				if($bezoek->werker == $_SESSION['useID']) {
					$text[] = "	<td><a href='edit.php?id=". $bezoek->id ."'><img src='../images/wisselen.png' height='16' title='Wijzig dit bezoek'></a></td>";
				} else {
					$text[] = "	<td>&nbsp;</td>";
				}
				$text[] = "	<td>". time2str("d LLLL yyyy", $bezoek->tijdstip) ."</td>";
				$text[] = "	<td>&nbsp;</td>";
				$text[] = "	<td>". $indiener->getName() ."</td>";
				$text[] = "	<td>&nbsp;</td>";
				$text[] = "	<td>". $typePastoraat[$bezoek->type] ."</td>";
				$text[] = "	<td>&nbsp;</td>";
				$text[] = "	<td>". $locatiePastoraat[$bezoek->locatie] ."</td>";
				$text[] = "	<td>&nbsp;</td>";
				$text[] = "	<td>". $bezoek->aantekening ."</td>";
				$text[] = "</tr>";
			}
			$text[] = "</table>";
		} else {
			$text[] = "Geen bezoeken geregistreerd";
		}
		
	} else {
		$text[] = "Foei, mag jij hier wel komen";
	}
} else {
	$text[] = "Geen lid bekend";
}


echo showCSSHeader();
echo '<div class="content_vert_kolom_full">'.NL;
echo "<div class='content_block'>".NL. implode(NL, $text).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();
?>