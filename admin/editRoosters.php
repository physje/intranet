<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

if(isset($_POST['save'])) {
	if($_POST['text_only'] == 1) {
		$_POST['aantal'] = 0;
		$_POST['planner'] = 0;
		$_POST['groep'] = 0;
	} else {
		$_POST['text_only'] = 0;
	}
		
	if(isset($_REQUEST['new'])) {		
		$sql = "INSERT INTO $TableRoosters ($RoostersNaam, $RoostersGroep, $RoostersBeheerder, $RoostersPlanner, $RoostersFields, $RoostersTextOnly, $RoostersReminder) VALUES ('". $_POST['naam'] ."', ". $_POST['groep'] .", ". $_POST['beheerder'] .", ". $_POST['planner'] .", ". $_POST['aantal'] .", ". $_POST['text_only'] .", '". $_POST['reminder'] ."')";
		toLog('info', $_SESSION['ID'], '', 'Roostergegevens '. $_POST['naam'] .' toegevoegd');
	} else {
		$sql = "UPDATE $TableRoosters SET $RoostersNaam = '". $_POST['naam'] ."', $RoostersPlanner = ". $_POST['planner'] .", $RoostersBeheerder = ". $_POST['beheerder'] .", $RoostersGroep = ". $_POST['groep'] .", $RoostersFields = ". $_POST['aantal'] .", $RoostersTextOnly = ". $_POST['text_only'] .", $RoostersReminder = '". $_POST['reminder'] ."', $RoostersAlert = '". $_POST['alert'] ."' WHERE $GroupID = ". $_POST['id'];
		toLog('info', $_SESSION['ID'], '', 'Roostergegevens '. $_POST['naam'] .' gewijzigd');
	}
		
	mysqli_query($db, $sql);
	
	$text[] = "Groep opgeslagen";	
} elseif(isset($_REQUEST['id']) OR isset($_REQUEST['new'])) {	
	$text[] = "<form action='". $_SERVER['PHP_SELF'] ."' method='post'>";
	
	if(isset($_REQUEST['new'])) {
		$text[] = "<input type='hidden' name='new' value=''>";
		$groepData = array('naam' => '', 'groep' => 0);
		$roosterData = array();
	} else {
		$id		= getParam('id', '');
		$roosterData = getRoosterDetails($id);
		$text[] = "<input type='hidden' name='id' value='$id'>";
	}	
	
	$text[] = "<table>";
	$text[] = "<tr>";
	$text[] = "	<td><input type='checkbox' name='text_only' value='1'". ($roosterData['text_only'] == 1 ? ' checked' : '') ."></td>";
	$text[] = "	<td>Dit rooster bevat enkel vrije tekst</td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td>Naam</td>";
	$text[] = "	<td><input type='text' name='naam' value='". $roosterData['naam'] ."'></td>";
	$text[] = "</tr>";	
	$text[] = "<tr>";
	$text[] = "	<td>Beheerder</td>";	
	$text[] = "	<td><select name='beheerder'>";
	$groepen = getAllGroups();	
	foreach($groepen as $groep) {
		$data = getGroupDetails($groep);
		$text[] = "	<option value='$groep'". ($groep == $roosterData['beheerder'] ? ' selected' : '') .">". $data['naam'] ."</option>";
	}
	$text[] = "	</select></td>";
	$text[] = "</tr>";
	
	if($roosterData['text_only'] == 0) {
		$text[] = "<tr>";
		$text[] = "	<td>Te plannen groep</td>";	
		$text[] = "	<td><select name='groep'>";
		$groepen = getAllGroups();	
		foreach($groepen as $groep) {
			$data = getGroupDetails($groep);
			$text[] = "	<option value='$groep'". ($groep == $roosterData['groep'] ? ' selected' : '') .">". $data['naam'] ."</option>";
		}
		$text[] = "	</select></td>";
		$text[] = "</tr>";			
		$text[] = "<tr>";
		$text[] = "	<td>Planner</td>";
		$text[] = "	<td><select name='planner'>";
		$groepen = getAllGroups();	
		foreach($groepen as $groep) {
			$data = getGroupDetails($groep);
			$text[] = "	<option value='$groep'". ($groep == $roosterData['planner'] ? ' selected' : '') .">". $data['naam'] ."</option>";
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
		$text[] = "	<td>Remindermails</td>";
		$text[] = "	<td><select name='reminder'>";		
		$text[] = "<option value='1'". ($roosterData['reminder'] == 1 ? ' selected' : '') .">Ja</option>";
		$text[] = "<option value='0'". ($roosterData['reminder'] == 0 ? ' selected' : '') .">Nee</option>";
		$text[] = "	</select></td>";
		$text[] = "</tr>";
	}
	
	$text[] = "<tr>";
	$text[] = "	<td>Leeg-rooster-alert</td>";
	$text[] = "	<td><select name='alert'>";		
	$text[] = "<option value='0'". ($roosterData['alert'] == 0 ? ' selected' : '') .">Uit</option>";
	$text[] = "<option value='1'". ($roosterData['alert'] == 1 ? ' selected' : '') .">1 week</option>";
	$text[] = "<option value='2'". ($roosterData['alert'] == 2 ? ' selected' : '') .">2 weken</option>";
	$text[] = "<option value='3'". ($roosterData['alert'] == 3 ? ' selected' : '') .">3 weken</option>";
	$text[] = "<option value='4'". ($roosterData['alert'] == 4 ? ' selected' : '') .">4 weken</option>";
	$text[] = "	</select></td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td colspan='2'>&nbsp;</td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td>&nbsp;</td>";
	$text[] = "	<td><input type='submit' name='save' value='Opslaan'></td>";
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