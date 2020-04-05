<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');

$db = connect_db();
$cfgProgDir = '../auth/';
$requiredUserGroups = array(1, 36);
include_once($cfgProgDir. "secure.php");

if($_POST['text'] != '') {	
	$regels = explode("\n", $_POST['text']);
	
	$maand = $_POST['maand'];
	$jaar = $_POST['jaar'];
	
	foreach($regels as $regel) {
		$delen = explode("|", $regel);
		
		if($regel != '') {
			$sql = "INSERT INTO $TablePunten ($PuntenDatum, $PuntenPunt) VALUES ('". $jaar.'-'.$maand.'-'.$delen[0] ."', '". urlencode(trim($delen[1])) ."')";
			mysqli_query($db, $sql);		
		}
	}
	$blockLinks = "Punten zijn opgeslagen";
	
	toLog('info', $_SESSION['ID'], '', "Gebedspunten van ". $maandArray[$maand] ." $jaar geimporteerd");	
} else {
	$volgendeMaand = mktime (1, 1, 1, (date("n")+1), 1);
	
	$blockLinks = "<form method=post>". NL;
	$blockLinks .= "Voer de gebedspunten in in het volgende formaat : <i>dag|gebedspunt</i><br>". NL;
	$blockLinks .= "Per gebedspunt een regel, en voer <i>dag</i> in als een getal tussen 1 en 31.<br>". NL;
	$blockLinks .= "Een gebedspunt voor vandaag zou je dus moeten invoeren als : ".date("j")."|gebedspunt<br>". NL;
	$blockLinks .= "<textarea name='text' rows=35 cols=135>". $_POST['text'] ."</textarea><br>". NL;
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
	$blockLinks .= "<input type='submit' name='save' value='Opslaan'>". NL;
	$blockLinks .= "</form>". NL;
}

# Pagina tonen
echo $HTMLHeader;
echo '<table border=0 width=100%>'.NL;
echo '<tr>'.NL;
echo '	<td valign="top" width="50">&nbsp;</td>'.NL;
echo '	<td valign="top">'.NL;
echo showBlock($blockLinks, 100);
echo '	</td>'.NL;
echo '	<td valign="top" width="50">&nbsp;</td>'.NL;
echo '</tr>'.NL;
echo '</table>'.NL;
echo $HTMLFooter;

?>