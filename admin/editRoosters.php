<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

if(isset($_POST['save'])) {
	if(isset($_REQUEST['new'])) {
		$sql = "INSERT INTO $TableRoosters ($RoostersNaam, $RoostersGroep, $RoostersFields) VALUES ('". $_POST['naam'] ."', ". $_POST['groep'] .", ". $_POST['aantal'] .")";
		toLog('info', $_SESSION['ID'], '', 'Roostergegevens '. $_POST['naam'] .' toegevoegd');
	} else {
		$sql = "UPDATE $TableRoosters SET $RoostersNaam = '". $_POST['naam'] ."', $RoostersGroep = ". $_POST['groep'] .", $RoostersFields = ". $_POST['aantal'] ." WHERE $GroupID = ". $_POST['id'];
		toLog('info', $_SESSION['ID'], '', 'Roostergegevens '. $_POST['naam'] .' gewijzigd');
	}
	
	mysqli_query($db, $sql);
	
	$text[] = "Groep opgeslagen";	
} elseif(isset($_REQUEST['id']) OR isset($_REQUEST['new'])) {	
	$text[] = "<form action='". $_SERVER['PHP_SELF'] ."' method='post'>";
	
	if(isset($_REQUEST['new'])) {
		$text[] = "<input type='hidden' name='new' value=''>";
		$groepData = array('naam' => '', 'groep' => 0);
	} else {
		$id		= getParam('id', '');
		$roosterData = getRoosterDetails($id);
		$text[] = "<input type='hidden' name='id' value='$id'>";
	}	
	
	$text[] = "<table>";
	$text[] = "<tr>";
	$text[] = "	<td>Naam</td>";
	$text[] = "	<td><input type='text' name='naam' value='". $roosterData['naam'] ."'></td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td>Groep</td>";
	$text[] = "	<td><select name='groep'>";
	$groepen = getAllGroups();	
	foreach($groepen as $groep) {
		$data = getGroupDetails($groep);
		$text[] = "	<option value='$groep'". ($groep == $roosterData['groep'] ? ' selected' : '') .">". $data['naam'] ."</option>";
	}
	$text[] = "	</select></td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td>Aantal personen</td>";
	$text[] = "	<td><select name='aantal'>";		
	for($a=1 ; $a<=10 ; $a++)	{	$text[] = "<option value='$a'". ($a == $roosterData['aantal'] ? ' selected' : '') .">$a</option>";	}	
	$text[] = "	</select></td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td rowspan='2'><input type='submit' name='save' value='Opslaan'></td>";
	$text[] = "</tr>";
	$text[] = "</table>";
	$text[] = "</form>";
} else {
	$roosters = getRoosters();
	
	$text[] = "<a href='?new'>Nieuw rooster toevoegen</a>";
	$text[] = "<p>";
	
	foreach($roosters as $rooster) {
		$data = getRoosterDetails($rooster);
		$text[] = "<a href='?id=$rooster'>". $data['naam'] ."</a><br>";
	}	
}

echo $HTMLHeader;
echo implode("\n", $text);
echo $HTMLFooter;

?>