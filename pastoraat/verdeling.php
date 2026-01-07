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
if(isset($_REQUEST['wijk'])) {
	$w			= new Wijk;
	$w->wijk	= $_REQUEST['wijk'];
	$wijkteam	= $w->getWijkteam();
	
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
				$user = new Member();
				$user->id = $lid;
				$user->pastor = $pastor;
				
				if(isset($_POST['bezoeker'][$lid])) {
					$user->bezoeker = $_POST['bezoeker'][$lid];
				} else {
					$user->bezoeker = $user->getBezoeker();
				}
				
				$user->setPastor();				
			}
			
			toLog('Verdeling ouderlingen/pastoraal bezoekers wijk '. $w->wijk .' aangepast');
			
			$text[] = "<b>Wijzigingen opgeslagen</b><br><br>Je kan dit venster sluiten om terug te gaan naar het wijk-overzicht.<p>&nbsp;</p>";
		}

		# Leden opvragen
		$wijkLeden = $w->getWijkleden();

		$masterSelectPastor = $masterSelectBezoeker = array();
		
		foreach($wijkteam as $id => $rol) {
			if($rol == 1) {
				$pas = new Member($id);
				$masterSelectPastor[$id] = $pas->getName();
			} 

			if($rol == 4 OR $rol == 5) {
				$bez = new Member($id);
				$masterSelectBezoeker[$id] = $bez->getName();
			} 
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
			if(is_array($leden)) {
				$lid = new Member($leden[0]);
			} else {
				$lid = new Member($leden);
			}
			
			$selectPastor = $masterSelectPastor;
			$selectBezoeker = $masterSelectBezoeker;
						
			$p = $lid->getPastor();
			$b = $lid->getBezoeker();
			
			# Als er een ouderling bekend is, die niet in het wijkteam zit
			# voeg die naam dan toe aan de selectie-lijst
			if($p > 0 && !array_key_exists($p, $wijkteam)) {
				$pastor		= new Member($p);
				$selectPastor[$p] = $pastor->getName();
			}

			# Als er een pastoraal bezoek bekend is, die niet in het wijkteam zit
			# voeg die naam dan toe aan de selectie-lijst
			if($b > 0 && !array_key_exists($b, $wijkteam)) {
				$bezoeker	= new Member($b);
				$selectBezoeker[$b] = $bezoeker->getName();
			}	
							
			$text[] = '<tr>';				
			$text[] = "	<td>". $lid->getName(5) ."</td>";
			$text[] = "	<td>";
			$text[] = "	<select name='ouderling[". $lid->id ."]'>";
			$text[] = "	<option value='0'". ($p == 0 ? ' selected' : '') ."></option>";
			foreach($selectPastor as $teamLid => $naam)	$text[] = "	<option value='$teamLid'". ($p == $teamLid ? ' selected' : '') .">". $naam ."</option>";	
			$text[] = "	</select>";
			$text[] = "	</td>";
			$text[] = "	<td>";
			
			if(count($selectBezoeker) > 0) {
				$text[] = "	<select name='bezoeker[". $lid->id ."]'>";
				$text[] = "	<option value='0'". ($b == 0 ? ' selected' : '') ."></option>";
				foreach($selectBezoeker as $teamLid => $naam)	$text[] = "	<option value='$teamLid'". ($b == $teamLid ? ' selected' : '') .">". $naam ."</option>";	
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
		$text[] = "Helaas, als ". strtolower($teamRollen[$rol]) ." van wijk ". $w->wijk ." heb je geen toegang";
	} else {		
		$text[] = "Je bent niet bekend als lid van het wijkteam van wijk ". $w->wijk;
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