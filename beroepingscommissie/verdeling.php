<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/config_mails.php');
include_once('include/HTML_TopBottom.php');
include_once('include/HTML_HeaderFooter.php');

$db = connect_db();
$cfgProgDir = 'auth/';
$requiredUserGroups = array(1, 22);
include($cfgProgDir. "secure.php");

$sql_ja = "SELECT * FROM `votingcodes` WHERE `keuze` = '1'";
$aantal_ja = mysqli_num_rows(mysqli_query($db, $sql_ja));

$sql_nee = "SELECT * FROM `votingcodes` WHERE `keuze` = '1'";
$aantal_nee = mysqli_num_rows(mysqli_query($db, $sql_nee));

$totaal = $aantal_ja + $aantal_nee;
$perc_ja = ($aantal_ja/$totaal)*100;
$perc_nee = ($aantal_nee/$totaal)*100;

$text[] = "<table width='100%'>";
$text[] = "<tr>";
$text[] = "	<td width='50'>Ja</td>";
$text[] = "	<td><table width='100%'><tr><td width='". ($perc_ja) ."%'>&nbsp;</td><td width='". (100-$perc_ja) ."%'>&nbsp;</td></tr></table></td>";
$text[] = "</tr>";
$text[] = "<tr>";
$text[] = "	<td>Nee</td>";
$text[] = "	<td><table width='100%'><tr><td width='". ($perc_nee) ."%'>&nbsp;</td><td width='". (100-$perc_nee) ."%'>&nbsp;</td></tr></table></td>";
$text[] = "</tr>";
$text[] = "</table>";

echo $HTMLHeader;
echo implode("\n", $text);
echo $HTMLFooter;