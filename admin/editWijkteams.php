<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include_once($cfgProgDir. "secure.php");
$db = connect_db();

if(isset($_POST['opslaan'])) {
	$wijk = $_REQUEST['wijk'];
	$sql = "DELETE FROM $TableWijkteam WHERE $WijkteamWijk like '$wijk'";
	$result = mysqli_query($db, $sql);
	
	foreach($_POST['lid'] as $id => $lid) {
		if($lid > 0) {
			$rol = $_POST['rol'][$id];
						
			$sql = "INSERT INTO $TableWijkteam ($WijkteamWijk, $WijkteamLid, $WijkteamRol) VALUES ('$wijk', $lid, $rol)";
			$result = mysqli_query($db, $sql);
		}
	}
}

if(isset($_REQUEST['wijk'])) {
	$wijk = $_REQUEST['wijk'];
	$wijkLeden = getWijkMembers($wijk);
	$ouderlingen = getGroupMembers(8);
	$diakenen = getGroupMembers(9);
	$predikanten = getGroupMembers(34);
	$leden = getWijkteamLeden($wijk);
	$aantal = count($leden);
	
	$text[] = "<form method='post'>";
	$text[] = "<input type='hidden' name='wijk' value='$wijk'>";
	$text[] = "<table>";
	$text[] = "<tr>";
	$text[] = "	<td colspan='2'><h1>Wijkteam wijk $wijk</h1></td>";
	$text[] = "</tr>";
	
	if($aantal > 0) {
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
			$lid = key($leden);
			$rol = current($leden);
		
			$text[] = "<tr>";
			$text[] = "	<td><input type='checkbox' name='lid[$i]' value='$lid' checked>". makeName($lid, 5) ."</td>";
			$text[] = "	<td>". $teamRollen[$rol] ."</td>";
			$text[] = "</tr>";
			$text[] = "<input type='hidden' name='rol[$i]' value='$rol'>";
			next($leden);
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
	$text[] = "<option valu=''></option>";
	$text[] = "<optgroup label='Predikanten'>";
	foreach($predikanten as $id)	$text[] = "		<option value='$id'>".makeName($id, 5)."</option>";
	$text[] = "	</optgroup>";
	$text[] = "<optgroup label='Ouderlingen'>";
	foreach($ouderlingen as $id)	$text[] = "		<option value='$id'>".makeName($id, 5)."</option>";
	$text[] = "	</optgroup>";
	$text[] = "	<optgroup label='Diakenen'>";
	foreach($diakenen as $id)			$text[] = "		<option value='$id'>".makeName($id, 5)."</option>";
	$text[] = "	</optgroup>";
	$text[] = "	<optgroup label='Wijkleden'>";	
	foreach($wijkLeden as $id)		$text[] = "		<option value='$id'>".makeName($id, 5)."</option>";
	$text[] = "	</optgroup>";	
	$text[] = "	</select></td>";
	$text[] = "	<td><select name='rol[$i]'>";	
	$text[] = "<option valu=''></option>";
	foreach($teamRollen as $id => $rol) {
		$text[] = "<option value='$id'>$rol</option>";		
	}	
	$text[] = "	</select></td>";
	$text[] = "</tr>";
		
	/*
	for($i =0 ; $i <= $aantal ; $i++) {
		$lidID = key($leden);
		$lidRol = current($leden);
				
		$text[] = "<tr>";
		$text[] = "	<td><select name='lid[$i]'>";	
		$text[] = "<option valu=''></option>";
		foreach($IDs as $id) {
			$text[] = "<option value='$id'".($lidID == $id ? ' selected' : '').">".makeName($id, 5)."</option>";		
		}	
		$text[] = "	</select></td>";
		$text[] = "	<td><select name='rol[$i]'>";	
		$text[] = "<option valu=''></option>";
		foreach($teamRollen as $id => $rol) {
			$text[] = "<option value='$id'".($lidRol == $id ? ' selected' : '').">$rol</option>";		
		}	
		$text[] = "	</select></td>";
		$text[] = "</tr>";
		next($leden);
	}
	*/

	$text[] = "<tr>";
	$text[] = "	<td colspan='2'>&nbsp;</td>";
	$text[] = "</tr>";		
	$text[] = "<tr>";
	$text[] = "	<td colspan='2'><input type='submit' name='opslaan' value='Opslaan'></td>";
	$text[] = "</tr>";	
	$text[] = "</table>";
	$text[] = "</form>";
} else {
	$text[] = 'Selecteer de wijk die u wilt aanpassen  :<br>';
	foreach($wijkArray as $wijk) {
		$text[] = "<a href='?wijk=$wijk'>Wijk $wijk</a><br>";
	}
}

echo $HTMLHeader;
echo implode("\n", $text);
echo $HTMLFooter;


?>
