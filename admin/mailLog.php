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
		$param = json_decode($row[$MailMail], true);
		
		echo "<tr>";
		echo "	<td>". time2str('%a %e %b %H:%M ', $row[$MailTime]) ."</td>";
		echo "	<td>". makeName($param['to'], 5)."</td>";
		echo "	<td><a href='?id=". $row[$MailID] ."'>". $param['subject'] ."</a></td>";
		echo "</tr>";		
	} while($row = mysqli_fetch_array($result));		
}


?>