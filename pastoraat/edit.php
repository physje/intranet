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
if(isset($_REQUEST['id'])) {
	$details = getPastoraalbezoekDetails($_REQUEST['id']);
	
	if($details['indiener'] == $_SESSION['ID']) {
		if(isset($_POST['save'])) {
			$sql = "UPDATE $TablePastoraat SET ";
			$sql .= "$PastoraatTijdstip = ". mktime(12,12,0,$_POST['maand'],$_POST['dag'],$_POST['jaar']) .", ";
			$sql .= "$PastoraatType = ". $_POST['type'] .", ";
			$sql .= "$PastoraatLocatie = ". $_POST['locatie'] .", ";
			#$sql .= "$PastoraatZichtOud = '". (isset($_POST['ouderling']) ? 1 : 0) ."', ";
			#$sql .= "$PastoraatZichtPred = '". (isset($_POST['predikant']) ? 1 : 0) ."', ";
			#$sql .= "$PastoraatZichtPas = '". (isset($_POST['bezoeker']) ? 1 : 0) ."', ";
			$sql .= "$PastoraatNote = '". urlencode(str_rot13($_POST['aantekening'])) ."' ";
			$sql .= "WHERE $PastoraatID = ". $_POST['id'];
						
			if(mysqli_query($db, $sql)) {
				$text[] = "Opgeslagen<br>";
				toLog('info', $_SESSION['ID'], $_POST['lid'], 'Pastoraal bezoek ['. $_POST['id'] .'] van '. $_POST['dag'] .'-'. $_POST['maand'] .'-'. $_POST['jaar'] .' gewijzigd');
			} else {
				$text[] = "Probelemen met opslaan<br>";
				toLog('error', $_SESSION['ID'], $_POST['lid'], 'Problemen met wijzigen pastoraal bezoek ['. $_POST['id'] .'] van '. $_POST['dag'] .'-'. $_POST['maand'] .'-'. $_POST['jaar']);
			}						
		} else {
			$dag		= getParam('dag', date("d", $details['datum']));
			$maand	= getParam('maand', date("m", $details['datum']));
			$jaar		= getParam('jaar', date("Y", $details['datum']));
				
			$text[] = "<form method='post'>";
			$text[] = "<input type='hidden' name='id' value='". $_REQUEST['id'] ."'>";
			$text[] = "<input type='hidden' name='lid' value='". $details['lid'] ."'>";
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
			foreach($typePastoraat as $value => $name)	$text[] = "	<option value='$value'". ($value == $details['type'] ? ' selected' : '') .">$name</option>";	
			$text[] = "	</select>";
			$text[] = "	</td>";	
			$text[] = "</tr>";
			
			$text[] = "	<td>Locatie</td>";
			$text[] = "	<td>";
			$text[] = "	<select name='locatie'>";
			$text[] = "	<option value='0'></option>";
			foreach($locatiePastoraat as $value => $name)	$text[] = "	<option value='$value'". ($value == $details['locatie'] ? ' selected' : '') .">$name</option>";	
			$text[] = "	</select>";
			$text[] = "	</td>";	
			$text[] = "</tr>";
			$text[] = "<tr>";
			$text[] = "	<td valign='top'>Aantekening</td>";
			$text[] = "	<td><textarea name='aantekening'>". str_rot13(urldecode($details['note'])) ."</textarea></td>";
			$text[] = "</tr>";
			$text[] = "</table>";
			$text[] = "<p class='after_table'><input type='submit' name='save' value='Opslaan'></p>";			
			$text[] = "</form>";
		}
	} else {
		$text[] = "Foei, mag jij hier wel komen";
	}
} else {
	$text[] = "Geen bezoek gedefinieerd";
}

echo showCSSHeader();
echo '<div class="content_vert_kolom_full">'.NL;
echo "<h1>". makeName($details['lid'], 5) ."</h1>";
echo "<div class='content_block'>".NL. implode(NL, $text).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();
?>