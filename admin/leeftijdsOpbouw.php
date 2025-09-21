<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

$geslacht = array('M', 'V');
$jongens = $meisjes = $max = 0;

$group = getParam('group', 'l');

# Groepeer op leeftijd
if($group == 'l') {
	$l_min = 0;
	$l_max = 100;
	$l_stap = 1;
# of op geboortejaar
} else {
	$l_min = date('Y')-100;
	$l_max = date('Y');
	$l_stap = 1;
}

for($l = $l_min ; $l <= $l_max ; $l=$l + $l_stap) {
	if($group == 'l') {
		$start = mktime(0,0,0,date("n"), date("j")+1, (date("Y")-$l-1));
		$eind = mktime(23,59,59,date("n"), date("j"), (date("Y")-$l));
	} else {
		$start = mktime(0,0,0,1,1,$l);
		$eind = mktime(23,59,59,12,31,$l);
	}		
	
	$sql_all = "SELECT count(*) FROM $TableUsers WHERE $UserGeboorte BETWEEN '". date("Y-m-d", $start) ."' AND '". date("Y-m-d", $eind) ."' AND $UserStatus like 'actief'";
		
	foreach($geslacht as $g) {
		$sql_g = $sql_all . " AND $UserGeslacht like '$g'";

		$result_all = mysqli_query($db, $sql_g);
		$row_all	= mysqli_fetch_array($result_all);
		
		$data[$l][$g] = $row_all[0];
		
		if($row_all[0] > $max)	$max = $row_all[0];
	}	
}

if($group == 'l')	$data = array_reverse($data, true);

$tree[] =  "<table width='100%' border=0>";
foreach($data as $leeftijd => $lData) {
	$widthM			= ($lData['M']/$max)*100;
	$widthMNot	= 100-$widthM;
	$widthV			= ($lData['V']/$max)*100;
	$widthVNot	= 100-$widthV;
	$jongens		= $jongens+$lData['M'];
	$meisjes		= $meisjes+$lData['V'];
	
	$tree[] = "<!-- $sql_all -->";
	$tree[] = "<!-- $leeftijd : $jongens | $meisjes -->";
	$tree[] = "<tr>";
	$tree[] = "	<td width='2%'>$leeftijd</td>";
	$tree[] = "	<td width='48%'>";
	
	# Mannen
	if($lData['M'] > 0) {
		$tree[] = "	<table width='100%'>";
		$tree[] = "	<tr>";
		$tree[] = "		<td width='$widthMNot%'>&nbsp;</td>";
		$tree[] = "		<td width='$widthM%' align='left' bgcolor='lightblue'>". $lData['M'] ."</td>";			
		$tree[] = "	<tr>";
		$tree[] = "	</table>";	
	} else {
		$tree[] = "	&nbsp;";	
	}
	$tree[] = "</td>";
	
	# Totalen
	$tree[] = "	<td width='2%' align='center'>". ($lData['M']+$lData['V']) ."</td>";	
	$tree[] = "	<td width='48%'>";
	
	if($lData['V'] > 0) {
		$tree[] = "	<table width='100%'>";
		$tree[] = "	<tr>";
		$tree[] = "		<td width='$widthV%' align='right' bgcolor='pink'>". $lData['V'] ."</td>";
		$tree[] = "		<td width='$widthVNot%'>&nbsp;</td>";
		$tree[] = "	<tr>";
		$tree[] = "	</table>";	
	} else {
		$tree[] = "	&nbsp;";	
	}	
	$tree[] = "</td>";
		
	$tree[] = "</tr>";
}
$tree[] = "</table>";

$legend[] = "<table width='100%'>";
$legend[] = "<tr>";
$legend[] = "	<td width='2%'>&nbsp;</td>";
$legend[] = "	<td width='48%' align='right'>$jongens</td>";
$legend[] = "	<td width='2%'>&nbsp;</td>";
$legend[] = "	<td width='48%'>$meisjes</td>";
$legend[] = "</tr>";
$legend[] = "</table>";

$sort[] = "Groepeer op ". ($group == 'l' ? "<a href='?group=j'>geboortjaar</a>" : "<a href='?group=l'>leeftijd</a>");

echo showCSSHeader();
echo '<div class="content_vert_kolom_full">'.NL;
echo '<h1>Leeftijdsopbouw</h1>'.NL;
echo "<div class='content_block'>". implode(NL, $tree) ."</div>".NL;
echo "<div class='content_block'>". implode(NL, $legend) ."</div>".NL;
echo "<div class='content_block'>". implode(NL, $sort) ."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

?>