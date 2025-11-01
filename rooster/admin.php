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


$id		= getParam('id', 0);
$rooster = new Rooster($id);

if(isset($_POST['save'])) {
	/*
	if(isset($_POST['text_only'])) {
		$rooster->velden = 0;
		$rooster->planner = 0;
		$rooster->groep = 0;
	} else {
		$rooster->tekst = 0;
	}
	*/
		
	$rooster->velden	= (isset($_POST['aantal']) ? $_POST['aantal'] : $rooster->velden);
	$rooster->planner	= (isset($_POST['planner']) ? $_POST['planner'] : $rooster->planner);	
	$rooster->groep		= (isset($_POST['groep']) ? $_POST['groep'] : $rooster->groep);
	$rooster->reminder	= (isset($_POST['reminder']) ? $_POST['reminder'] : $rooster->reminder);
	$rooster->tekst		= (isset($_POST['text_only']) ? $_POST['text_only'] : 0);
	$rooster->naam		= $_POST['naam'];
	$rooster->beheerder	= $_POST['beheerder'];	
	$rooster->alert		= $_POST['alert'];
		
	if($rooster->save()) {
		$text[] = "Rooster opgeslagen";	
		toLog('Rooster '. $rooster->naam .' '. ($rooster->id == 0 ? 'toegevoegd' : 'gewijzigd'));
	} else {
		$text[] = "Probleem met opslaan rooster";
		toLog('Kon rooster '. $rooster->naam .' niet '. ($rooster->id == 0 ? 'toevoegen' : 'wijzigen'), 'error');
	}
} elseif(isset($_POST['delete'])) {
	$text[] = "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
	$text[] = "<input type='hidden' name='id' value='$id'>";
	$text[] = "<table>";
	$text[] = "<tr>";
	$text[] = "	<td colspan='2'>Weet je zeker dat je het rooster <i>". $rooster->naam ."</i> wilt verwijderen?<br>De bijbehorende groepen blijven wel bestaan.</td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td align='left'><input type='submit' name='real_delete' value='Ja'></td>";
	$text[] = "	<td align='right'><input type='submit' name='foutje' value='Nee'></td>";
	$text[] = "</tr>";
	$text[] = "</table>";
	$text[] = "</form>";
} elseif(isset($_POST['real_delete'])) {		
	if($rooster->delete()) {
		$text[] = "Rooster <i>". $rooster->naam ."</i> verwijderd<br>";
		toLog('Rooster '. $rooster->naam .' verwijderd');
	} else {
		$text[] = "Probleem met verwijderen rooster <i>". $rooster->naam ."</i><br>";
		toLog('Kon roosterData '. $rooster->naam .' niet verwijderen', 'error');
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
	$text[] = "	<td><input type='checkbox' name='text_only' value='1'". ($rooster->tekst ? ' checked' : '') ."></td>";
	$text[] = "	<td>Dit rooster bevat enkel vrije tekst</td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td>Naam</td>";
	$text[] = "	<td><input type='text' name='naam' value='". $rooster->naam ."'></td>";
	$text[] = "</tr>";	
	$text[] = "<tr>";
	$text[] = "	<td>Beheerder</td>";	
	$text[] = "	<td><select name='beheerder'>";
	
	$teams = Team::getAllTeams();	
	foreach($teams as $id) {
		$team = new Team($id);
		$text[] = "	<option value='$id'". ($id == $rooster->beheerder ? ' selected' : '') .">". $team->name ."</option>";
	}
	$text[] = "	</select></td>";
	$text[] = "</tr>";
	
	if(!$rooster->tekst) {
		$text[] = "<tr>";
		$text[] = "	<td>Te plannen groep</td>";	
		$text[] = "	<td><select name='groep'>";
		foreach($teams as $id) {
			$team = new Team($id);
			$text[] = "	<option value='$id'". ($id == $rooster->groep ? ' selected' : '') .">". $team->name ."</option>";
		}
		$text[] = "	</select></td>";
		$text[] = "</tr>";			
		$text[] = "<tr>";
		$text[] = "	<td>Planner</td>";
		$text[] = "	<td><select name='planner'>";
		foreach($teams as $id) {
			$team = new Team($id);
			$text[] = "	<option value='$id'". ($id == $rooster->planner ? ' selected' : '') .">". $team->name ."</option>";
		}
		$text[] = "	</select></td>";		
		$text[] = "</tr>";		
		$text[] = "<tr>";
		$text[] = "	<td>Aantal personen</td>";
		$text[] = "	<td><select name='aantal'>";		
		for($a=1 ; $a<=10 ; $a++)	{	$text[] = "<option value='$a'". ($a == $rooster->velden ? ' selected' : '') .">$a</option>";	}	
		$text[] = "	</select></td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td>Remindermails</td>";
		$text[] = "	<td><select name='reminder'>";		
		$text[] = "<option value='1'". ($rooster->reminder ? ' selected' : '') .">Ja</option>";
		$text[] = "<option value='0'". ($rooster->reminder ? '' : ' selected') .">Nee</option>";
		$text[] = "	</select></td>";
		$text[] = "</tr>";
	}
	
	$text[] = "<tr>";
	$text[] = "	<td>Leeg-rooster-alert</td>";
	$text[] = "	<td><select name='alert'>";		
	$text[] = "<option value='0'". ($rooster->alert == 0 ? ' selected' : '') .">Uit</option>";
	$text[] = "<option value='1'". ($rooster->alert == 1 ? ' selected' : '') .">1 week</option>";
	$text[] = "<option value='2'". ($rooster->alert == 2 ? ' selected' : '') .">2 weken</option>";
	$text[] = "<option value='3'". ($rooster->alert == 3 ? ' selected' : '') .">3 weken</option>";
	$text[] = "<option value='4'". ($rooster->alert == 4 ? ' selected' : '') .">4 weken</option>";
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
	$roosters = Rooster::getAllRoosters();
	
	$text[] = "<a href='?new'>Nieuw rooster toevoegen</a>";
	$text[] = "<p>";
	
	foreach($roosters as $id) {
		$rooster = new Rooster($id);
		$text[] = "<a href='?id=$id'>". $rooster->naam ."</a><br>";
	}	
}

echo showCSSHeader();
echo '<div class="content_vert_kolom">'.NL;
echo "<div class='content_block'>".NL. implode(NL, $text).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom\' -->'.NL;
echo showCSSFooter();

?>