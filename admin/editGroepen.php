<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

$id		= getParam('id', '');
$groepData = getGroupDetails($id);

if(isset($_POST['save'])) {
	if(isset($_REQUEST['new'])) {
		$sql = "INSERT INTO $TableGroups ($GroupNaam, $GroupBeheer) VALUES ('". $_POST['naam'] ."', ". $_POST['beheerder'] .")";
		$actie = 'add';
	} else {
		$sql = "UPDATE $TableGroups SET $GroupNaam = '". $_POST['naam'] ."', $GroupBeheer = ". $_POST['beheerder'] ." WHERE $GroupID = ". $_POST['id'];
		$actie = 'change';
	}
	
	if(mysqli_query($db, $sql)) {
		$text[] = "Groep opgeslagen";	
		toLog('info', $_SESSION['realID'], '', 'Groep '. $_POST['naam'] .' '. ($actie == 'add' ? 'toegevoegd' : 'gewijzigd'));
	} else {
		$text[] = "Probleem met opslaan groep";
		toLog('error', $_SESSION['realID'], '', 'Kon groep '. $_POST['naam'] .' niet '. ($actie == 'add' ? 'toevoegen' : 'wijzigen'));
	}
} elseif(isset($_POST['delete'])) {
	$text[] = "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
	$text[] = "<input type='hidden' name='id' value='$id'>";
	$text[] = "<table>";
	$text[] = "<tr>";
	$text[] = "	<td colspan='2'>Weet je zeker dat je de groep <i>". $groepData['naam'] ."</i> met al zijn leden wilt verwijderen?</td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td align='left'><input type='submit' name='real_delete' value='Ja'></td>";
	$text[] = "	<td align='right'><input type='submit' name='foutje' value='Nee'></td>";
	$text[] = "</tr>";
	$text[] = "</table>";
	$text[] = "</form>";
} elseif(isset($_POST['real_delete'])) {	
	$sql_groep = "DELETE FROM $TableGroups WHERE $GroupID = ". $_POST['id'];
	if(mysqli_query($db, $sql_groep)) {
		$text[] = "Groep <i>". $groepData['naam'] ."</i> verwijderd<br>";
		toLog('info', $_SESSION['realID'], '', 'Groep '. $groepData['naam'] .' verwijderd');
	} else {
		$text[] = "Probleem met verwijderen groep <i>". $groepData['naam'] ."</i><br>";
		toLog('error', $_SESSION['realID'], '', 'Kon groep '. $groepData['naam'] .' niet verwijderen');
	}
	
	$sql_leden = "DELETE FROM $TableGrpUsr WHERE $GrpUsrGroup = ". $_POST['id'];
	if(mysqli_query($db, $sql_leden)) {
		$text[] = "Leden van <i>". $groepData['naam'] ."</i> verwijderd<br>";
		toLog('debug', $_SESSION['realID'], '', 'leden van '. $groepData['naam'] .' verwijderd');
	} else {
		$text[] = "Probleem met verwijderen leden van <i>". $groepData['naam'] ."</i><br>";
		toLog('error', $_SESSION['realID'], '', 'Kon leden van '. $groepData['naam'] .' niet verwijderen');
	}
	
	$sql_rooster = "DELETE FROM $TableRoosters WHERE $RoostersGroep = ". $_POST['id'];
	if(mysqli_query($db, $sql_rooster)) {
		$text[] = "Rooster met <i>". $groepData['naam'] ."</i> verwijderd<br>";
		toLog('debug', $_SESSION['realID'], '', 'rooster met '. $groepData['naam'] .' verwijderd');
	} else {
		$text[] = "Probleem met verwijderen rooster met <i>". $groepData['naam'] ."</i><br>";
		toLog('error', $_SESSION['realID'], '', 'Kon rooster met '. $groepData['naam'] .' niet verwijderen');
	}
	
} elseif(isset($_REQUEST['id']) OR isset($_REQUEST['new'])) {	
	$text[] = "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
	
	if(isset($_REQUEST['new'])) {
		$text[] = "<input type='hidden' name='new' value=''>";
		$groepData = array('naam' => '', 'beheer' => 0);
	} else {		
		$text[] = "<input type='hidden' name='id' value='$id'>";
	}	
	
	$text[] = "<table>";
	$text[] = "<tr>";
	$text[] = "	<td>Naam</td>";
	$text[] = "	<td><input type='text' name='naam' value='". $groepData['naam'] ."'></td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td>Beheerder</td>";
	$text[] = "	<td><select name='beheerder'>";
	$groepen = getAllGroups();
	
	foreach($groepen as $groep) {
		$data = getGroupDetails($groep);		
		$text[] = "	<option value='$groep'". ($groep == $groepData['beheer'] ? ' selected' : '') .">". $data['naam'] ."</option>";
	}
	$text[] = "	</select></td>";
	$text[] = "</tr>";
	#$text[] = "<tr>";
	#$text[] = "	<td colspan='2'>&nbsp;</td>";
	#$text[] = "</tr>";
	#$text[] = "<tr>";
	#$text[] = "	<td align='left'><input type='submit' name='save' value='Opslaan'></td>";
	#$text[] = "	<td align='right'><input type='submit' name='delete' value='Verwijderen'></td>";
	#$text[] = "</tr>";	
	$text[] = "</table>";
	$text[] = "<p class='after_table'><input type='submit' name='save' value='Opslaan'>&nbsp;<input type='submit' name='delete' value='Verwijderen'></p>";	
	$text[] = "</form>";
} else {
	$groepen = getAllGroups();
	
	$text[] = "<a href='?new'>Nieuwe groep toevoegen</a>";
	$text[] = "<p>";
	
	foreach($groepen as $groep) {
		$data = getGroupDetails($groep);
		$text[] = "<a href='?id=$groep'>". $data['naam'] ."</a><br>";
	}	
}

echo showCSSHeader();
echo '<div class="content_vert_kolom">'.NL;
echo "<div class='content_block'>".NL. implode(NL, $text).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom\' -->'.NL;
echo showCSSFooter();

?>