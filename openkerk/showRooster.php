<?php
include_once('config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');

$db = connect_db();
$cfgProgDir = '../auth/';
$requiredUserGroups = array(1, 43);
include($cfgProgDir. "secure.php");

$text[] = "<table>";

$sql		= "SELECT * FROM $TableOpenKerkRooster WHERE $OKRoosterTijd > ". time() ." GROUP BY $OKRoosterTijd ORDER BY $OKRoosterTijd ASC";
$result	= mysqli_query($db, $sql);
$row = mysqli_fetch_array($result);

do {
	$datum = $row[$OKRoosterTijd];
	$eindTijd = $datum + (60*60);

	$text[] = "<tr>";
	$text[] = "		<td valign='top'>".time2str("%a %d %b %H:%M", $datum) .'-'. time2str("%H:%M", $eindTijd) ."</td>";
	$text[] = "		<td valign='top'>";
	
	$sql_datum		= "SELECT * FROM $TableOpenKerkRooster WHERE $OKRoosterTijd = ". $datum;
	$result_datum	= mysqli_query($db, $sql_datum);
	$row_datum = mysqli_fetch_array($result_datum);
	
	do {
		$key = $row_datum[$OKRoosterPersoon];
				
		if(is_numeric($key)) {
			$text[] = "<a href='profiel.php?id=$key'>". makeName($key, 5) ."</a><br>";
		} else {
			$text[] = $extern[$key]['naam']."<br>";
		}		
	} while($row_datum = mysqli_fetch_array($result_datum));
	
	$text[] = "</td>";

	$sql_opmerking = "SELECT * FROM $TableOpenKerkOpmerking WHERE $OKOpmerkingTijd = ". $datum;
	$result_opmerking	= mysqli_query($db, $sql_opmerking);
	if($row_opmerking = mysqli_fetch_array($result_opmerking)) {
		$text[] = "<td valign='top'><i>". $row_opmerking[$OKOpmerkingOpmerking] ."</i></td>";
	} else {
		$text[] = "<td>&nbsp;</td>";
	}
	
	$text[] = "</tr>";
	
} while($row = mysqli_fetch_array($result));

$text[] = "</table>";

echo $HTMLHeader;
echo implode("\n", $text);
echo $HTMLFooter;

?>