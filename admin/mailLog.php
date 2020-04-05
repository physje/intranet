<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include_once($cfgProgDir. "secure.php");
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
	$block[] = "<table border=0>";
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
		$eersteOntvanger = current($param['to']);
		
		if(count($eersteOntvanger) == 1 AND is_numeric(current($eersteOntvanger))) {
			$block[] = "	<td>". makeName(current($eersteOntvanger), 5)."</td>";
		} elseif(count($eersteOntvanger) == 2) {
			$block[] = "	<td>". $eersteOntvanger[1] ."</td>";
		} else {
			$block[] = "	<td>". $eersteOntvanger ."</td>";
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
			$block[] = "	<td colspan='3'>".urldecode($row[$MailMail])."</td>";
			$block[] = "</tr>";
						
			$block[] = "<tr>";
			$block[] = "	<td>&nbsp;</td>";
			$block[] = "	<td colspan='3' align='center'><input type='submit' name='send' value='Versturen'></td>";
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