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
if(isset($_REQUEST['id'])) {
	$bezoek = new Bezoek($_REQUEST['id']);
	$user	= new Member($bezoek->lid);
	
	if($bezoek->werker == $_SESSION['useID']) {
		if(isset($_POST['save'])) {
			$bezoek->tijdstip	= mktime(12,12,0,$_POST['maand'],$_POST['dag'],$_POST['jaar']);
			$bezoek->type		= $_POST['type'];
			$bezoek->locatie	= $_POST['locatie'];
			$bezoek->aantekening	= $_POST['aantekening'];
									
			if($bezoek->save()) {
				$text[] = "Opgeslagen<br>";
				toLog('Pastoraal bezoek ['. $bezoek->id .'] van '. $_POST['dag'] .'-'. $_POST['maand'] .'-'. $_POST['jaar'] .' gewijzigd', '', $bezoek->lid);
			} else {
				$text[] = "Probelemen met opslaan<br>";
				toLog('Problemen met wijzigen pastoraal bezoek ['. $bezoek->id .'] van '. $_POST['dag'] .'-'. $_POST['maand'] .'-'. $_POST['jaar'], 'error', $bezoek->lid);
			}						
		} else {
			$dag	= getParam('dag', date("d", $bezoek->tijdstip));
			$maand	= getParam('maand', date("m", $bezoek->tijdstip));
			$jaar	= getParam('jaar', date("Y", $bezoek->tijdstip));
				
			$text[] = "<form method='post'>";
			$text[] = "<input type='hidden' name='id' value='". $bezoek->id ."'>";
			$text[] = "<input type='hidden' name='lid' value='". $bezoek->lid ."'>";
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
			foreach($typePastoraat as $value => $name)	$text[] = "	<option value='$value'". ($value == $bezoek->type ? ' selected' : '') .">$name</option>";	
			$text[] = "	</select>";
			$text[] = "	</td>";	
			$text[] = "</tr>";			
			$text[] = "	<td>Locatie</td>";
			$text[] = "	<td>";
			$text[] = "	<select name='locatie'>";
			$text[] = "	<option value='0'></option>";
			foreach($locatiePastoraat as $value => $name)	$text[] = "	<option value='$value'". ($value == $bezoek->locatie ? ' selected' : '') .">$name</option>";	
			$text[] = "	</select>";
			$text[] = "	</td>";	
			$text[] = "</tr>";
			$text[] = "<tr>";
			$text[] = "	<td valign='top'>Aantekening</td>";
			$text[] = "	<td><textarea name='aantekening'>". $bezoek->aantekening ."</textarea></td>";
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
echo "<h1>". $user->getName(5) ."</h1>";
echo "<div class='content_block'>".NL. implode(NL, $text).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();
?>