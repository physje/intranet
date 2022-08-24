<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');

$db = connect_db();
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

$wijk = 'D';

if(isset($_REQUEST['addID']) AND !isset($_POST['save'])) {
	#$text[] = $_REQUEST['addID'];
	
	$dag		= getParam('dag', date("d"));
	$maand	= getParam('maand', date("m"));
	$jaar		= getParam('jaar', date("Y"));
		
	$text[] = "<form method='post'>";
	$text[] = "<input type='hidden' name='lid' value='". $_REQUEST['addID'] ."'>";
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
	
	#$text[] = "<tr>";
	#$text[] = "	<td>Aantekening</td>";
	#$text[] = "	<td><textarea name='aantekeningS'></textarea></td>";
	#$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td valign='top'>Zichtbaar voor</td>";
	$text[] = "	<td>"; #<input type='checkbox' name='prive'> Alleen mijzelf<br>";
	$text[] = "<input type='checkbox' name='predikant' value='1'> Predikant<br>";
	$text[] = "<input type='checkbox' name='ouderling' value='1'> Ouderling<br>";
	$text[] = "<input type='checkbox' name='bezoeker' value='1'> Pastoraal bezoekers</td>";
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
		$sql = "INSERT INTO $TablePastoraat ($PastoraatIndiener, $PastoraatTijdstip, $PastoraatLid, $PastoraatType, $PastoraatLocatie, $PastoraatZichtOud, $PastoraatZichtPred, $PastoraatZichtPas) VALUES (". $_SESSION['ID'] .", ". mktime(12,12,0,$_POST['maand'],$_POST['dag'],$_POST['jaar']) .", ". $_POST['lid'] .", ". $_POST['type'] .", ". $_POST['locatie'] .", '". (isset($_POST['ouderling']) ? 1 : 0) ."', '". (isset($_POST['predikant']) ? 1 : 0) ."', '". (isset($_POST['bezoeker']) ? 1 : 0) ."')";
		if(mysqli_query($db, $sql)) {
			$text[] = "Opgeslagen<br>";
		} else {
			$text[] = "Probelemen met opslaan<br>";
		}
	}
		
	$wijkLeden = getWijkledenByAdres($wijk);
	$vorig_adres = 0;
	
	$text[] = '<table>';
	
	foreach($wijkLeden as $adres => $leden) {
		foreach($leden as $lid) {
			$text[] = '<tr>';
			
			if($adres != $vorig_adres) {
				$text[] = "	<td colspan='2'><b>". makeName($lid, 5) ."</b></td>";
				$vorig_adres = $adres;
			} else {
				$text[] = "	<td>&nbsp;</td>";
				$text[] = "	<td>". makeName($lid, 1) ."</td>";
			}
			$text[] = "	<td>". date('d-m-Y') ."</td>";
			$text[] = "	<td><a href='?addID=$lid'>+</a></td>";
			$text[] = "</tr>";		
		}
	}
	
	$text[] = '</table>';
	$text[] = "</form>";
}

echo $HTMLHeader;
echo implode("\n", $text);
echo $HTMLFooter;

?>