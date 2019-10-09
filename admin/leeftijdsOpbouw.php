<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');
//$cfgProgDir = 'auth/';
//include($cfgProgDir. "secure.php");
$db = connect_db();

$l_min = 0;
$l_max = 100;
$l_stap = 1;

for($l = $l_min ; $l <= $l_max ; $l=$l + $l_stap) {
	$start = mktime(0,0,0,date("n"), date("j")+1, (date("Y")-$l-1));
	$eind = mktime(23,59,59,date("n"), date("j"), (date("Y")-$l));
	
	$sql_all = "SELECT count(*) FROM $TableUsers WHERE $UserGeboorte BETWEEN '". date("Y-m-d", $start) ."' AND '". date("Y-m-d", $eind) ."'";
	//$sql_m = $sql_all . " AND $UserGeslacht like 'M'";
	//$sql_v = $sql_all . " AND $UserGeslacht like 'V'";
	
	$sql_m = $sql_all . " AND $UserBelijdenis like 'belijdend lid'";
	$sql_v = $sql_all . " AND $UserBelijdenis NOT like 'belijdend lid'";
		
	$result_all = mysqli_query($db, $sql_all);
	$row_all	= mysqli_fetch_array($result_all);
	
	$result_m = mysqli_query($db, $sql_m);
	$row_m	= mysqli_fetch_array($result_m);
	
	$result_v = mysqli_query($db, $sql_v);
	$row_v	= mysqli_fetch_array($result_v);
	
	echo $l ."	". $row_all[0] ."	".$row_m[0]."	".$row_v[0].'<br>';
	//echo $l .' : '. $sql .'<br>';
}


echo $HTMLFooter;
?>