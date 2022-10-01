<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');

$db = connect_db();
$cfgProgDir = '../auth/';
$requiredUserGroups = array(1, 48);
include($cfgProgDir. "secure.php");

$opties[1] = 'Ja';
$opties[0] = 'Nee';
$opties[2] = 'Blanco';

foreach($opties as $id => $dummy) {
	$sql = "SELECT * FROM `votingcodes` WHERE `keuze` = '$id'";
	$aantal[$id] = mysqli_num_rows(mysqli_query($db, $sql));
}

$totaal = array_sum($aantal);

foreach($opties as $id => $dummy) {
	$perc[$id] = ($aantal[$id]/$totaal)*100;
}

$text[] = "<table width='100%'>";

foreach($opties as $id => $naam) {
	$text[] = "<tr>";
	$text[] = "	<td width='75'>$naam</td>";
	$text[] = "	<td width='25'>". $aantal[$id] ."</td>";
	$text[] = "	<td>";
	$text[] = "	<table width='100%' border='0'>";
	$text[] = "	<tr>";
	$text[] = "		<td width='". ($perc[$id]) ."%' bgcolor='#8C1974'>&nbsp;</td>";
	$text[] = "		<td width='". (100-$perc[$id]) ."%'>&nbsp;</td>";
	$text[] = "	</tr>";
	$text[] = "	</table>";
	$text[] = "	</td>";
	$text[] = "</tr>";
}

$text[] = "<tr>";
$text[] = "	<td colspan='3'>&nbsp;</td>";
$text[] = "</tr>";

$sql_all = "SELECT * FROM `votingcodes`";
$max = mysqli_num_rows(mysqli_query($db, $sql_all));

$text[] = "<tr>";
$text[] = "	<td colspan='2'>Opkomst</td>";
$text[] = "	<td>$totaal van $max (". number_format(($totaal/$max)*100, 1) ."%)</td>";
$text[] = "</tr>";


$text[] = "</table>";


echo $HTMLHeader;
echo implode("\n", $text);
echo $HTMLFooter;

?>