<?php
include_once('../include/functions.php');
include_once('../include/config.php');
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

$xml[] = '<?xml version="1.0" encoding="utf-8"?>';
$xml[] = '<phonebooks>';
$xml[] = '<phonebook name="3GK Telefoongids '. date("d-m-y").'" owner="1">';

$sql = "SELECT * FROM $TableUsers WHERE $UserTelefoon != '' AND $UserStatus like 'actief'";
$result = mysqli_query($db, $sql);
$row = mysqli_fetch_array($result);

do {	
	$xml[] = '<contact>';
	//$xml[] = '	<category>'. array_search($row[$UserWijk],$wijkArray) .'</category>';
	# 0 =  gewoon; 1 = VIP
	$xml[] = '	<category>0</category>';
	$xml[] = '	<person><realName>'. makeName($row[$UserID], 5) .'</realName></person>';
	$xml[] = '	<telephony nid="1">';
	
	if(substr(trim($row[$UserTelefoon]),0,2) == '06') {
		$xml[] = '		<number type="mobile" prio="1" id="0">'. str_replace('-', '', $row[$UserTelefoon]) .'</number>';
	} else {
		$xml[] = '		<number type="home" prio="1" id="0">'. str_replace('-', '', $row[$UserTelefoon]) .'</number>';
	}	
	
	$xml[] = '	</telephony>';
	//$xml[] = '	<uniqueid>2795</uniqueid>';
	$xml[] = '</contact>';
} while($row = mysqli_fetch_array($result));

$xml[] = '</phonebook>';
$xml[] = '</phonebooks>';

$fp = fopen('3GK_telefoongids-'. date("Y_m_d").'.xml', 'w');
fwrite($fp, implode("\n", $xml));
fclose($fp);

?>