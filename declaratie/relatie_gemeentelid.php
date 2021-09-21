<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/EB_functions.php');
include_once('../include/HTML_TopBottom.php');
$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

$relaties = eb_getRelaties();

$page[] = '<table>';

foreach($relaties as $relatieData) {
	$page[] = '<tr>';
	$page[] = '	<td>'. $relatieData['code'] .'</td>';
	$page[] = '	<td>'. $relatieData['naam'] .'</td>';
		
	$sql_2 = "SELECT $UserID FROM $TableUsers WHERE $UserEBRelatie = ". $relatieData['code'];
	$result_2 = mysqli_query($db, $sql_2);
	if(mysqli_num_rows($result_2) > 0) {
		$row_2 = mysqli_fetch_array($result_2);
		$page[] = '	<td>'.makeName($row_2[$UserID], 5) .'</td>';
	} else {
		$page[] = '	<td>&nbsp;</td>';
	}
	$page[] = '</tr>';		
}

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