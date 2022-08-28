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
			$text[] = "	<td><textarea name='aantekening'></textarea></td>";
			$text[] = "</tr>";
			$text[] = "<tr>";
			$text[] = "	<td valign='top'>Zichtbaar voor</td>";
			$text[] = "	<td>"; #<input type='checkbox' name='prive'> Alleen mijzelf<br>";
			$text[] = "<input type='checkbox' name='predikant' value='1' checked> Predikant<br>";
			$text[] = "<input type='checkbox' name='ouderling' value='1' checked> Ouderling<br>";
			$text[] = "<input type='checkbox' name='bezoeker' value='1' checked> Pastoraal bezoekers</td>";
			$text[] = "</tr>";	
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
												
			# Alle wijkleden opvragen, zonder zonen en dochters (= false)				
			$wijkLeden = getWijkledenByAdres($wijk, false);
			$vorig_adres = 0;
			
			$text[] = '<table>';
			$text[] = '<tr>';
			$text[] = "	<td><b>Lid</b></td>";
			$text[] = "	<td>&nbsp;</td>";
			$text[] = "	<td><b>Bezoeker</b></td>";
			$text[] = "	<td>&nbsp;</td>";
			$text[] = "	<td><b>Bezoeken</b></td>";
			$text[] = "	<td colspan='2'>&nbsp;</td>";			
			$text[] = '</tr>';
			
			foreach($wijkLeden as $adres => $leden) {
				foreach($leden as $lid) {
					$data = array();
					$datum = '';

					$text[] = '<tr>';
					
					if($adres != $vorig_adres) {
						$text[] = "	<td><b>". makeName($lid, 5) ."</b></td>";
						$vorig_adres = $adres;
					} else {
						$text[] = "	<td>". makeName($lid, 5) ."</td>";
					}
					
					$pastor = getPastor($lid);
					$text[] = "	<td>&nbsp;</td>";
					$text[] = "	<td>". ($pastor > 0 ? makeName($pastor, 5) : '&nbsp;') ."</td>";
					$text[] = "	<td>&nbsp;</td>";
					
					$bezoeken = getPastoraleBezoeken($lid, $_SESSION['ID']);					
										
					foreach($bezoeken as $bezoekID) {
						$details = getPastoraalbezoekDetails($bezoekID);
						
						if(count($data) == 0)	$datum = date('d-m-Y', $details['datum']);
						$data[] = date('d-m-Y', $details['datum']) .' '. $typePastoraat[$details['type']];					  
					}
					
					if(count($data) > 0) {
					    $text[] = "	<td><a href='details.php?ID=$lid' title='". implode("\n", $data) ."' target='bezoek'>". $datum ."</a></td>";
					} else {
					    $text[] = "	<td>&nbsp;</td>";
					}
					
					$text[] = "	<td>&nbsp;</td>";
					$text[] = "	<td><a href='". $_SERVER['PHP_SELF'] ."?wijk=$wijk&addID=$lid'><img src='../images/add-icon.png' height='16' title='Voeg bezoek aan ". makeName($lid, 1) ." toe'></a></td>";
					$text[] = "</tr>";		
				}
			}
			
			$text[] = '</table>';
			$text[] = "</form>";
			$text[] = "</form>";
			$text[] = "<a href='verdeling.php?wijk=$wijk'>Pas verdeling over wijkteam aan</a>";
		}
	} else {
		$text[] = "Ben je wel lid van het wijkteam van wijk $wijk ?";
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