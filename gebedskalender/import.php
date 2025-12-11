<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Member.php');
include_once('../Classes/Gebedspunt.php');
include_once('../Classes/Logging.php');

$cfgProgDir = '../auth/';
$requiredUserGroups = array(1, 36);
include($cfgProgDir. "secure.php");

if(isset($_POST['text']) AND $_POST['text'] != '') {	
	$regels = explode("\n", $_POST['text']);
		
	foreach($regels as $regel) {
		$delen = explode("|", $regel);
		
		if($regel != '') {
			$gebedspunt = new Gebedspunt();
			$gebedspunt->dag 		= $delen[0];
			$gebedspunt->maand 		= $_POST['maand'];
			$gebedspunt->jaar 		= $_POST['jaar'];
			$gebedspunt->gebedspunt	= $delen[1];

			$gebedspunt->save();
		}
	}
	$blockLinks = "Punten zijn opgeslagen";
	
	toLog("Gebedspunten van ". $maandArray[$_POST['maand']] ." ". $_POST['jaar'] ." geimporteerd", 'info');	
} else {
	$volgendeMaand = mktime (1, 1, 1, (date("n")+1), 1);
	
	$blockLinks = "<form method=post>". NL;
	$blockLinks .= "Voer de gebedspunten in in het volgende formaat : <i>dag|gebedspunt</i><br>". NL;
	$blockLinks .= "Per gebedspunt een regel, en voer <i>dag</i> in als een getal tussen 1 en 31.<br>". NL;
	$blockLinks .= "Een gebedspunt voor vandaag zou je dus moeten invoeren als : ".date("j")."|gebedspunt<br>". NL;
	$blockLinks .= "<textarea name='text' rows=35 cols=135>". getParam('text', '') ."</textarea><br>". NL;
	$blockLinks .= "<select name='maand'>".NL;
	foreach($maandArrayLang as $nr => $naam) {		
		$blockLinks .= "<option value='$nr'". ($nr == date("n", $volgendeMaand) ? ' selected' : '') .">$naam</option>".NL;
	}	
	$blockLinks .= "</select>".NL;
	$blockLinks .= "<select name='jaar'>".NL;
	for($j = (date("Y")-1) ; $j <= (date("Y")+1) ; $j++) {
		$blockLinks .= "<option value='$j'". ($j == date("Y", $volgendeMaand) ? ' selected' : '') .">$j</option>".NL;		
	}	
	$blockLinks .= "</select><br>".NL;	
	$blockLinks .= "<p class='after_table'><input type='submit' name='save' value='Opslaan'></p>". NL;
	$blockLinks .= "</form>". NL;
}

# Pagina tonen
echo showCSSHeader();
echo '<div class="content_vert_kolom_full">'.NL;
echo "<div class='content_block'>". $blockLinks ."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

?>