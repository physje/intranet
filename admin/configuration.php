<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

if(isset($_POST['save'])) {
	if(isset($_POST['delete'])) {
		foreach($_POST['delete'] as $id => $value) {
			$sql_delete = "DELETE FROM $TableConfig WHERE $ConfigID = $id";
			if(mysqli_query($db, $sql_delete)) {
				$text[] = $id. ' verwijderd';
			}
		}
	}
	
	foreach($_POST['value'] as $id => $value) {
		if(isset($_POST['name'][$id])) 	$name = $_POST['name'][$id];
		
		if(isset($_POST['key'][$id])) {
			$key = $_POST['key'][$id];
		} else {
			$key = '';
		}
		
		if(isset($_POST['comment'][$id])) {
			$comment = $_POST['comment'][$id];
		} else {
			$comment = '';
		}
		
		if($id == 999 AND $value != '') {
			$sql_insert = "INSERT INTO $TableConfig ($ConfigName, $ConfigKey, $ConfigValue, $ConfigOpmerking, $ConfigAdded) VALUES ('". urlencode($name) ."', '". urlencode($key) ."', '". urlencode($value) ."', '". urlencode($comment) ."', '". time() ."')";
			if(mysqli_query($db, $sql_insert)) {
				$text[] = $name. ' toegevoegd';
			} else {
				$text[] = $sql_insert;
			}
		}
		
		if($id != 999) {
			$sql_update = "UPDATE $TableConfig SET $ConfigName = '". urlencode($name) ."', $ConfigKey = '". urlencode($key) ."', $ConfigValue = '". urlencode($value) ."', $ConfigOpmerking = '". urlencode($comment) ."' WHERE $ConfigID = '$id'";
			if(!mysqli_query($db, $sql_update)) {
			$text[] = "$name niet kunnen updaten";
				echo $sql_update .'<br>';
			}
		}		
	}
}

$sql = "SELECT $ConfigName, COUNT(*) as aantal FROM $TableConfig GROUP BY $ConfigName ORDER BY $ConfigName";
$result = mysqli_query($db, $sql);
$row = mysqli_fetch_array($result);
	
$text[] = "<form action='". $_SERVER['PHP_SELF'] ."' method='post'>";
$text[] = "<table border=0>";
$text[] = "<tr>";
$text[] = "	<td><b>Verwijder</b></td>";
$text[] = "	<td><b>Naam</b></td>";
$text[] = "	<td><b>Index</b> (bij array)</td>";
$text[] = "	<td><b>Waarde</b></td>";
$text[] = "	<td><b>Opmerking</b></td>";
$text[] = "</tr>";

do {
	$name = $row[$ConfigName];	
	$aantal = $row['aantal'];
	$sql_name = "SELECT * FROM $TableConfig WHERE $ConfigName like '$name' ORDER BY $ConfigKey";
	$result_name = mysqli_query($db, $sql_name);
	$row_name = mysqli_fetch_array($result_name);
	$first = true;
	
	do {
		$id = $row_name[$ConfigID];	
		$text[] = "<tr>";
		$text[] = "	<td><input type='checkbox' name='delete[$id]' value='1'></td>";
		
		if($first) {
			$text[] = "	<td". ($aantal == 1 ? '' : " rowspan='$aantal' valign='top'") ."><input type='text' name='name[$id]' value='". urldecode($name) ."'></td>";
			$first = false;			
		} else {
			$text[] = "<input type='hidden' name='name[$id]' value='$name'>";
		}
				
		if($row_name[$ConfigKey] != '') {
			$text[] = "	<td><input type='text' name='key[$id]' value='". urldecode($row_name[$ConfigKey]) ."' size='25'></td>";	
			$text[] = "	<td><input type='text' name='value[$id]' value='". urldecode($row_name[$ConfigValue]) ."' size='25'></td>";	
		} else {
			$text[] = "	<td colspan='2'><input type='text' name='value[$id]' value='". urldecode($row_name[$ConfigValue]) ."' size='55'></td>";
		}
		$text[] = "	<td><input type='text' name='comment[$id]' value='". urldecode($row_name[$ConfigOpmerking]) ."' size='45'></td>";
		$text[] = "</tr>";
	} while($row_name = mysqli_fetch_array($result_name));	
} while($row = mysqli_fetch_array($result));

$text[] = "<tr>";
$text[] = "	<td>&nbsp;</td>";
$text[] = "	<td><input type='text' name='name[999]'></td>";
$text[] = "	<td><input type='text' name='key[999]'></td>";	
$text[] = "	<td><input type='text' name='value[999]''></td>";	
$text[] = "	<td><input type='text' name='comment[999]''></td>";	
$text[] = "</tr>";
$text[] = "<tr>";
$text[] = "	<td colspan='5'>&nbsp;</td>";
$text[] = "</tr>";
$text[] = "<tr>";
$text[] = "	<td colspan='5' align='center'><input type='submit' name='save' value='Opslaan'></td>";
$text[] = "</tr>";
$text[] = "</table>";
$text[] = "</form>";


echo $HTMLHeader;
echo implode("\n", $text);
echo $HTMLFooter;

?>