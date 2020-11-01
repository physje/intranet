<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/EB_functions.php');
include_once('../include/HTML_TopBottom.php');
$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

$sql = "SELECT * FROM $TableEBoekhouden ORDER BY $EBoekhoudenNaam";
$result = mysqli_query($db, $sql);
$row = mysqli_fetch_array($result);

$page[] = '<table>'; 
do {
	$page[] = '<tr>';
	$page[] = '	<td>'. $row[$EBoekhoudenCode] .'</td>';
	$page[] = '	<td>'. $row[$EBoekhoudenNaam] .'</td>';
		
	$sql_2 = "SELECT $UserID FROM $TableUsers WHERE $UserEBRelatie = ". $row[$EBoekhoudenCode];
	$result_2 = mysqli_query($db, $sql_2);
	if(mysqli_num_rows($result_2) > 0) {
		$row_2 = mysqli_fetch_array($result_2);
		$page[] = '	<td>'.makeName($row_2[$UserID], 5) .'</td>';
	} else {
		$page[] = '	<td>&nbsp;</td>';
	}
	$page[] = '</tr>';		
} while($row = mysqli_fetch_array($result));

$page[] = '</table>'; 

# Pagina tonen
echo $HTMLHeader;
echo '<table border=0 width=100%>'.NL;
echo '<tr>'.NL;
echo '	<td valign="top" width="25%">&nbsp;</td>'.NL;
echo '	<td valign="top">'. showBlock(implode("\n", $page), 100). '</td>'.NL;
echo '	<td valign="top" width="25%">&nbsp;</td>'.NL;
echo '</tr>'.NL;
echo '</table>'.NL;
echo $HTMLFooter;