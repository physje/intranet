<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

$eind = time();
$start = $eind - (24*60*60);

$sql = "SELECT * FROM $TableMail WHERE $MailTime BETWEEN $start AND $eind";
$result = mysqli_query($db, $sql);
if($row = mysqli_fetch_array($result)) {
	echo "<table>";
	echo "<tr>";
	echo "	<td><b>Tijdstip</b></td>";
	echo "	<td><b>Ontvanger</b></td>";
	echo "	<td><b>Onderwerp</b></td>";
	echo "</tr>";
	
	do {
		$param = json_decode(urldecode($row[$MailMail]), true);
		
		echo "<tr>";
		echo "	<td>". time2str('%a %e %b %H:%M ', $row[$MailTime]) ."</td>";
		if(is_numeric($param['to'])) {
			echo "	<td>". makeName($param['to'], 5)."</td>";
		} else {
			echo "	<td>". implode('|', $param['to']) ."</td>";
		}
		echo "	<td><a href='?id=". $row[$MailID] ."'>". $param['subject'] ."</a></td>";
		echo "</tr>";
		if(isset($_REQUEST['id']) AND $_REQUEST['id'] == $row[$MailID]) {			
			foreach($param as $key => $value) {
				echo "<tr>";
				echo "	<td valign='top'>". $key ."</td>";
				if(is_array($value)) {
					echo "	<td colspan='2'>". implode('|', $value) ."</td>";
				} else {
					echo "	<td colspan='2'><textarea name='$key' cols=75>". $value ."</textarea></td>";
				}
				echo "</tr>";
			}
			//echo "	<td colspan='3'>". str_replace('\/', '/', urldecode($row[$MailMail])) ."</td>";
			
		}		
	} while($row = mysqli_fetch_array($result));		
}


?>