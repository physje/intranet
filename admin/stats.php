<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

$group = getParam('group', 'k');

if($group == 'k') {
	$sortArray = array('belijdend lid', 'dooplid', 'betrokkene');
} elseif($group == 'b') {
	$sortArray = $burgelijkArray;
} elseif($group == 'g') {
	$sortArray = array('M', 'V');
}
	
$width = 80/count($sortArray);
$superTotal = 0;

$tree[] = "<table border=1>";
$tree[] = "<tr>";
$tree[] = "<td width='10%'>Wijk</td>";

foreach($sortArray as $status) {
	$tree[] = "<td width='$width%' align='middle'>$status</td>";
	$subtotal[$status] = 0;
}
$tree[] = "<td width='10%' align='right'>Totaal</td>";
$tree[] = "</tr>";

foreach($wijkArray as $wijk) {
	$total = 0;
	$tree[] = "<tr>";
	$tree[] = "	<td><a href='../ledenlijst.php?wijk=$wijk'>".$wijk .'</a></td>';
	
	foreach($sortArray as $status) {
		$sql_all = "SELECT count(*) FROM $TableUsers WHERE $UserWijk like '$wijk' AND $UserStatus like 'actief' AND ";
		
		if($group == 'k') {
			$sql_all .= "$UserBelijdenis like '$status'";
		} elseif($group == 'b') {
			$sql_all .= "$UserBurgelijk like '$status'";
		} elseif($group == 'g') {
			$sql_all .= "$UserGeslacht like '$status'";			
		}
		
		$result_all = mysqli_query($db, $sql_all);
		$row_all	= mysqli_fetch_array($result_all);
		
		$total = $total+$row_all[0];
		$subtotal[$status] = $subtotal[$status]+$row_all[0];
		
		$tree[] = "	<td align='middle'>".$row_all[0] ."</td>";
	}
	$tree[] = "	<td align='right'><b>$total</b></td>";
	$tree[] = "</tr>";
	$superTotal = $superTotal+$total;
}
$tree[] = "<tr>";
$tree[] = "<td>&nbsp;</td>";

foreach($sortArray as $status) {
	$tree[] = "<td align='middle'><b>".$subtotal[$status]."</b></td>";
}
$tree[] = "<td align='right'><b>". $superTotal ."</b></td>";
$tree[] = "</tr>";
$tree[] = "</table>";	

if($group != 'k') {
	$sort[] = "Groepeer op <a href='?group=k'>lidsoort</a>";
}

if($group != 'b') {
	$sort[] = "Groepeer op <a href='?group=b'>relatie</a>";
}

if($group != 'g') {
	$sort[] = "Groepeer op <a href='?group=g'>geslacht</a>";
}




echo showCSSHeader(array('default', 'table_default'));
echo '<div class="content_vert_kolom_full">'.NL;
echo '<h1>Statistieken</h1>'.NL;
echo "<div class='content_block'>". implode(NL, $tree) ."</div>".NL;
echo "<div class='content_block'>". implode(" | ", $sort) ."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

?>