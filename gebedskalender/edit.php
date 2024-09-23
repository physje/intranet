<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');

$db = connect_db();
$cfgProgDir = '../auth/';
$requiredUserGroups = array(1, 36);
include($cfgProgDir. "secure.php");

if(isset($_POST['save'])) {	
	$data = $_POST['datum'];
	$punten = $_POST['gebedspunt'];
	$blockLinks = '';
	
	foreach($punten as $id => $punt) {
		$oldData = getGebedspunt($id);
		
		if($oldData['gebedspunt'] != $punt) {
			$sql = "UPDATE $TablePunten SET $PuntenDatum = '". $data[$id] ."', $PuntenPunt = '". urlencode(trim($punt)) ."' WHERE $PuntenID = $id";
		
			if(!mysqli_query($db, $sql)) {
				$blockLinks .= "Er ging iets mis met het opslaan van het gebedspunt voor $id<br>";
				toLog('error', $_SESSION['realID'], '', 'Gebedspunt van '. $data[$id] .' kon niet worden gewijzigd');
			} else {
				toLog('info', $_SESSION['realID'], '', 'Gebedspunt van '. $data[$id] .' gewijzigd');
			}
		}
	}
	
	if($blockLinks == '') {
		$blockLinks = "Punten zijn opgeslagen";
	}	
} else {
	$punten = getGebedspunten(date("Y-m-d"), date("Y-m-d", mktime(0,0,1,date("n"),date("j"), (date("Y")+1))));
	
	$blockLinks = "<form method=post>". NL;	
	$blockLinks .= "<table>". NL;
	
	foreach($punten as $punt) {
		$data = getGebedspunt($punt);
		$blockLinks .= "<tr>". NL;
		$blockLinks .= "	<td valign='top'><input type='text' name='datum[$punt]' value='". $data['datum'] ."'></td>". NL;
		$blockLinks .= "	<td valign='top'><textarea name='gebedspunt[$punt]' rows=4 cols=100>". trim($data['gebedspunt']) ."</textarea></td>". NL;
		$blockLinks .= "</tr>".NL;
	}	
	$blockLinks .= "</table>". NL;
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