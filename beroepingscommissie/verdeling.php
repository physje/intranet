<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');

$db = connect_db();
$cfgProgDir = '../auth/';
$requiredUserGroups = array(1, 48);
include($cfgProgDir. "secure.php");

$sql_ja = "SELECT * FROM `votingcodes` WHERE `keuze` = '1'";
$aantal_ja = mysqli_num_rows(mysqli_query($db, $sql_ja));

$sql_nee = "SELECT * FROM `votingcodes` WHERE `keuze` = '0'";
$aantal_nee = mysqli_num_rows(mysqli_query($db, $sql_nee));

$sql_blanco = "SELECT * FROM `votingcodes` WHERE `keuze` = '2'";
$aantal_blanco = mysqli_num_rows(mysqli_query($db, $sql_blanco));

$totaal = $aantal_ja + $aantal_nee + $aantal_blanco;
$perc_ja = ($aantal_ja/$totaal)*100;
$perc_nee = ($aantal_nee/$totaal)*100;
$perc_blanco = ($aantal_blanco/$totaal)*100;

$text[] = "<table width='100%'>";
$text[] = "<tr>";
$text[] = "	<td width='40'>Ja</td>";
$text[] = "	<td width='25'>$aantal_ja</td>";
$text[] = "	<td><table width='100%' border='0'><tr><td width='". ($perc_ja) ."%' bgcolor='#8C1974'>&nbsp;</td><td width='". (100-$perc_ja) ."%'>&nbsp;</td></tr></table></td>";
$text[] = "</tr>";
$text[] = "<tr>";
$text[] = "	<td>Nee</td>";
$text[] = "	<td>$aantal_nee</td>";
$text[] = "	<td><table width='100%' border='0'><tr><td width='". ($perc_nee) ."%' bgcolor='#8C1974'>&nbsp;</td><td width='". (100-$perc_nee) ."%'>&nbsp;</td></tr></table></td>";
$text[] = "</tr>";
$text[] = "<tr>";
$text[] = "	<td>Nee</td>";
$text[] = "	<td>$aantal_blanco</td>";
$text[] = "	<td><table width='100%' border='0'><tr><td width='". ($perc_blanco) ."%' bgcolor='#8C1974'>&nbsp;</td><td width='". (100-$perc_blanco) ."%'>&nbsp;</td></tr></table></td>";
$text[] = "</tr>";

$text[] = "</table>";

echo $HTMLHeader;
echo implode("\n", $text);
echo $HTMLFooter;

?>