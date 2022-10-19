<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('shared.php');

$db = connect_db();
$cfgProgDir = '../auth/';
$requiredUserGroups = array(1, 48);
include($cfgProgDir. "secure.php");

foreach($opties as $id => $dummy) {
	$sql = "SELECT * FROM `votingcodes` WHERE `keuze` = '$id'";
	$aantal[$id] = mysqli_num_rows(mysqli_query($db, $sql));
}

$totaal = array_sum($aantal);
$max = max($aantal);

foreach($opties as $id => $dummy) {
	if($totaal > 0) {
		$perc[$id] = ($aantal[$id]/$totaal)*100;		
	} else {
		$perc[$id] = 0;
	}
	
	if($relatief) {
		$width[$id] = ($aantal[$id]/$max)*100;
	} else {
		$width[$id] = $perc[$id];
	}	
}

$text[] = "<table width='100%' border='0'>";

foreach($opties as $id => $naam) {
	$text[] = "<tr>";	
	$text[] = "	<td width='25' align='right'>". number_format($perc[$id], 1) ."%</td>";	
	$text[] = "	<td width='5'rowspan='2'>&nbsp;</td>";
	$text[] = "	<td>$naam</td>";
	$text[] = "</tr>";
	$text[] = "<tr>";	
	$text[] = "	<td align='right'>". $aantal[$id] ."</td>";
	$text[] = "	<td>";
	$text[] = "	<table width='100%' border='0'>";
	$text[] = "	<tr>";
	if($perc[$id] == 0) {
		$text[] = "		<td colspan='2'>&nbsp;</td>";
	} else {
		$text[] = "		<td width='". number_format($width[$id], 2) ."%' bgcolor='#8C1974' title='". number_format($perc[$id], 1) ."% | ". $aantal[$id] ." stemmen'>&nbsp;</td>";
		$text[] = "		<td width='". number_format((100-$width[$id]), 2) ."%'>&nbsp;</td>";
	}
	$text[] = "	</tr>";
	$text[] = "	</table>";
	$text[] = "	</td>";
	$text[] = "</tr>";
	
	$text[] = "<tr>";
	$text[] = "	<td colspan='3'>&nbsp;</td>";
	$text[] = "</tr>";
}

$sql_all = "SELECT * FROM `votingcodes`";
$max = mysqli_num_rows(mysqli_query($db, $sql_all));

$text[] = "<tr>";
$text[] = "	<td colspan='2'>Opkomst</td>";
$text[] = "	<td>$totaal van $max (". number_format(($totaal/$max)*100, 1) ."%)</td>";
$text[] = "</tr>";


$text[] = "</table>";


echo $HTMLHeader;
echo implode(NL, $text);
echo $HTMLFooter;

?>