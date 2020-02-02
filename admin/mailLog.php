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
	$block[] = "<table>";
	$block[] = "<tr>";
	$block[] = "	<td><b>Tijdstip</b></td>";
	$block[] = "	<td><b>Ontvanger</b></td>";
	$block[] = "	<td><b>Onderwerp</b></td>";
	$block[] = "</tr>";
	
	do {
		$param = json_decode(urldecode($row[$MailMail]), true);
		
		$block[] = "<tr>";
		$block[] = "	<td>". time2str('%a %e %b %H:%M ', $row[$MailTime]) ."</td>";
		if(is_numeric($param['to'])) {
			$block[] = "	<td>". makeName($param['to'], 5)."</td>";
		} elseif(is_array($param['to'])) {
			$block[] = "	<td>". $param['to'][1] ."</td>";
		} else {
			$block[] = "	<td>". $param['to'] ."</td>";
		}
		$block[] = "	<td><a href='?id=". $row[$MailID] ."'>". $param['subject'] ."</a></td>";
		$block[] = "</tr>";
		if(isset($_REQUEST['id']) AND $_REQUEST['id'] == $row[$MailID]) {			
			foreach($param as $key => $value) {
				$block[] = "<tr>";
				$block[] = "	<td valign='top'>". $key ."</td>";
				if(is_array($value)) {
					$block[] = "	<td colspan='2'>";
					foreach($value as $subkey => $subvalue) {
						$block[] = "<input type='text' name='$key[$subkey]' value='$subvalue' size=35> ";
					}
					$block[] = "</td>";
				} else {
					$block[] = "<td colspan='2'>";
					if($key != 'message') {
						$block[] = "<input type='text' name='$key' value='$value' size=75>";
					} else {
						$block[] = "	<textarea name='$key' cols=75 rows=25>". $value ."</textarea>";
					}
					$block[] = "</td>";
				}
				$block[] = "</tr>";
			}
			//echo "	<td colspan='3'>". str_replace('\/', '/', urldecode($row[$MailMail])) ."</td>";
			
		$block[] = "<tr>";
		$block[] = "<td colspan='3' align='center'><input type='submit' name='send' value='Versturen'></td>";
		$block[] = "</tr>";
		}		
	} while($row = mysqli_fetch_array($result));		
	$block[] = "</table>";
}

echo $HTMLHeader;
echo showBlock(implode(NL, $block), 100);
echo $HTMLFooter;	

?>