<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

if(isset($_POST['id'])) {
	$id = key($_POST['id']);
}

if(isset($_POST['send'])) {
	$parameters = $_POST;
	unset($parameters['send']);	
	sendMail_new($parameters);
}

//$eind = time();
//$start = $eind - (48*60*60);
//$sql = "SELECT * FROM $TableMail WHERE $MailTime BETWEEN $start AND $eind ORDER BY $MailTime DESC";

$sql = "SELECT * FROM $TableMail ORDER BY $MailTime DESC LIMIT 0,25";

$result = mysqli_query($db, $sql);
if($row = mysqli_fetch_array($result)) {
	$block[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
	$block[] = "<table>";
	$block[] = "<tr>";
	$block[] = "	<td><b>Tijdstip</b></td>";
	$block[] = "	<td><b>Ontvanger</b></td>";
	$block[] = "	<td><b>Onderwerp</b></td>";
	$block[] = "	<td>&nbsp;</td>";
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
		$block[] = "	<td>". $param['subject'] ."</td>";
		if(isset($id) AND $id == $row[$MailID]) {	
			$block[] = "	<td><input type='submit' name='id[0]' value='-'></td>";
		} else {
			$block[] = "	<td><input type='submit' name='id[".$row[$MailID]."]' value='+'></td>";
		}
		$block[] = "</tr>";
		if(isset($id) AND $id == $row[$MailID]) {			
			foreach($mailVariabele as $key) {			
				$block[] = "<tr>";
				$block[] = "	<td valign='top'>". $key ."</td>";
				$block[] = "	<td colspan='3'>";
				if(!isset($param[$key])) {
					$block[] = "<input type='text' name='$key' size=75>";
				} else {
					$value = $param[$key];
					if(is_array($value)) {
						foreach($value as $subkey => $subvalue) {
							$block[] = "<input type='text' name='". $key ."[$subkey]' value='$subvalue' size=35> ";
						}					
					} else {
						if($key != 'message') {
							$block[] = "<input type='text' name='$key' value='$value' size=75>";
						} else {
							$block[] = "	<textarea name='$key' cols=75 rows=25>". $value ."</textarea>";
						}					
					}
				}
				$block[] = "	</td>";
				$block[] = "</tr>";
			}
						
			$block[] = "<tr>";
			$block[] = "<td colspan='3' align='center'><input type='submit' name='send' value='Versturen'></td>";
			$block[] = "</tr>";
		}		
	} while($row = mysqli_fetch_array($result));		
	$block[] = "</table>";
	$block[] = "</form>";
}

echo $HTMLHeader;
echo showBlock(implode(NL, $block), 100);
echo $HTMLFooter;	

?>