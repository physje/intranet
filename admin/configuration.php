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
		
		if(isset($_POST['groep'][$id])) {
			$groep = $_POST['groep'][$id];
		} else {
			$groep = 1;
		}
		
		if($id == 999 AND $value != '') {
			$sql_insert = "INSERT INTO $TableConfig ($ConfigGroep, $ConfigName, $ConfigKey, $ConfigValue, $ConfigOpmerking, $ConfigAdded) VALUES ($groep, '". urlencode($name) ."', '". urlencode($key) ."', '". urlencode($value) ."', '". urlencode($comment) ."', '". time() ."')";
			if(mysqli_query($db, $sql_insert)) {
				$text[] = $name. ' toegevoegd';
			} else {
				$text[] = $sql_insert;
			}
		}
		
		if($id != 999) {
			$sql_update = "UPDATE $TableConfig SET $ConfigGroep = '$groep', $ConfigName = '". urlencode($name) ."', $ConfigKey = '". urlencode($key) ."', $ConfigValue = '". urlencode($value) ."', $ConfigOpmerking = '". urlencode($comment) ."' WHERE $ConfigID = '$id'";
			if(!mysqli_query($db, $sql_update)) {
			$text[] = "$name niet kunnen updaten";
				echo $sql_update .'<br>';
			}
		}		
	}
}

$text[] = "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
$thead[] = "<table>";
$thead[] = "<thead>";
$thead[] = "<tr>";
$thead[] = "	<th>Verwijder</th>";
if($configMoveGroups) {
	$thead[] = "	<th>Groep</th>";
}
$thead[] = "	<th>Naam</th>";
$thead[] = "	<th>Index</th>";
$thead[] = "	<th>Waarde</th>";
$thead[] = "	<th>Opmerking</th>";
$thead[] = "</tr>";
$thead[] = "</thead>";

$tfooter[] = "</table>";

$configGroups = array_merge(array(0 => 'Onbekend'), $configGroups);

foreach($configGroups as $groepID => $groepNaam) {
	$sql = "SELECT $ConfigName, COUNT(*) as aantal FROM $TableConfig WHERE $ConfigGroep = $groepID GROUP BY $ConfigName ORDER BY $ConfigName";
	$result = mysqli_query($db, $sql);
		
	if($row = mysqli_fetch_array($result)) {
		$text = array();
		
		do {
			$name = $row[$ConfigName];
			$groep = $groepID;
			$aantal = $row['aantal'];
			$sql_name = "SELECT * FROM $TableConfig WHERE $ConfigName like '$name' AND $ConfigGroep = $groepID ORDER BY $ConfigKey";
			$result_name = mysqli_query($db, $sql_name);
			$row_name = mysqli_fetch_array($result_name);
			$first = true;
			
			do {
				$id = $row_name[$ConfigID];
											
				$text[] = "<tr>";
				$text[] = "	<td><input type='checkbox' name='delete[$id]' value='1'></td>";
				
				if($configMoveGroups) {
					$text[] = "	<td><select name='groep[$id]'>";
					foreach($configGroups as $groupID => $groupName) {
						$text[] = "	<option value='$groupID'". ($groep == $groupID ? ' selected' : '') .">$groupName</option>";
					}					
					$text[] = "	</select></td>";
				} else {
					$text[] = "	<input type='hidden' name='groep[$id]' value='$groep'>";
				}
				
				if($first) {
					#$text[] = "	<td". ($aantal == 1 ? '' : " rowspan='$aantal' valign='top'") ."><input type='text' name='name[$id]' value='". urldecode($name) ."'></td>";
					$text[] = "	<td><input type='text' name='name[$id]' value='". urldecode($name) ."'></td>";
					$first = false;	
				} else {					
					$text[] = "	<td>&nbsp;</td>";
					$text[] = "	<input type='hidden' name='name[$id]' value='$name'>";
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
		
		$block[$groepID] = array_merge($thead, $text, $tfooter);
	}
}

$text = array();
#$text[] = "<tr>";
#$text[] = "	<td>&nbsp;</td>";
#$text[] = "	<td colspan='". ($configMoveGroups ? 5 : 4)."'><h2>Nieuwe toevoegen</h2></td>";
#$text[] = "</tr>";		
$text[] = "<tr>";
$text[] = "	<td>&nbsp;</td>";
if($configMoveGroups) {
	$text[] = "	<td>&nbsp;</td>";
}
$text[] = "	<td><input type='text' name='name[999]'></td>";
$text[] = "	<td><input type='text' name='key[999]'></td>";	
$text[] = "	<td><input type='text' name='value[999]''></td>";	
$text[] = "	<td><input type='text' name='comment[999]''></td>";	
$text[] = "</tr>";
#$text[] = "<tr>";
#$text[] = "	<td colspan='". ($configMoveGroups ? 6 : 5)."'>&nbsp;</td>";
#$text[] = "</tr>";
#$text[] = "<tr>";
#$text[] = "	<td colspan='". ($configMoveGroups ? 6 : 5)."' align='center'><input type='submit' name='save' value='Opslaan'></td>";
#$text[] = "</tr>";
#$text[] = "</table>";
#$text[] = "</form>";

$block[($groepID+1)] = array_merge($thead, $text, $tfooter);
$configGroups[($groepID+1)] = 'Nieuwe toevoegen';

$header[] = '<style>';
$header[] = '@media only screen and (max-width: 760px), (min-device-width: 768px) and (max-device-width: 1024px)  {';
$header[] = '	td:nth-of-type(1):before { content: "Verwijder"; }';
if($configMoveGroups) {
	$header[] = '	td:nth-of-type(2):before { content: "Groep"; }';
	$header[] = '	td:nth-of-type(3):before { content: "Naam"; }';
	$header[] = '	td:nth-of-type(4):before { content: "Index"; }';
	$header[] = '	td:nth-of-type(5):before { content: "Waarde"; }';
	$header[] = '	td:nth-of-type(6):before { content: "Opmerking"; }';
} else {
	$header[] = '	td:nth-of-type(2):before { content: "Naam"; }';
	$header[] = '	td:nth-of-type(3):before { content: "Index"; }';
	$header[] = '	td:nth-of-type(4):before { content: "Waarde"; }';
	$header[] = '	td:nth-of-type(5):before { content: "Opmerking"; }';
}
$header[] = "}";
$header[] = "</style>";	

$tables = array('default', 'table_rot');

echo showCSSHeader($tables, $header);
echo '<div class="content_vert_kolom_full">'.NL;
echo "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
foreach($block as $id => $blok) {
	echo "<h1>". $configGroups[$id] ."</h1>";
	echo "<div class='content_block'>".NL. implode(NL, $blok).NL."</div>".NL;
}

echo "<p class='after_table'><input type='submit' name='save' value='Opslaan'></p>";	
echo '</form>';
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

?>