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
$blokGrootte = (31*24*60*60);

if(isset($_POST['save'])) {
	# Doorloop alle tijden
	# Verwijder de huidige
	# En voer de nieuwe in
	# item[$datum][$slotID][$positie]
	foreach($_POST['item'] as $datum => $sub) {
		foreach($sub as $slotID => $sub2) {
			$start	= mktime($uren[$slotID][0], $uren[$slotID][1], 0, date('n', $datum), date('j', $datum), date('Y', $datum));
			$eind		= mktime($uren[$slotID][2], $uren[$slotID][3], 0, date('n', $datum), date('j', $datum), date('Y', $datum));
			foreach($sub2 as $pos => $persoon) {							
				$sql_delete = "DELETE FROM $TableOpenKerkRooster WHERE $OKRoosterStart = $start AND $OKRoosterPos = $pos";
				if(!mysqli_query($db, $sql_delete)) {
				    echo $sql_delete .'<br>';
				}
				
				if($persoon != '') {					
					$sql_insert = "INSERT INTO $TableOpenKerkRooster ($OKRoosterStart, $OKRoosterEind, $OKRoosterPos, $OKRoosterPersoon) VALUES ('$start', '$eind', '$pos', '$persoon')";
					if(!mysqli_query($db, $sql_insert)) {
					    echo $sql_insert .'<br>';
					}
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
			$sql = "INSERT INTO $TableOpenKerkOpmerking ($OKOpmerkingOpmerking, $OKOpmerkingTijd) VALUES ('". urlencode($opmerking) ."', $datum)";
		# Als die al wel bestaat en de nieuwe is niet leeg -> update
		} elseif(trim($opmerking) != '') {
			$sql = "UPDATE $TableOpenKerkOpmerking SET $OKOpmerkingOpmerking = '". urlencode($opmerking) ."' WHERE $OKOpmerkingTijd = $datum";
		# Als de nieuwe is leeg -> verwijder
		} elseif(trim($opmerking) == '') {
			$sql = "DELETE FROM $TableOpenKerkOpmerking WHERE $OKOpmerkingTijd = $datum";
		}
		
		mysqli_query($db, $sql);
		
	}
		
	$text[] = '<i>Wijzigingen in het rooster zijn opgeslagen</i>';
}

if(isset($_POST['start'])) {
	$start = $_POST['start'];
} else {
	$start = time();
}

if(isset($_POST['next_week'])) {
	$start = ($start + $blokGrootte);
}

$einde = $start + $blokGrootte;

#echo "start : ". date("d-m-Y", $start)."<br>";
#echo "einde : ". date("d-m-Y", $einde)."<br>";

# Een keer alle namen ophalen en in een array zetten zodat dit later hergebruikt kan worden
foreach($namen as $key => $value) {
	if(is_array($value)) {
		$namenArray[$key] = $value['naam'];
	} else {
		$namenArray[$value] = makeName($value, 5);
	}
}

$sql		= "SELECT * FROM $TableOpenKerkRooster WHERE $OKRoosterStart BETWEEN ". $start ." AND ". $einde ." ORDER BY $OKRoosterStart DESC LIMIT 0,1";
$result = mysqli_query($db, $sql);
$row		= mysqli_fetch_array($result);
$lastDag	= $row[$OKRoosterStart];

#echo "lastDag : ". date("d-m-Y", $lastDag)."<br>";

$text[] = "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
$text[] = "<input type='hidden' name='start' value='$start'>";
$text[] = "<table>";
$text[] = "<tr>";
$text[] = "		<td colspan='2'>&nbsp;</td>";
$text[] = "		<td>Opmerkingen</td>";
$text[] = "</tr>";

$dag = 0;
$datum = $start;

while($datum < $lastDag) {
	foreach($uren as $slotID => $slot) {
		
		$datum		= mktime(0, 0, 0, date('n', $start), (date('j', $start)+$dag), date('Y', $start));
		$tijdstip	= mktime($slot[0], $slot[1], 0, date('n', $start), (date('j', $start)+$dag), date('Y', $start));
		$eind			= mktime($slot[2], $slot[3], 0, date('n', $start), (date('j', $start)+$dag), date('Y', $start));
		$weekdag	= date('w', $tijdstip);
		
		#echo "datum : ". date("d-m-Y", $datum)."<br>";
		
		if(($minDag <= $weekdag) AND ($weekdag <= $maxDag)) {
			$text[] = "<tr>";
			$text[] = "		<td valign='top'>".time2str("%a %d %b %Y %H:%M", $tijdstip)." - ".time2str("%H:%M", $eind)."</td>";
			$text[] = "		<td valign='top'>";
					
			for($positie=0; $positie < $aantal ; $positie++) {
				$text[] = "<select name='item[$datum][$slotID][$positie]'>";
				$text[] = "<option value=''></option>";
				
				$sql_vulling		= "SELECT * FROM $TableOpenKerkRooster WHERE $OKRoosterStart = ". $tijdstip ." AND $OKRoosterPos = ". $positie;
				
				#echo date("d-m-Y", $tijdstip) .' - '. $sql_vulling ."<br>\n";
				
				$result_vulling = mysqli_query($db, $sql_vulling);
				$row_vulling		= mysqli_fetch_array($result_vulling);
				
				foreach($namenArray as $id => $naam) {
					$text[] = "<option value='$id'". ((isset($row_vulling[$OKRoosterPersoon]) AND $row_vulling[$OKRoosterPersoon] == $id) ? ' selected' : '') .">". $naam ."</option>";												
				}	
								
				$text[] = "		</select>&nbsp;";
			}	
			
			$sql_opmerking = "SELECT * FROM $TableOpenKerkOpmerking WHERE $OKOpmerkingTijd = $tijdstip";
			$result_opmerking = mysqli_query($db, $sql_opmerking);
			$row_opmerking = mysqli_fetch_array($result_opmerking);
						
			$text[] = "</td>";
			$text[] = "<td><input type='text' name='opmerking[$tijdstip]' value='". (isset($row_opmerking[$OKOpmerkingOpmerking]) ? urldecode($row_opmerking[$OKOpmerkingOpmerking]) : '') ."'></td>";
			$text[] = "</tr>";
		}				
	}
	$dag++;
}

$text[] = "	<tr>";
$text[] = "		<td colspan='3'>&nbsp;</td>";
$text[] = "	</tr>";
$text[] = "	<tr>";
$text[] = "		<td><input type='submit' name='save' value='Opslaan'></td>";
$text[] = "		<td>&nbsp;</td>";
$text[] = "		<td><input type='submit' name='next_week' value='Toon volgende periode'></td>";
$text[] = "	</tr>";
$text[] = "</table>";
$text[] = "</form>";

echo $HTMLHeader;
echo implode("\n", $text);
echo $HTMLFooter;

?>