<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Mysql.php');

$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

if(isset($_POST['id'])) {
	$id = key($_POST['id']);
}

if(isset($_POST['volgende'])) {
	$start = ($_POST['start']+25);
} elseif(isset($_POST['vorige'])) {
	$start = ($_POST['start']-25);
} elseif(isset($_POST['start'])) {
	$start = $_POST['start'];
} else {
	$start = 0;
}

$db = new Mysql();

$sql = "SELECT * FROM `mail_log` ORDER BY `tijd` DESC LIMIT $start,25";
$data = $db->select($sql, true);

if(count($data) > 0) {
	$block[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
	$block[] = "<input type='hidden' name='start' value='$start'>";
	$block[] = "<table>";
	$block[] = "<thead>";
	$block[] = "<tr>";	
	$block[] = "	<th>Tijdstip</th>";
	$block[] = "	<th>Ontvanger</th>";
	$block[] = "	<th>Onderwerp</th>";
	$block[] = "	<th>&nbsp;</th>";
	$block[] = "</tr>";
	$block[] = "</thead>";
	
	do {		
		$mail = unserialize($data['bericht']);
		
		$block[] = "<tr>";		
		$block[] = "	<td>". time2str('E d L HH:mm ', $row[$MailTime]) ."</td>";
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
			$block[] = "<tr>";
			$block[] = "	<td>&nbsp;</td>";
			$block[] = "	<td colspan='3'>";
			$block[] = "	<table border=1>";
			
			foreach($mailVariabele as $key) {			
				if(isset($param[$key])) {
					$value = $param[$key];
					
					$block[] = "<tr>";
					$block[] = "	<td valign='top'>". $key ."</td>";
					$block[] = "	<td>";
					
					if(is_array($value)) {						
						foreach($value as $subkey => $subvalue) {
							if(is_array($subvalue)) {
								foreach($subvalue as $subsubkey => $subsubvalue) {
									$block[] = addslashes ($subsubvalue);
								}
							} else {
								$block[] = addslashes ($subsubvalue);
							}
							$block[] = "<br>";
						}					
					} else {
						if($key != 'message') {
							$block[] = addslashes($value);
						} else {
							$block[] = $value;
						}
					}
					
					$block[] = "	</td>";
					$block[] = "</tr>";
				}
			}
						
			$block[] = "	<tr>";
			$block[] = "		<td>&nbsp;</td>";
			$block[] = "		<td align='center'><a href='composeMail.php?id=$id'>Bewerk deze mail</a></td>";
			$block[] = "	</tr>";
			$block[] = "	</table>";
			$block[] = "</td>";
			$block[] = "</tr>";
		}		
	} while($row = mysqli_fetch_array($result));
	
	/*
	$block[] = "<tr>";
	$block[] = "	<td>&nbsp;</td>";
	
	if($start > 20) {
		$block[] = "	<td align='left'><input type='submit' name='vorige' value='Vorige'></td>";
	} else {
		$block[] = "	<td>&nbsp;</td>";
	}
	
	$block[] = "	<td>&nbsp;</td>";
	$block[] = "	<td align='right'><input type='submit' name='volgende' value='Volgende'></td>";
	$block[] = "</tr>";
	*/	
	$block[] = "</table>";	
	$block[] = "<p class='after_table'>". ($start > 20 ? "<input type='submit' name='vorige' value='Vorige'>" : ''). "&nbsp;<input type='submit' name='volgende' value='Volgende'></p>";	
	$block[] = "</form>";
}

echo showCSSHeader(array('default', 'table_default'));
echo '<div class="content_vert_kolom_full">'.NL;
echo "<div class='content_block'>".NL. implode(NL, $block).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

?>