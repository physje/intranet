<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');

$db = connect_db();
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

$wijk = 'D';

$wijkLeden = getWijkledenByAdres($wijk);
$vorig_adres = 0;

$text[] = '<table>';

foreach($wijkLeden as $adres => $leden) {
	foreach($leden as $lid) {
		$text[] = '<tr>';
		
		if($adres != $vorig_adres) {
			$text[] = "	<td colspan='2'><b>". makeName($lid, 5) ."</b></td>";
			$vorig_adres = $adres;
		} else {
			$text[] = '	<td>&nbsp;</td>';
			$text[] = '	<td>'. makeName($lid, 1) .'</td>';
		}
		$text[] = '	<td>'. date('d-m-Y') .'</td>';
		$text[] = '	<td>+</td>';
		$text[] = '</tr>';		
	}
}

$text[] = '</table>';

echo $HTMLHeader;
echo implode("\n", $text);
echo $HTMLFooter;

?>