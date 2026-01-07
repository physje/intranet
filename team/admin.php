<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Member.php');
include_once('../Classes/Team.php');
include_once('../Classes/Logging.php');
include_once('../Classes/Rooster.php');

$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

$db = new Mysql();

$id		= getParam('id', 0);
$team	= new Team($id);

if(isset($_POST['save'])) {
	$team->name = $_POST['naam'];
	$team->beheerder = intval($_POST['beheerder']);

	if($team->save()) {
		$text[] = "Team ". $team->name ." opgeslagen";	
		toLog('Team '. $team->name .' '. ($id > 0 ? 'gewijzigd' : 'toegevoegd'));
	} else {
		$text[] = "Probleem met opslaan team ". $team->name;
		toLog('Kon groep '. $team->name .' niet '. ($id > 0 ? 'wijzigen' : 'toevoegen'), 'error');
	}
	
} elseif(isset($_POST['delete'])) {	
	$text[] = "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
	$text[] = "<input type='hidden' name='id' value='$id'>";
	$text[] = "<table>";
	$text[] = "<tr>";
	$text[] = "	<td colspan='2'>Weet je zeker dat je de groep <i>". $team->name ."</i> met al zijn leden wilt verwijderen?</td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td align='left'><input type='submit' name='real_delete' value='Ja'></td>";
	$text[] = "	<td align='right'><input type='submit' name='foutje' value='Nee'></td>";
	$text[] = "</tr>";
	$text[] = "</table>";
	$text[] = "</form>";
} elseif(isset($_POST['real_delete'])) {
	if($team->delete()) {
		$text[] = "Team <i>". $team->name ."</i> verwijderd<br>";
		toLog('Team '. $team->name .' verwijderd');
	} else {
		$text[] = "Probleem met verwijderen team <i>". $team->name  ."</i><br>";
		toLog('Kon team '. $team->name .' niet verwijderen', 'error');
	}

	# Een rooster met een groep die niet meer bestaat zorgt voor troep
	# daarom ook het bijbehorende rooster verwijderen
	$roosterID = Rooster::findRoosterByTeam($team->id);
	if($roosterID > 0) {
		$rooster = new Rooster($roosterID);

		if($rooster->delete()) {
			$text[] = "Rooster <i>". $rooster->naam ."</i> verwijderd<br>";
			toLog('Rooster '. $rooster->naam .' verwijderd');
		} else {
			$text[] = "Probleem met verwijderen rooster <i>". $rooster->naam ."</i><br>";
			toLog('Kon rooster '. $rooster->naam .' niet verwijderen', 'error');
		}
	}
} elseif(isset($_REQUEST['id']) OR isset($_REQUEST['new'])) {	
	$text[] = "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
	
	if(isset($_REQUEST['new'])) {
		$text[] = "<input type='hidden' name='new' value=''>";		
	} else {		
		$text[] = "<input type='hidden' name='id' value='$id'>";
	}	
	
	$text[] = "<table>";
	$text[] = "<tr>";
	$text[] = "	<td>Naam</td>";
	$text[] = "	<td><input type='text' name='naam' value='". $team->name ."'></td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td>Beheerder</td>";
	$text[] = "	<td><select name='beheerder'>";
	
	$teams = Team::getAllTeams();	
	foreach($teams as $id) {
		$beheerderTeam = new Team($id);
		$text[] = "	<option value='". $beheerderTeam->id."'". ($id == $team->beheerder ? ' selected' : '') .">". $beheerderTeam->name ."</option>";		
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
	$teams = Team::getAllTeams();
	
	$text[] = "<a href='?new'>Nieuw team toevoegen</a>";
	$text[] = "<p>";
	
	foreach($teams as $id) {
		$team = new Team($id);
		$text[] = "<a href='?id=". $team->id ."'>". $team->name ."</a><br>";
	}	
}

echo showCSSHeader();
echo '<div class="content_vert_kolom">'.NL;
echo "<div class='content_block'>".NL. implode(NL, $text).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom\' -->'.NL;
echo showCSSFooter();

?>