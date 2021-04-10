<?php
include_once('config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');

$db = connect_db();
$cfgProgDir = '../auth/';
$requiredUserGroups = array(1, 43);
include($cfgProgDir. "secure.php");

$leden = getGroupMembers(43);
$namen = array_merge($leden, $extern);

if(isset($_POST['save'])) {
	# Doorloop alle tijden
	# Verwijder de huidige
	# En voer de nieuwe in
	foreach($_POST['item'] as $datum => $sub) {
		foreach($sub as $uur => $sub2) {
			foreach($sub2 as $pos => $persoon) {
				$sql_delete = "DELETE FROM $TableOpenKerkRooster WHERE $OKRoosterTijd = $datum AND $OKRoosterPos = $pos";
				mysqli_query($db, $sql_delete);
				
				if($persoon != '') {
					$sql_insert = "INSERT INTO $TableOpenKerkRooster ($OKRoosterTijd, $OKRoosterPos, $OKRoosterPersoon) VALUES ('$datum', '$pos', '$persoon')";
					mysqli_query($db, $sql_insert);
				}
			}
		}
	}
	
	# Doorloop alle opmerkingen	
	foreach($_POST['opmerking'] as $datum => $opmerking) {		
		# Check of er bij die datum al een opmerking in de database staat
		$sql_check = "SELECT * FROM $TableOpenKerkOpmerking WHERE $OKOpmerkingTijd = $datum";
		$result = mysqli_query($db, $sql_check);
		
		# Als die nog niet bestaat en de nieuwe is niet leeg -> voeg toe
		if(mysqli_num_rows($result) == 0 AND trim($opmerking) != '') {
			$sql = "INSERT INTO $TableOpenKerkOpmerking ($OKOpmerkingPersoon, $OKOpmerkingTijd) VALUES ('". urlencode($opmerking) ."', $datum)";
		# Als die al wel bestaat en de nieuwe is niet leeg -> update
		} elseif(trim($opmerking) != '') {
			$sql = "UPDATE $TableOpenKerkOpmerking SET $OKOpmerkingPersoon = '". urlencode($opmerking) ."' WHERE $OKOpmerkingTijd = $datum";
		# Als de nieuwe is leeg -> verwijder
		} elseif(trim($opmerking) == '') {
			$sql = "DELETE FROM $TableOpenKerkOpmerking WHERE $OKOpmerkingTijd = $datum";
		}
		
		mysqli_query($db, $sql);
		
	}
		
	$text[] = '<i>Wijzigingen in het rooster zijn opgeslagen</i>';
}

$sql		= "SELECT * FROM $TableOpenKerkRooster WHERE $OKRoosterTijd > ". time() ." ORDER BY $OKRoosterTijd DESC";
$result = mysqli_query($db, $sql);
$row		= mysqli_fetch_array($result);
$lastDag	= $row[$OKRoosterTijd];

$text[] = "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
$text[] = "<table>";
$text[] = "<tr>";
$text[] = "		<td colspan='2'>&nbsp;</td>";
$text[] = "		<td>Opmerkingen</td>";
$text[] = "</tr>";

$dag = 0;

# Een keer alle namen ophalen en in een array zetten zodat dit later hergebruikt kan worden
foreach($namen as $key => $value) {
	if(is_array($value)) {
		$namenArray[$key] = $value['naam'];
	} else {
		$namenArray[$value] = makeName($value, 5);
	}
}


do {
	for($uur=$minUur; $uur < $maxUur ; $uur++) {
		$datum = mktime($uur, 0, 0, date('n'), (date('j')+$dag));
		$weekdag = date('w', $datum);
		
		if(($minDag <= $weekdag) AND ($weekdag <= $maxDag)) {
			$text[] = "<tr>";
			$text[] = "		<td valign='top'>".time2str("%a %d %b %H:%M", $datum)."</td>";
			$text[] = "		<td valign='top'>";
					
			for($positie=0; $positie < $aantal ; $positie++) {
				$text[] = "<select name='item[$datum][$uur][$positie]'>";
				$text[] = "<option value=''></option>";
				
				$sql_vulling		= "SELECT * FROM $TableOpenKerkRooster WHERE $OKRoosterTijd = ". $datum ." AND $OKRoosterPos = ". $positie;
				$result_vulling = mysqli_query($db, $sql_vulling);
				$row_vulling		= mysqli_fetch_array($result_vulling);
				
				foreach($namenArray as $id => $naam) {
					$text[] = "<option value='$id'". ((isset($row_vulling[$OKRoosterPersoon]) AND $row_vulling[$OKRoosterPersoon] == $id) ? ' selected' : '') .">". $naam ."</option>";												
				}	
								
				$text[] = "		</select>&nbsp;";
			}	
			
			$sql_opmerking = "SELECT * FROM $TableOpenKerkOpmerking WHERE $OKOpmerkingTijd = $datum";
			$result_opmerking = mysqli_query($db, $sql_opmerking);
			$row_opmerking = mysqli_fetch_array($result_opmerking);
						
			$text[] = "</td>";
			$text[] = "<td><input type='text' name='opmerking[$datum]' value='". (isset($row_opmerking[$OKOpmerkingPersoon]) ? $row_opmerking[$OKOpmerkingPersoon] : '') ."'></td>";
			$text[] = "</tr>";
		}				
	}
	$dag++;
} while($datum < $lastDag);


$text[] = "	<tr>";
$text[] = "		<td colspan='3'><input type='submit' name='save' value='Opslaan'></td>";
$text[] = "	</tr>";
$text[] = "</table>";
$text[] = "</form>";

echo $HTMLHeader;
echo implode("\n", $text);
echo $HTMLFooter;

?>