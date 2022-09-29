<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');

$db = connect_db();
#$cfgProgDir = '../auth/';
#include($cfgProgDir. "secure.php");

if(isset($_POST['save'])) {
	$sql_update = "UPDATE $TablePastorConvert SET $PastorConvertScipioID = ". $_POST['scipio'] ." WHERE $PastorConvertFamID = ". $_POST['fam_id'];
	mysqli_query($db, $sql_update);	
}

if(isset($_POST['skip'])) {
	$sql_skip = "UPDATE $TablePastorConvert SET $PastorConvertScipioID = 1 WHERE $PastorConvertFamID = ". $_POST['fam_id'];
	mysqli_query($db, $sql_skip);	
}

$sql = "SELECT * FROM $TablePastorConvert WHERE $PastorConvertScipioID = '' LIMIT 0,1";
$result = mysqli_query($db, $sql);
if($row = mysqli_fetch_array($result)) {
	$wijk = $row[$PastorConvertWijk];
	
	$wijkLeden = getWijkledenByAdres($wijk, 0);
		
	foreach($wijkLeden as $wijklid) {
		$score = levenshtein(makeName($wijklid[0], 6), $row[$PastorConvertFamName]);
		#echo makeName($wijklid[0], 4) .' : '. $score .'<br>';
		$scores[$wijklid[0]] = $score;		
	}
	
	asort($scores);	
	$key = key($scores);
	
	if($scores[$key] == 0) {
		$sql_update = "UPDATE $TablePastorConvert SET $PastorConvertScipioID = $key WHERE $PastorConvertFamID = ". $row[$PastorConvertFamID];
		mysqli_query($db, $sql_update);
		
		echo '<html>';
		echo '<head>';
		echo '	<meta http-equiv="refresh" content="3; url=" />';
		echo '</head>';
		echo '<body>';		
		echo $row[$PastorConvertFamName] .' ['.$row[$PastorConvertFamID].'] gekoppeld aan '. makeName($key, 6) .' ['. $key .']';		
	} else {
		
		echo '<html>';		
		echo '<body>';	
		echo '<h1>'. $row[$PastorConvertFamName] .'</h1><br>';		
		echo "<form method='post'>";
		echo "<input type='hidden' name='fam_id' value='". $row[$PastorConvertFamID] ."'>";
		#echo "<select name='scipio'>";	
		foreach($wijkLeden as $wijklid) {
			#echo "<option value='". $wijklid[0] ."'". ($wijklid[0] == $key ? ' selected' : '') .">". makeName($wijklid[0], 4)."</option>\n";
			echo "<input type='radio' name='scipio' value='". $wijklid[0] ."'". ($wijklid[0] == $key ? ' checked' : '') ."> ". makeName($wijklid[0], 6)."<br>\n";		
		}	
		#echo "</select> ";
		echo "<input type='submit' value='Koppelen' name='save'>";
		echo "<input type='submit' value='Onbepaald' name='skip'>";
		echo "</form>";
	}
}