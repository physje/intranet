<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Wijk.php');
include_once('../Classes/Team.php');
include_once('../Classes/Member.php');
include_once('../Classes/Voorganger.php');

$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

if(isset($_POST['opslaan'])) {
	$wijk = new Wijk();
	$wijk->wijk	= $_REQUEST['wijk'];
	foreach($_POST['lid'] as $key => $lid) {		
		$values[$lid] = $_POST['rol'][$key];
	}
	$wijk->wijkteam = $values;
	if($wijk->save()) {
		toLog('Wijkteam wijk '. $wijk->wijk .' aangepast');
	} else {
		toLog('Kon wijkteam wijk '. $wijk->wijk .' niet aanpassen', 'error');
	}
}

if(isset($_REQUEST['wijk'])) {
	$wijk = new Wijk();
	$wijk->wijk	= $_REQUEST['wijk'];
	$wijk->type = 2;
	$wijkLeden	= $wijk->getWijkleden();
	$wijkTeam	= $wijk->getWijkteam();

	$ouderlingen	= new Team(8);	
	$diakenen		= new Team(9);
	$predikanten	= new Team(34);
	
	$aantal = count($wijkTeam);
	
	$text[] = "<form method='post'>";
	$text[] = "<input type='hidden' name='wijk' value='". $wijk->wijk ."'>";
	$text[] = "<table>";
	$text[] = "<tr>";
	$text[] = "	<td colspan='2'><h1>Wijkteam wijk ". $wijk->wijk ."</h1></td>";
	$text[] = "</tr>";
	
	if($wijkTeam > 0) {
		$text[] = "<tr>";
		$text[] = "	<td colspan='2'>Dit zijn de mensen die nu in het wijkteam zitten.<br>Door het vinkje voor de naam weg te halen<br>verdwijnt de persoon uit het wijkteam.</td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td colspan='2'>&nbsp;</td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td><b>Naam</b></td>";
		$text[] = "	<td><b>Rol</b></td>";
		$text[] = "</tr>";
		
		for($i =0 ; $i < $aantal ; $i++) {
			$lid = key($wijkTeam);
			$rol = current($wijkTeam);

			$wijkteamLid = new Member($lid);
		
			$text[] = "<tr>";
			$text[] = "	<td><input type='checkbox' name='lid[$i]' value='". $wijkteamLid->id ."' checked>". $wijkteamLid->getName() ."</td>";
			$text[] = "	<td>". $teamRollen[$rol] ."</td>";
			$text[] = "</tr>";
			$text[] = "<input type='hidden' name='rol[$i]' value='$rol'>";
			next($wijkTeam);
		}		
		
		$text[] = "<tr>";
		$text[] = "	<td colspan='2'>&nbsp;</td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td colspan='2'>Selecteer naam en rol om de persoon aan het wijkteam toe te voegen.</td>";
		$text[] = "</tr>";
	}
	
	$i++;
	$text[] = "<tr>";
	$text[] = "	<td><select name='lid[$i]'>";	
	$text[] = "<option value=''></option>";
	$text[] = "<optgroup label='Predikanten'>";
	foreach($predikanten->leden as $id) {
		$predikant = new Member($id);
		$text[] = "		<option value='". $predikant->id ."'>". $predikant->getName() ."</option>";
	}
	$text[] = "	</optgroup>";
	$text[] = "<optgroup label='Ouderlingen'>";
	foreach($ouderlingen->leden as $id) {
		$ouderling = new Member($id);
		$text[] = "		<option value='". $ouderling->id ."'>". $ouderling->getName() ."</option>";
	}

	$text[] = "	</optgroup>";
	$text[] = "	<optgroup label='Diakenen'>";
	foreach($diakenen->leden as $id) {
		$diaken = new Member(($id));
		$text[] = "		<option value='". $diaken->id ."'>". $diaken->getName() ."</option>";
	}
	$text[] = "	</optgroup>";
	$text[] = "	<optgroup label='Wijkleden'>";	
	foreach($wijkLeden as $id) {
		$wijklid = new Member($id);
		$text[] = "		<option value='". $wijklid->id ."'>". $wijklid->getName() ."</option>";
	}		
	$text[] = "	</optgroup>";	
	$text[] = "	</select></td>";
	$text[] = "	<td><select name='rol[$i]'>";	
	$text[] = "<option valu=''></option>";
	foreach($teamRollen as $id => $rol) {
		$text[] = "<option value='$id'>$rol</option>";		
	}	
	$text[] = "	</select></td>";
	$text[] = "</tr>";
		
	#$text[] = "<tr>";
	#$text[] = "	<td colspan='2'>&nbsp;</td>";
	#$text[] = "</tr>";		
	#$text[] = "<tr>";
	#$text[] = "	<td colspan='2'><input type='submit' name='opslaan' value='Opslaan'></td>";
	#$text[] = "</tr>";	
	$text[] = "</table>";
	$text[] = "<p class='after_table'><input type='submit' name='opslaan' value='Opslaan'></p>";	
	$text[] = "</form>";
} else {
	$text[] = 'Selecteer de wijk die u wilt aanpassen  :<br>';
	foreach($wijkArray as $wijk) {
		$text[] = "<a href='?wijk=$wijk'>Wijk $wijk</a><br>";
	}
}

echo showCSSHeader();
echo '<div class="content_vert_kolom">'.NL;
echo "<div class='content_block'>".NL. implode(NL, $text).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom\' -->'.NL;
echo showCSSFooter();

?>
