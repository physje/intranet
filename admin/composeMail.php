<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_TopBottom.php');

$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

if(isset($_POST['send'])) {
	foreach($_POST as $key => $value) {
		if($value != '' AND $key != 'send') {
			$parameters[$key] = $value;
		}
	}
	
	if(sendMail_new($parameters)) {
		$block[] = "'". $parameters['subject'] ."' is succesvol verstuurd";
	} else {
		$block[] = "'". $parameters['subject'] ."' kon helaas niet verstuurd worden";
	}
} else {
	if(isset($_REQUEST['id'])) {
		$db = connect_db();
		
		$id = $_REQUEST['id'];
	
		$sql = "SELECT * FROM $TableMail WHERE $MailID = $id";
		$result = mysqli_query($db, $sql);
		$row = mysqli_fetch_array($result);
		$param = json_decode(urldecode($row[$MailMail]), true);
	} else {
		$param = array();
	}
	
	$block[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
	$block[] = "<table border=0>";
	
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
					if(is_array($subvalue)) {
						foreach($subvalue as $subsubkey => $subsubvalue) {
							$block[] = "<input type='text' name='". $key ."[$subkey][$subsubkey]' value='". addslashes ($subsubvalue) ."' size=35>";
						}
					} else {
						$block[] = "<input type='text' name='". $key ."[$subkey]' value='". addslashes($subvalue) ."' size=35> ";
					}
					$block[] = "<br>";
				}					
			} else {
				if($key != 'message') {
					$block[] = "<input type='text' name='$key' value='". addslashes($value) ."' size=75>";
				} else {
					$block[] = "	<textarea name='$key' cols=75 rows=25>". $value ."</textarea>";
				}					
			}
		}
		$block[] = "	</td>";
		$block[] = "</tr>";
	}
	
	$block[] = "<tr>";
	$block[] = "	<td>&nbsp;</td>";
	$block[] = "	<td colspan='3' align='center'><input type='submit' name='send' value='Versturen'></td>";
	$block[] = "</tr>";	

	$block[] = "<tr>";
	$block[] = "	<td>&nbsp;</td>";
	$block[] = "	<td colspan='3' align='center'><textarea name='JSON' cols=75 rows=25>". urldecode($row[$MailMail]) ."</textarea></td>";
	$block[] = "</tr>";
	$block[] = "<tr>";
	$block[] = "	<td>&nbsp;</td>";
	$block[] = "	<td colspan='3' align='center'><input type='submit' name='send' value='Versturen'></td>";
	$block[] = "</tr>";

	$block[] = "</table>";
	$block[] = "</form>";
}

echo $HTMLHeader;
echo showBlock(implode(NL, $block), 100);
echo $HTMLFooter;	

?>